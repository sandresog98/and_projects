<?php
/**
 * AND PROJECTS APP - Proyecto Model
 * Modelo para gestión de proyectos
 */

class ProyectoModel {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener proyecto por ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*, e.nombre as empresa_nombre, e.logo as empresa_logo,
                   u.nombre as creador_nombre
            FROM proyectos_proyectos p
            LEFT JOIN proyectos_empresas e ON p.empresa_id = e.id
            LEFT JOIN proyectos_usuarios u ON p.creado_por = u.id
            WHERE p.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Obtener todos los proyectos
     */
    public function getAll(array $filters = []): array {
        $sql = "SELECT p.*, e.nombre as empresa_nombre, e.logo as empresa_logo,
                (SELECT COUNT(*) FROM proyectos_tareas WHERE proyecto_id = p.id) as total_tareas,
                (SELECT COUNT(*) FROM proyectos_tareas WHERE proyecto_id = p.id AND estado = 3) as tareas_completadas
                FROM proyectos_proyectos p
                LEFT JOIN proyectos_empresas e ON p.empresa_id = e.id
                WHERE 1=1";
        $params = [];
        
        if (isset($filters['empresa_id'])) {
            $sql .= " AND p.empresa_id = :empresa_id";
            $params['empresa_id'] = $filters['empresa_id'];
        }
        
        if (isset($filters['estado'])) {
            $sql .= " AND p.estado = :estado";
            $params['estado'] = $filters['estado'];
        }
        
        if (isset($filters['search'])) {
            $sql .= " AND p.nombre LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['exclude_cancelled']) && $filters['exclude_cancelled']) {
            $sql .= " AND p.estado != 0";
        }
        
        $sql .= " ORDER BY p.fecha_actualizacion DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Crear proyecto
     */
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO proyectos_proyectos (
                empresa_id, nombre, descripcion, color,
                fecha_inicio, fecha_fin_estimada,
                estado, creado_por
            ) VALUES (
                :empresa_id, :nombre, :descripcion, :color,
                :fecha_inicio, :fecha_fin_estimada,
                :estado, :creado_por
            )
        ");
        
        $stmt->execute([
            'empresa_id' => $data['empresa_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'color' => $data['color'] ?? '#55A5C8',
            'fecha_inicio' => $data['fecha_inicio'] ?? null,
            'fecha_fin_estimada' => $data['fecha_fin_estimada'] ?? null,
            'estado' => $data['estado'] ?? 1,
            'creado_por' => $data['creado_por'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    /**
     * Actualizar proyecto
     */
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'empresa_id', 'nombre', 'descripcion', 'color',
            'fecha_inicio', 'fecha_fin_estimada', 'fecha_fin_real',
            'avance', 'estado'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) return false;
        
        $sql = "UPDATE proyectos_proyectos SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Eliminar proyecto (soft delete)
     */
    public function delete(int $id): bool {
        return $this->update($id, ['estado' => 0]);
    }
    
    /**
     * Calcular y actualizar avance del proyecto
     */
    public function recalcularAvance(int $proyectoId): float {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN estado = 3 THEN 1 ELSE 0 END) as completadas
            FROM proyectos_tareas 
            WHERE proyecto_id = :id AND estado != 0
        ");
        $stmt->execute(['id' => $proyectoId]);
        $result = $stmt->fetch();
        
        $avance = $result['total'] > 0 
            ? round(($result['completadas'] / $result['total']) * 100, 2)
            : 0;
        
        $this->update($proyectoId, ['avance' => $avance]);
        
        return $avance;
    }
    
    /**
     * Obtener proyectos de una empresa
     */
    public function getByEmpresa(int $empresaId): array {
        return $this->getAll(['empresa_id' => $empresaId, 'exclude_cancelled' => true]);
    }
    
    /**
     * Obtener proyectos activos para select
     */
    public function getActiveForSelect(): array {
        $stmt = $this->db->query("
            SELECT p.id, p.nombre, e.nombre as empresa_nombre
            FROM proyectos_proyectos p
            LEFT JOIN proyectos_empresas e ON p.empresa_id = e.id
            WHERE p.estado IN (1, 2)
            ORDER BY p.nombre ASC
        ");
        return $stmt->fetchAll();
    }
}
