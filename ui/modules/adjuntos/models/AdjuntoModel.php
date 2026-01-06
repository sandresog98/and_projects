<?php
/**
 * AND PROJECTS APP - Modelo de Adjuntos
 */

require_once __DIR__ . '/../../../config/paths.php';

class AdjuntoModel {
    private PDO $db;
    private string $table = 'proyectos_adjuntos';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll(array $filters = []): array {
        $sql = "SELECT a.*, u.nombre as usuario_nombre
                FROM {$this->table} a
                LEFT JOIN proyectos_usuarios u ON a.usuario_id = u.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['tipo_entidad'])) {
            $sql .= " AND a.tipo_entidad = :tipo_entidad";
            $params['tipo_entidad'] = $filters['tipo_entidad'];
        }
        
        if (!empty($filters['entidad_id'])) {
            $sql .= " AND a.entidad_id = :entidad_id";
            $params['entidad_id'] = $filters['entidad_id'];
        }
        
        if (!empty($filters['usuario_id'])) {
            $sql .= " AND a.usuario_id = :usuario_id";
            $params['usuario_id'] = $filters['usuario_id'];
        }
        
        $sql .= " ORDER BY a.fecha_creacion DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById(int $id): ?array {
        $sql = "SELECT a.*, u.nombre as usuario_nombre
                FROM {$this->table} a
                LEFT JOIN proyectos_usuarios u ON a.usuario_id = u.id
                WHERE a.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    public function create(array $data): int {
        $sql = "INSERT INTO {$this->table} 
                (tipo_entidad, entidad_id, usuario_id, nombre_original, nombre_servidor, ruta, tipo_mime, tamano, descripcion)
                VALUES 
                (:tipo_entidad, :entidad_id, :usuario_id, :nombre_original, :nombre_servidor, :ruta, :tipo_mime, :tamano, :descripcion)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tipo_entidad' => $data['tipo_entidad'],
            'entidad_id' => $data['entidad_id'],
            'usuario_id' => $data['usuario_id'],
            'nombre_original' => $data['nombre_original'],
            'nombre_servidor' => $data['nombre_servidor'],
            'ruta' => $data['ruta'],
            'tipo_mime' => $data['tipo_mime'],
            'tamano' => $data['tamano'],
            'descripcion' => $data['descripcion'] ?? null
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        
        if (isset($data['descripcion'])) {
            $fields[] = "descripcion = :descripcion";
            $params['descripcion'] = $data['descripcion'];
        }
        
        if (empty($fields)) return false;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete(int $id): bool {
        // Obtener el adjunto para eliminar el archivo físico
        $adjunto = $this->getById($id);
        if (!$adjunto) return false;
        
        // Eliminar archivo físico
        $rutaCompleta = UPLOADS_PATH . '/' . $adjunto['ruta'];
        if (file_exists($rutaCompleta)) {
            unlink($rutaCompleta);
        }
        
        // Eliminar registro
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
    
    /**
     * Subir un archivo adjunto
     */
    public function subirArchivo(array $file, string $tipoEntidad, int $entidadId, int $usuarioId, ?string $descripcion = null): array {
        $response = ['success' => false, 'message' => '', 'id' => null];
        
        // Validar archivo
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $response['message'] = 'Error al subir el archivo';
            return $response;
        }
        
        // Validar tamaño según tipo
        $maxSize = $this->getMaxSize($file['type']);
        if ($file['size'] > $maxSize) {
            $response['message'] = 'El archivo excede el tamaño máximo permitido';
            return $response;
        }
        
        // Validar tipo de archivo
        if (!$this->isAllowedType($file['type'])) {
            $response['message'] = 'Tipo de archivo no permitido';
            return $response;
        }
        
        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nombreServidor = $this->generarNombreUnico($tipoEntidad, $entidadId, $extension);
        
        // Crear directorio si no existe
        $directorio = "adjuntos/{$tipoEntidad}s/{$entidadId}";
        $rutaDirectorio = UPLOADS_PATH . '/' . $directorio;
        if (!is_dir($rutaDirectorio)) {
            mkdir($rutaDirectorio, 0755, true);
        }
        
        // Mover archivo
        $rutaCompleta = $rutaDirectorio . '/' . $nombreServidor;
        if (!move_uploaded_file($file['tmp_name'], $rutaCompleta)) {
            $response['message'] = 'Error al guardar el archivo';
            return $response;
        }
        
        // Guardar en base de datos
        try {
            $id = $this->create([
                'tipo_entidad' => $tipoEntidad,
                'entidad_id' => $entidadId,
                'usuario_id' => $usuarioId,
                'nombre_original' => $file['name'],
                'nombre_servidor' => $nombreServidor,
                'ruta' => $directorio . '/' . $nombreServidor,
                'tipo_mime' => $file['type'],
                'tamano' => $file['size'],
                'descripcion' => $descripcion
            ]);
            
            $response['success'] = true;
            $response['message'] = 'Archivo subido correctamente';
            $response['id'] = $id;
            
        } catch (Exception $e) {
            // Eliminar archivo si falla la BD
            unlink($rutaCompleta);
            $response['message'] = 'Error al registrar el archivo';
        }
        
        return $response;
    }
    
    /**
     * Generar nombre único para el archivo
     */
    private function generarNombreUnico(string $tipo, int $id, string $extension): string {
        $prefijo = substr($tipo, 0, 3); // pro, tar, sub
        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        return "{$prefijo}_{$id}_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Obtener tamaño máximo según tipo de archivo
     */
    private function getMaxSize(string $mimeType): int {
        if (str_starts_with($mimeType, 'image/')) {
            return UPLOAD_MAX_IMAGE_SIZE; // 5MB
        }
        if ($mimeType === 'application/pdf') {
            return UPLOAD_MAX_PDF_SIZE; // 10MB
        }
        if (str_starts_with($mimeType, 'video/')) {
            return UPLOAD_MAX_VIDEO_SIZE; // 100MB
        }
        return 10 * 1024 * 1024; // 10MB por defecto
    }
    
    /**
     * Verificar si el tipo de archivo está permitido
     */
    private function isAllowedType(string $mimeType): bool {
        $allowedTypes = [
            // Imágenes
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            // Documentos
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            // Videos
            'video/mp4', 'video/webm', 'video/quicktime',
            // Comprimidos
            'application/zip', 'application/x-rar-compressed'
        ];
        
        return in_array($mimeType, $allowedTypes);
    }
    
    /**
     * Obtener icono según tipo de archivo
     */
    public static function getIcono(string $mimeType): string {
        if (str_starts_with($mimeType, 'image/')) return 'bi-file-image';
        if ($mimeType === 'application/pdf') return 'bi-file-pdf';
        if (str_contains($mimeType, 'word')) return 'bi-file-word';
        if (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) return 'bi-file-excel';
        if (str_starts_with($mimeType, 'video/')) return 'bi-file-play';
        if (str_contains($mimeType, 'zip') || str_contains($mimeType, 'rar')) return 'bi-file-zip';
        return 'bi-file-earmark';
    }
    
    /**
     * Formatear tamaño de archivo
     */
    public static function formatTamano(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Estadísticas de adjuntos
     */
    public function getEstadisticas(): array {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(tamano) as tamano_total,
                    SUM(CASE WHEN tipo_mime LIKE 'image/%' THEN 1 ELSE 0 END) as imagenes,
                    SUM(CASE WHEN tipo_mime = 'application/pdf' THEN 1 ELSE 0 END) as pdfs,
                    SUM(CASE WHEN tipo_mime LIKE 'video/%' THEN 1 ELSE 0 END) as videos
                FROM {$this->table}";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
}
