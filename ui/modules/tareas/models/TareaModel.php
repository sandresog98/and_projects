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
        
        $sql .= " ORDER BY t.prioridad DESC, t.fecha_fin_estimada ASC";
        
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
}
