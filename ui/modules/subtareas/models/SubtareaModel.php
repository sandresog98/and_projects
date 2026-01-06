<?php
/**
 * AND PROJECTS APP - Modelo de Subtareas
 */

require_once __DIR__ . '/../../../config/paths.php';

class SubtareaModel {
    private PDO $db;
    private string $table = 'proyectos_subtareas';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll(array $filters = []): array {
        $sql = "SELECT s.*, 
                       t.nombre as tarea_nombre,
                       t.proyecto_id,
                       p.nombre as proyecto_nombre,
                       p.empresa_id,
                       e.nombre as empresa_nombre,
                       e.logo as empresa_logo,
                       u.nombre as realizado_por_nombre
                FROM {$this->table} s
                LEFT JOIN proyectos_tareas t ON s.tarea_id = t.id
                LEFT JOIN proyectos_proyectos p ON t.proyecto_id = p.id
                LEFT JOIN proyectos_empresas e ON p.empresa_id = e.id
                LEFT JOIN proyectos_usuarios u ON s.realizado_por = u.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (s.nombre LIKE :search OR s.descripcion LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['empresa_id'])) {
            $sql .= " AND p.empresa_id = :empresa_id";
            $params['empresa_id'] = $filters['empresa_id'];
        }
        
        if (!empty($filters['tarea_id'])) {
            $sql .= " AND s.tarea_id = :tarea_id";
            $params['tarea_id'] = $filters['tarea_id'];
        }
        
        if (!empty($filters['proyecto_id'])) {
            $sql .= " AND t.proyecto_id = :proyecto_id";
            $params['proyecto_id'] = $filters['proyecto_id'];
        }
        
        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $sql .= " AND s.estado = :estado";
            $params['estado'] = $filters['estado'];
        }
        
        if (!empty($filters['realizado_por'])) {
            $sql .= " AND s.realizado_por = :realizado_por";
            $params['realizado_por'] = $filters['realizado_por'];
        }
        
        if (!empty($filters['exclude_cancelled'])) {
            $sql .= " AND s.estado != 5";
        }
        
        $sql .= " ORDER BY s.orden ASC, s.fecha_creacion ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById(int $id): ?array {
        $sql = "SELECT s.*, 
                       t.nombre as tarea_nombre,
                       t.proyecto_id,
                       p.nombre as proyecto_nombre,
                       p.empresa_id,
                       e.nombre as empresa_nombre,
                       u.nombre as realizado_por_nombre
                FROM {$this->table} s
                LEFT JOIN proyectos_tareas t ON s.tarea_id = t.id
                LEFT JOIN proyectos_proyectos p ON t.proyecto_id = p.id
                LEFT JOIN proyectos_empresas e ON p.empresa_id = e.id
                LEFT JOIN proyectos_usuarios u ON s.realizado_por = u.id
                WHERE s.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    public function create(array $data): int {
        // Obtener el siguiente orden
        $sqlOrden = "SELECT IFNULL(MAX(orden), 0) + 1 FROM {$this->table} WHERE tarea_id = :tarea_id";
        $stmtOrden = $this->db->prepare($sqlOrden);
        $stmtOrden->execute(['tarea_id' => $data['tarea_id']]);
        $orden = $stmtOrden->fetchColumn();
        
        $sql = "INSERT INTO {$this->table} 
                (tarea_id, nombre, descripcion, fecha_inicio_estimada, fecha_fin_estimada, 
                 horas_estimadas, realizado_por, orden)
                VALUES 
                (:tarea_id, :nombre, :descripcion, :fecha_inicio_estimada, :fecha_fin_estimada,
                 :horas_estimadas, :realizado_por, :orden)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tarea_id' => $data['tarea_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha_inicio_estimada' => $data['fecha_inicio_estimada'] ?? null,
            'fecha_fin_estimada' => $data['fecha_fin_estimada'] ?? null,
            'horas_estimadas' => $data['horas_estimadas'] ?? null,
            'realizado_por' => $data['realizado_por'] ?? null,
            'orden' => $orden
        ]);
        
        $id = (int)$this->db->lastInsertId();
        
        // Actualizar avance de la tarea padre
        $this->actualizarTareaPadre($data['tarea_id']);
        
        return $id;
    }
    
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'nombre', 'descripcion', 'fecha_inicio_estimada', 'fecha_fin_estimada',
            'fecha_fin_real', 'estado', 'horas_estimadas', 'realizado_por', 'orden'
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
        $result = $stmt->execute($params);
        
        // Actualizar avance de la tarea padre
        $subtarea = $this->getById($id);
        if ($subtarea) {
            $this->actualizarTareaPadre($subtarea['tarea_id']);
        }
        
        return $result;
    }
    
    public function delete(int $id): bool {
        $subtarea = $this->getById($id);
        if (!$subtarea) return false;
        
        $result = $this->update($id, ['estado' => 5]);
        
        // Actualizar avance de la tarea padre
        $this->actualizarTareaPadre($subtarea['tarea_id']);
        
        return $result;
    }
    
    public function getByTarea(int $tareaId): array {
        return $this->getAll(['tarea_id' => $tareaId, 'exclude_cancelled' => true]);
    }
    
    // ====== TRACKING DE TIEMPO ======
    
    public function registrarTiempo(array $data): int {
        $sql = "INSERT INTO proyectos_tiempos 
                (subtarea_id, usuario_id, fecha, horas, minutos, descripcion)
                VALUES 
                (:subtarea_id, :usuario_id, :fecha, :horas, :minutos, :descripcion)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'subtarea_id' => $data['subtarea_id'],
            'usuario_id' => $data['usuario_id'],
            'fecha' => $data['fecha'] ?? date('Y-m-d'),
            'horas' => $data['horas'] ?? 0,
            'minutos' => $data['minutos'] ?? 0,
            'descripcion' => $data['descripcion'] ?? null
        ]);
        
        $id = (int)$this->db->lastInsertId();
        
        // Actualizar horas_reales de la subtarea
        $this->actualizarHorasReales($data['subtarea_id']);
        
        return $id;
    }
    
    public function getTiemposSubtarea(int $subtareaId): array {
        $sql = "SELECT t.*, u.nombre as usuario_nombre 
                FROM proyectos_tiempos t
                LEFT JOIN proyectos_usuarios u ON t.usuario_id = u.id
                WHERE t.subtarea_id = :subtarea_id
                ORDER BY t.fecha DESC, t.fecha_creacion DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['subtarea_id' => $subtareaId]);
        return $stmt->fetchAll();
    }
    
    public function eliminarTiempo(int $tiempoId): bool {
        // Obtener subtarea antes de eliminar
        $sql = "SELECT subtarea_id FROM proyectos_tiempos WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $tiempoId]);
        $tiempo = $stmt->fetch();
        
        if (!$tiempo) return false;
        
        $sqlDelete = "DELETE FROM proyectos_tiempos WHERE id = :id";
        $stmtDelete = $this->db->prepare($sqlDelete);
        $result = $stmtDelete->execute(['id' => $tiempoId]);
        
        // Actualizar horas reales
        $this->actualizarHorasReales($tiempo['subtarea_id']);
        
        return $result;
    }
    
    private function actualizarHorasReales(int $subtareaId): void {
        $sql = "UPDATE {$this->table} SET 
                horas_reales = (
                    SELECT IFNULL(SUM(horas + (minutos / 60)), 0)
                    FROM proyectos_tiempos WHERE subtarea_id = :subtarea_id
                )
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['subtarea_id' => $subtareaId, 'id' => $subtareaId]);
    }
    
    private function actualizarTareaPadre(int $tareaId): void {
        require_once __DIR__ . '/../../tareas/models/TareaModel.php';
        $tareaModel = new TareaModel();
        $tareaModel->actualizarAvance($tareaId);
    }
    
    // ====== COMPLETAR/MARCAR COMO HECHA ======
    
    public function completar(int $id, int $usuarioId): bool {
        return $this->update($id, [
            'estado' => 3, // Completada
            'fecha_fin_real' => date('Y-m-d'),
            'realizado_por' => $usuarioId
        ]);
    }
    
    public function reabrir(int $id): bool {
        return $this->update($id, [
            'estado' => 2, // En progreso
            'fecha_fin_real' => null
        ]);
    }
    
    // ====== ESTADÍSTICAS ======
    
    public function getEstadisticasPorTarea(int $tareaId): array {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 2 THEN 1 ELSE 0 END) as en_progreso,
                    SUM(CASE WHEN estado = 3 THEN 1 ELSE 0 END) as completadas,
                    SUM(CASE WHEN estado = 4 THEN 1 ELSE 0 END) as bloqueadas,
                    SUM(IFNULL(horas_estimadas, 0)) as horas_estimadas_total,
                    SUM(IFNULL(horas_reales, 0)) as horas_reales_total
                FROM {$this->table} 
                WHERE tarea_id = :tarea_id AND estado != 5";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tarea_id' => $tareaId]);
        return $stmt->fetch();
    }
    
    public function getColaboradoresSelect(): array {
        $sql = "SELECT id, nombre FROM proyectos_usuarios WHERE rol IN ('admin', 'colaborador') AND estado = 1 ORDER BY nombre";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}

