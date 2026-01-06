<?php
/**
 * AND PROJECTS APP - Empresa Model
 * Modelo para gestión de empresas
 */

class EmpresaModel {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener empresa por ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM proyectos_empresas WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Obtener todas las empresas
     */
    public function getAll(array $filters = []): array {
        $sql = "SELECT e.*, 
                (SELECT COUNT(*) FROM proyectos_proyectos WHERE empresa_id = e.id) as total_proyectos,
                (SELECT COUNT(*) FROM proyectos_usuarios WHERE empresa_id = e.id) as total_usuarios
                FROM proyectos_empresas e WHERE 1=1";
        $params = [];
        
        if (isset($filters['estado'])) {
            $sql .= " AND e.estado = :estado";
            $params['estado'] = $filters['estado'];
        }
        
        if (isset($filters['search'])) {
            $sql .= " AND (e.nombre LIKE :search OR e.razon_social LIKE :search OR e.nit LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY e.nombre ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Crear empresa
     */
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO proyectos_empresas (
                nombre, razon_social, nit, logo, email, telefono,
                direccion, ciudad, pais, sitio_web, descripcion, color_primario, estado
            ) VALUES (
                :nombre, :razon_social, :nit, :logo, :email, :telefono,
                :direccion, :ciudad, :pais, :sitio_web, :descripcion, :color_primario, :estado
            )
        ");
        
        $stmt->execute([
            'nombre' => $data['nombre'],
            'razon_social' => $data['razon_social'] ?? null,
            'nit' => $data['nit'] ?? null,
            'logo' => $data['logo'] ?? null,
            'email' => $data['email'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'ciudad' => $data['ciudad'] ?? null,
            'pais' => $data['pais'] ?? 'Colombia',
            'sitio_web' => $data['sitio_web'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'color_primario' => $data['color_primario'] ?? '#55A5C8',
            'estado' => $data['estado'] ?? 1
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar empresa
     */
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'nombre', 'razon_social', 'nit', 'logo', 'email', 'telefono',
            'direccion', 'ciudad', 'pais', 'sitio_web', 'descripcion', 'color_primario', 'estado'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) return false;
        
        $sql = "UPDATE proyectos_empresas SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Eliminar empresa (soft delete)
     */
    public function delete(int $id): bool {
        return $this->update($id, ['estado' => 0]);
    }
    
    /**
     * Obtener empresas activas para select
     */
    public function getActiveForSelect(): array {
        $stmt = $this->db->query("SELECT id, nombre FROM proyectos_empresas WHERE estado = 1 ORDER BY nombre ASC");
        return $stmt->fetchAll();
    }
}

