<?php
/**
 * AND PROJECTS APP - User Model
 * Modelo para gestión de usuarios
 */

class UserModel {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener usuario por ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM proyectos_usuarios WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Obtener usuario por email
     */
    public function getByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM proyectos_usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Verificar si email existe
     */
    public function emailExists(string $email, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM proyectos_usuarios WHERE email = :email";
        $params = ['email' => $email];
        
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Crear usuario
     */
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO proyectos_usuarios (
                empresa_id, nombre, email, password, avatar, rol, 
                cargo, telefono, requiere_cambio_clave, estado
            ) VALUES (
                :empresa_id, :nombre, :email, :password, :avatar, :rol,
                :cargo, :telefono, :requiere_cambio_clave, :estado
            )
        ");
        
        $stmt->execute([
            'empresa_id' => $data['empresa_id'] ?? null,
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'password' => $data['password'],
            'avatar' => $data['avatar'] ?? null,
            'rol' => $data['rol'] ?? 'colaborador',
            'cargo' => $data['cargo'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'requiere_cambio_clave' => $data['requiere_cambio_clave'] ?? 0,
            'estado' => $data['estado'] ?? 1
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar usuario
     */
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'empresa_id', 'nombre', 'email', 'password', 'avatar',
            'rol', 'cargo', 'telefono', 'requiere_cambio_clave', 'estado'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) return false;
        
        $sql = "UPDATE proyectos_usuarios SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Actualizar último acceso
     */
    public function updateLastAccess(int $id): void {
        $stmt = $this->db->prepare("UPDATE proyectos_usuarios SET ultimo_acceso = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
    
    /**
     * Obtener todos los usuarios
     */
    public function getAll(array $filters = []): array {
        $sql = "SELECT u.*, e.nombre as empresa_nombre 
                FROM proyectos_usuarios u 
                LEFT JOIN proyectos_empresas e ON u.empresa_id = e.id
                WHERE 1=1";
        $params = [];
        
        if (isset($filters['rol'])) {
            $sql .= " AND u.rol = :rol";
            $params['rol'] = $filters['rol'];
        }
        
        if (isset($filters['empresa_id'])) {
            $sql .= " AND u.empresa_id = :empresa_id";
            $params['empresa_id'] = $filters['empresa_id'];
        }
        
        if (isset($filters['estado'])) {
            $sql .= " AND u.estado = :estado";
            $params['estado'] = $filters['estado'];
        }
        
        if (isset($filters['search'])) {
            $sql .= " AND (u.nombre LIKE :search OR u.email LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY u.nombre ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener colaboradores (admin y colaborador)
     */
    public function getColaboradores(): array {
        return $this->getAll(['rol' => 'admin']) + 
               $this->getAll(['rol' => 'colaborador']);
    }
    
    /**
     * Obtener clientes
     */
    public function getClientes(): array {
        return $this->getAll(['rol' => 'cliente']);
    }
    
    /**
     * Eliminar usuario (soft delete)
     */
    public function delete(int $id): bool {
        return $this->update($id, ['estado' => 0]);
    }
}

