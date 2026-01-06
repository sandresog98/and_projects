<?php
/**
 * AND PROJECTS APP - Modelo de Comentarios
 */

require_once __DIR__ . '/../../../config/paths.php';

class ComentarioModel {
    private PDO $db;
    private string $table = 'proyectos_comentarios';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll(array $filters = []): array {
        $sql = "SELECT c.*, u.nombre as usuario_nombre, u.avatar as usuario_avatar, u.rol as usuario_rol
                FROM {$this->table} c
                LEFT JOIN proyectos_usuarios u ON c.usuario_id = u.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['tipo_entidad'])) {
            $sql .= " AND c.tipo_entidad = :tipo_entidad";
            $params['tipo_entidad'] = $filters['tipo_entidad'];
        }
        
        if (!empty($filters['entidad_id'])) {
            $sql .= " AND c.entidad_id = :entidad_id";
            $params['entidad_id'] = $filters['entidad_id'];
        }
        
        if (!empty($filters['usuario_id'])) {
            $sql .= " AND c.usuario_id = :usuario_id";
            $params['usuario_id'] = $filters['usuario_id'];
        }
        
        $sql .= " ORDER BY c.fecha_creacion DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById(int $id): ?array {
        $sql = "SELECT c.*, u.nombre as usuario_nombre, u.avatar as usuario_avatar
                FROM {$this->table} c
                LEFT JOIN proyectos_usuarios u ON c.usuario_id = u.id
                WHERE c.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    public function create(array $data): int {
        $sql = "INSERT INTO {$this->table} (tipo_entidad, entidad_id, usuario_id, comentario)
                VALUES (:tipo_entidad, :entidad_id, :usuario_id, :comentario)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tipo_entidad' => $data['tipo_entidad'],
            'entidad_id' => $data['entidad_id'],
            'usuario_id' => $data['usuario_id'],
            'comentario' => $data['comentario']
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $sql = "UPDATE {$this->table} SET comentario = :comentario WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'comentario' => $data['comentario']
        ]);
    }
    
    public function delete(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    public function getByEntidad(string $tipoEntidad, int $entidadId): array {
        return $this->getAll([
            'tipo_entidad' => $tipoEntidad,
            'entidad_id' => $entidadId
        ]);
    }
    
    public function getCountByEntidad(string $tipoEntidad, int $entidadId): int {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE tipo_entidad = :tipo_entidad AND entidad_id = :entidad_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tipo_entidad' => $tipoEntidad,
            'entidad_id' => $entidadId
        ]);
        return (int)$stmt->fetchColumn();
    }
    
    public function getRecientes(int $limit = 10, ?int $empresaId = null): array {
        $sql = "SELECT c.*, u.nombre as usuario_nombre, u.avatar as usuario_avatar,
                       CASE c.tipo_entidad
                           WHEN 'proyecto' THEN (SELECT nombre FROM proyectos_proyectos WHERE id = c.entidad_id)
                           WHEN 'tarea' THEN (SELECT nombre FROM proyectos_tareas WHERE id = c.entidad_id)
                           WHEN 'subtarea' THEN (SELECT nombre FROM proyectos_subtareas WHERE id = c.entidad_id)
                       END as entidad_nombre
                FROM {$this->table} c
                LEFT JOIN proyectos_usuarios u ON c.usuario_id = u.id";
        
        $params = [];
        
        if ($empresaId) {
            $sql .= " WHERE (
                (c.tipo_entidad = 'proyecto' AND c.entidad_id IN (SELECT id FROM proyectos_proyectos WHERE empresa_id = :empresa_id))
                OR (c.tipo_entidad = 'tarea' AND c.entidad_id IN (SELECT t.id FROM proyectos_tareas t 
                    INNER JOIN proyectos_proyectos p ON t.proyecto_id = p.id WHERE p.empresa_id = :empresa_id2))
                OR (c.tipo_entidad = 'subtarea' AND c.entidad_id IN (SELECT s.id FROM proyectos_subtareas s 
                    INNER JOIN proyectos_tareas t ON s.tarea_id = t.id 
                    INNER JOIN proyectos_proyectos p ON t.proyecto_id = p.id WHERE p.empresa_id = :empresa_id3))
            )";
            $params['empresa_id'] = $empresaId;
            $params['empresa_id2'] = $empresaId;
            $params['empresa_id3'] = $empresaId;
        }
        
        $sql .= " ORDER BY c.fecha_creacion DESC LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
