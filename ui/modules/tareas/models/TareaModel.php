<?php
/**
 * AND PROJECTS APP - Modelo de Tareas
 */

require_once __DIR__ . '/../../../config/paths.php';

class TareaModel {
    private PDO $db;
    private string $table = 'proyectos_tareas';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll(array $filters = []): array {
        $sql = "SELECT t.*, 
                       p.nombre as proyecto_nombre,
                       p.color as proyecto_color,
                       e.nombre as empresa_nombre,
                       e.logo as empresa_logo,
                       u.nombre as asignado_nombre,
                       (SELECT COUNT(*) FROM proyectos_subtareas WHERE tarea_id = t.id) as total_subtareas,
                       (SELECT COUNT(*) FROM proyectos_subtareas WHERE tarea_id = t.id AND estado = 3) as subtareas_completadas
                FROM {$this->table} t
                LEFT JOIN proyectos_proyectos p ON t.proyecto_id = p.id
                LEFT JOIN proyectos_empresas e ON p.empresa_id = e.id
                LEFT JOIN proyectos_usuarios u ON t.asignado_id = u.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (t.nombre LIKE :search OR t.descripcion LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['proyecto_id'])) {
            $sql .= " AND t.proyecto_id = :proyecto_id";
            $params['proyecto_id'] = $filters['proyecto_id'];
        }
        
        if (!empty($filters['empresa_id'])) {
            $sql .= " AND p.empresa_id = :empresa_id";
            $params['empresa_id'] = $filters['empresa_id'];
        }
        
        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $sql .= " AND t.estado = :estado";
            $params['estado'] = $filters['estado'];
        }
        
        if (!empty($filters['asignado_id'])) {
            $sql .= " AND t.asignado_id = :asignado_id";
            $params['asignado_id'] = $filters['asignado_id'];
        }
        
        if (!empty($filters['exclude_cancelled'])) {
            $sql .= " AND t.estado != 5";
        }
        
        // Ordenar por: tareas sin dependencia primero (por fecha creación), luego tareas con dependencia
        $sql .= " ORDER BY 
                    CASE WHEN EXISTS (
                        SELECT 1 FROM proyectos_dependencias d 
                        WHERE d.tipo_destino = 'tarea' AND d.id_destino = t.id
                    ) THEN 1 ELSE 0 END,
                    t.fecha_creacion ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById(int $id): ?array {
        $sql = "SELECT t.*, 
                       p.nombre as proyecto_nombre,
                       p.color as proyecto_color,
                       e.nombre as empresa_nombre,
                       e.id as empresa_id,
                       e.logo as empresa_logo,
                       u.nombre as asignado_nombre,
                       c.nombre as creador_nombre
                FROM {$this->table} t
                LEFT JOIN proyectos_proyectos p ON t.proyecto_id = p.id
                LEFT JOIN proyectos_empresas e ON p.empresa_id = e.id
                LEFT JOIN proyectos_usuarios u ON t.asignado_id = u.id
                LEFT JOIN proyectos_usuarios c ON t.creado_por = c.id
                WHERE t.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    public function create(array $data): int {
        $sql = "INSERT INTO {$this->table} 
                (proyecto_id, nombre, descripcion, fecha_inicio_estimada, fecha_fin_estimada, 
                 prioridad, asignado_id, creado_por)
                VALUES 
                (:proyecto_id, :nombre, :descripcion, :fecha_inicio_estimada, :fecha_fin_estimada,
                 :prioridad, :asignado_id, :creado_por)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'proyecto_id' => $data['proyecto_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha_inicio_estimada' => $data['fecha_inicio_estimada'] ?? null,
            'fecha_fin_estimada' => $data['fecha_fin_estimada'] ?? null,
            'prioridad' => $data['prioridad'] ?? 2,
            'asignado_id' => $data['asignado_id'] ?? null,
            'creado_por' => $data['creado_por']
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'nombre', 'descripcion', 'fecha_inicio_estimada', 'fecha_fin_estimada',
            'fecha_fin_real', 'estado', 'prioridad', 'asignado_id', 'avance'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) return false;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete(int $id): bool {
        return $this->update($id, ['estado' => 5]); // Marcar como cancelada
    }
    
    public function getByProyecto(int $proyectoId): array {
        return $this->getAll(['proyecto_id' => $proyectoId]);
    }
    
    public function actualizarAvance(int $tareaId): void {
        $sql = "UPDATE {$this->table} SET 
                avance = (
                    SELECT IFNULL(
                        ROUND(SUM(CASE WHEN estado = 3 THEN 1 ELSE 0 END) / COUNT(*) * 100, 2), 
                        0
                    )
                    FROM proyectos_subtareas WHERE tarea_id = :tarea_id
                )
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tarea_id' => $tareaId, 'id' => $tareaId]);
        
        // Actualizar estado de la tarea
        $this->actualizarEstadoAutomatico($tareaId);
    }
    
    private function actualizarEstadoAutomatico(int $tareaId): void {
        $tarea = $this->getById($tareaId);
        if (!$tarea) return;
        
        // Obtener subtareas
        $sql = "SELECT estado FROM proyectos_subtareas WHERE tarea_id = :tarea_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tarea_id' => $tareaId]);
        $subtareas = $stmt->fetchAll();
        
        if (empty($subtareas)) return;
        
        $estados = array_column($subtareas, 'estado');
        
        if (count(array_unique($estados)) === 1 && $estados[0] == 3) {
            // Todas completadas
            $this->update($tareaId, [
                'estado' => 3,
                'fecha_fin_real' => date('Y-m-d')
            ]);
        } elseif (in_array(2, $estados)) {
            // Al menos una en progreso
            if ($tarea['estado'] == 1) {
                $this->update($tareaId, ['estado' => 2]);
            }
        }
    }
    
    public function getArbolDependencias(int $proyectoId): array {
        // Obtener todas las tareas del proyecto
        $tareas = $this->getByProyecto($proyectoId);
        
        // Obtener dependencias
        $sql = "SELECT * FROM proyectos_dependencias WHERE tipo_origen = 'tarea' AND tipo_destino = 'tarea'";
        $stmt = $this->db->query($sql);
        $deps = $stmt->fetchAll();
        
        // Construir mapa de dependencias
        $dependencias = [];
        foreach ($deps as $dep) {
            $dependencias[$dep['id_destino']][] = $dep['id_origen'];
        }
        
        // Identificar tareas raíz (sin dependencias)
        $tareasConDep = array_keys($dependencias);
        $tareasRaiz = array_filter($tareas, fn($t) => !in_array($t['id'], $tareasConDep));
        
        // Construir árbol recursivo
        $arbol = [];
        foreach ($tareasRaiz as $tarea) {
            $arbol[] = $this->construirNodo($tarea, $tareas, $dependencias);
        }
        
        return $arbol;
    }
    
    private function construirNodo(array $tarea, array $todasTareas, array $dependencias): array {
        $nodo = [
            'id' => $tarea['id'],
            'nombre' => $tarea['nombre'],
            'estado' => $tarea['estado'],
            'avance' => $tarea['avance'] ?? 0,
            'hijos' => []
        ];
        
        // Encontrar tareas que dependen de esta
        foreach ($todasTareas as $t) {
            if (isset($dependencias[$t['id']]) && in_array($tarea['id'], $dependencias[$t['id']])) {
                $nodo['hijos'][] = $this->construirNodo($t, $todasTareas, $dependencias);
            }
        }
        
        return $nodo;
    }
    
    public function getColaboradoresSelect(): array {
        $sql = "SELECT id, nombre FROM proyectos_usuarios WHERE rol IN ('admin', 'colaborador') AND estado = 1 ORDER BY nombre";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getTareasActivas(): array {
        $sql = "SELECT t.id, t.nombre, p.nombre as proyecto_nombre
                FROM {$this->table} t
                LEFT JOIN proyectos_proyectos p ON t.proyecto_id = p.id
                WHERE t.estado NOT IN (3, 5)
                ORDER BY p.nombre, t.nombre";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    // ==========================================
    // MÉTODOS DE DEPENDENCIAS
    // ==========================================
    
    /**
     * Obtiene las tareas disponibles para ser predecesoras de una tarea
     * (del mismo proyecto, excluyendo la tarea actual y sus dependientes)
     */
    public function getTareasPredecesoras(int $proyectoId, ?int $excludeTareaId = null): array {
        $sql = "SELECT t.id, t.nombre, t.estado,
                       CASE t.estado 
                           WHEN 1 THEN 'Pendiente'
                           WHEN 2 THEN 'En progreso'
                           WHEN 3 THEN 'Completada'
                           WHEN 4 THEN 'En espera'
                           ELSE 'Otro'
                       END as estado_texto
                FROM {$this->table} t
                WHERE t.proyecto_id = :proyecto_id 
                AND t.estado NOT IN (5)";
        
        $params = ['proyecto_id' => $proyectoId];
        
        if ($excludeTareaId) {
            $sql .= " AND t.id != :exclude_id";
            $params['exclude_id'] = $excludeTareaId;
            
            // También excluir tareas que dependen de la tarea actual (evitar ciclos)
            $sql .= " AND t.id NOT IN (
                SELECT d.id_destino FROM proyectos_dependencias d 
                WHERE d.tipo_origen = 'tarea' AND d.id_origen = :exclude_id2
            )";
            $params['exclude_id2'] = $excludeTareaId;
        }
        
        $sql .= " ORDER BY t.fecha_creacion ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene la tarea predecesora de una tarea (si existe)
     */
    public function getTareaPredecesora(int $tareaId): ?array {
        $sql = "SELECT t.*, d.id as dependencia_id
                FROM proyectos_dependencias d
                JOIN {$this->table} t ON t.id = d.id_origen
                WHERE d.tipo_destino = 'tarea' 
                AND d.id_destino = :tarea_id
                AND d.tipo_origen = 'tarea'
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tarea_id' => $tareaId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Obtiene las tareas que dependen de una tarea (sucesoras)
     */
    public function getTareasSucesoras(int $tareaId): array {
        $sql = "SELECT t.*
                FROM proyectos_dependencias d
                JOIN {$this->table} t ON t.id = d.id_destino
                WHERE d.tipo_origen = 'tarea' 
                AND d.id_origen = :tarea_id
                AND d.tipo_destino = 'tarea'
                ORDER BY t.fecha_creacion ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tarea_id' => $tareaId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Establece la dependencia de una tarea (tarea depende de predecesora)
     */
    public function setDependencia(int $tareaId, ?int $predecesoraId): bool {
        // Primero eliminar dependencias existentes de esta tarea
        $this->removeDependencia($tareaId);
        
        if ($predecesoraId === null) {
            return true; // Solo queríamos eliminar
        }
        
        // Verificar que no se cree un ciclo
        if ($this->creaciaCiclo($tareaId, $predecesoraId)) {
            return false;
        }
        
        $sql = "INSERT INTO proyectos_dependencias (tipo_origen, id_origen, tipo_destino, id_destino)
                VALUES ('tarea', :predecesora_id, 'tarea', :tarea_id)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'predecesora_id' => $predecesoraId,
            'tarea_id' => $tareaId
        ]);
    }
    
    /**
     * Elimina la dependencia de una tarea
     */
    public function removeDependencia(int $tareaId): bool {
        $sql = "DELETE FROM proyectos_dependencias 
                WHERE tipo_destino = 'tarea' AND id_destino = :tarea_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['tarea_id' => $tareaId]);
    }
    
    /**
     * Verifica si crear una dependencia crearía un ciclo
     */
    private function creaciaCiclo(int $tareaId, int $predecesoraId): bool {
        // Si la predecesora depende directa o indirectamente de la tarea, hay ciclo
        $visitados = [];
        return $this->dependeIndirectamenteDe($predecesoraId, $tareaId, $visitados);
    }
    
    private function dependeIndirectamenteDe(int $tareaId, int $objetivoId, array &$visitados): bool {
        if (in_array($tareaId, $visitados)) {
            return false; // Ya visitado, evitar bucle infinito
        }
        $visitados[] = $tareaId;
        
        $predecesora = $this->getTareaPredecesora($tareaId);
        if (!$predecesora) {
            return false;
        }
        
        if ($predecesora['id'] == $objetivoId) {
            return true; // Encontramos el objetivo, hay ciclo
        }
        
        return $this->dependeIndirectamenteDe($predecesora['id'], $objetivoId, $visitados);
    }
    
    /**
     * Verifica si una tarea está bloqueada (su predecesora no está completada)
     */
    public function estaBloqueada(int $tareaId): bool {
        $predecesora = $this->getTareaPredecesora($tareaId);
        if (!$predecesora) {
            return false; // Sin dependencia, no está bloqueada
        }
        
        // Bloqueada si la predecesora no está completada (estado 3)
        return $predecesora['estado'] != 3;
    }
    
    /**
     * Obtiene información de bloqueo de una tarea
     */
    public function getInfoBloqueo(int $tareaId): ?array {
        $predecesora = $this->getTareaPredecesora($tareaId);
        if (!$predecesora) {
            return null;
        }
        
        if ($predecesora['estado'] == 3) {
            return null; // Predecesora completada, no hay bloqueo
        }
        
        return [
            'bloqueada' => true,
            'predecesora_id' => $predecesora['id'],
            'predecesora_nombre' => $predecesora['nombre'],
            'predecesora_estado' => $predecesora['estado']
        ];
    }
    
    /**
     * Obtiene tareas ordenadas por árbol de dependencias para un proyecto
     */
    public function getTareasOrdenadas(int $proyectoId): array {
        $tareas = $this->getByProyecto($proyectoId);
        
        // Crear mapa de id => tarea
        $mapaTareas = [];
        foreach ($tareas as $tarea) {
            $tarea['predecesora'] = $this->getTareaPredecesora($tarea['id']);
            $tarea['bloqueada'] = $tarea['predecesora'] && $tarea['predecesora']['estado'] != 3;
            $mapaTareas[$tarea['id']] = $tarea;
        }
        
        // Separar tareas raíz y con dependencia
        $tareasRaiz = [];
        $tareasConDep = [];
        
        foreach ($mapaTareas as $tarea) {
            if (!$tarea['predecesora']) {
                $tareasRaiz[] = $tarea;
            } else {
                $tareasConDep[$tarea['predecesora']['id']][] = $tarea;
            }
        }
        
        // Construir lista ordenada
        $resultado = [];
        foreach ($tareasRaiz as $tarea) {
            $this->agregarTareaYSucesoras($tarea, $tareasConDep, $resultado, 0);
        }
        
        return $resultado;
    }
    
    private function agregarTareaYSucesoras(array $tarea, array &$tareasConDep, array &$resultado, int $nivel): void {
        $tarea['nivel_dependencia'] = $nivel;
        $resultado[] = $tarea;
        
        // Agregar sucesoras recursivamente
        if (isset($tareasConDep[$tarea['id']])) {
            foreach ($tareasConDep[$tarea['id']] as $sucesora) {
                $this->agregarTareaYSucesoras($sucesora, $tareasConDep, $resultado, $nivel + 1);
            }
        }
    }
}
