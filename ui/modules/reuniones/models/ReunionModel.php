<?php
/**
 * AND PROJECTS APP - Modelo de Reuniones
 */

require_once __DIR__ . '/../../../config/paths.php';

class ReunionModel {
    private PDO $db;
    private string $table = 'proyectos_reuniones';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll(array $filters = []): array {
        $sql = "SELECT r.*, 
                       p.nombre as proyecto_nombre,
                       e.nombre as empresa_nombre,
                       e.logo as empresa_logo,
                       u.nombre as creador_nombre
                FROM {$this->table} r
                LEFT JOIN proyectos_proyectos p ON r.proyecto_id = p.id
                LEFT JOIN proyectos_empresas e ON r.empresa_id = e.id
                LEFT JOIN proyectos_usuarios u ON r.creado_por = u.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (r.titulo LIKE :search OR r.descripcion LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['proyecto_id'])) {
            $sql .= " AND r.proyecto_id = :proyecto_id";
            $params['proyecto_id'] = $filters['proyecto_id'];
        }
        
        if (!empty($filters['empresa_id'])) {
            $sql .= " AND r.empresa_id = :empresa_id";
            $params['empresa_id'] = $filters['empresa_id'];
        }
        
        if (!empty($filters['fecha_desde'])) {
            $sql .= " AND r.fecha >= :fecha_desde";
            $params['fecha_desde'] = $filters['fecha_desde'];
        }
        
        if (!empty($filters['fecha_hasta'])) {
            $sql .= " AND r.fecha <= :fecha_hasta";
            $params['fecha_hasta'] = $filters['fecha_hasta'];
        }
        
        if (!empty($filters['tipo'])) {
            $sql .= " AND r.tipo = :tipo";
            $params['tipo'] = $filters['tipo'];
        }
        
        $sql .= " ORDER BY r.fecha ASC, r.hora_inicio ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById(int $id): ?array {
        $sql = "SELECT r.*, 
                       p.nombre as proyecto_nombre,
                       e.nombre as empresa_nombre,
                       e.logo as empresa_logo,
                       u.nombre as creador_nombre
                FROM {$this->table} r
                LEFT JOIN proyectos_proyectos p ON r.proyecto_id = p.id
                LEFT JOIN proyectos_empresas e ON r.empresa_id = e.id
                LEFT JOIN proyectos_usuarios u ON r.creado_por = u.id
                WHERE r.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
    
    public function create(array $data): int {
        $sql = "INSERT INTO {$this->table} 
                (proyecto_id, empresa_id, titulo, descripcion, fecha, hora_inicio, 
                 duracion_minutos, tipo, ubicacion, finalidad, creado_por)
                VALUES 
                (:proyecto_id, :empresa_id, :titulo, :descripcion, :fecha, :hora_inicio,
                 :duracion_minutos, :tipo, :ubicacion, :finalidad, :creado_por)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'proyecto_id' => $data['proyecto_id'] ?? null,
            'empresa_id' => $data['empresa_id'] ?? null,
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha' => $data['fecha'],
            'hora_inicio' => $data['hora_inicio'],
            'duracion_minutos' => $data['duracion_minutos'] ?? 60,
            'tipo' => $data['tipo'] ?? 'presencial',
            'ubicacion' => $data['ubicacion'] ?? null,
            'finalidad' => $data['finalidad'] ?? null,
            'creado_por' => $data['creada_por']
        ]);
        
        return (int)$this->db->lastInsertId();
    }
    
    public function update(int $id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = [
            'proyecto_id', 'empresa_id', 'titulo', 'descripcion', 'fecha', 'hora_inicio',
            'duracion_minutos', 'tipo', 'ubicacion', 'enlace_virtual', 'finalidad', 'asistentes', 'insights'
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
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    public function getHoy(): array {
        $sql = "SELECT r.*, 
                       p.nombre as proyecto_nombre,
                       e.nombre as empresa_nombre,
                       e.logo as empresa_logo
                FROM {$this->table} r
                LEFT JOIN proyectos_proyectos p ON r.proyecto_id = p.id
                LEFT JOIN proyectos_empresas e ON r.empresa_id = e.id
                WHERE r.fecha = CURRENT_DATE()
                ORDER BY r.hora_inicio ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getProximas(int $dias = 7): array {
        $sql = "SELECT r.*, 
                       p.nombre as proyecto_nombre,
                       e.nombre as empresa_nombre,
                       e.logo as empresa_logo
                FROM {$this->table} r
                LEFT JOIN proyectos_proyectos p ON r.proyecto_id = p.id
                LEFT JOIN proyectos_empresas e ON r.empresa_id = e.id
                WHERE r.fecha BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL :dias DAY)
                ORDER BY r.fecha ASC, r.hora_inicio ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['dias' => $dias]);
        return $stmt->fetchAll();
    }
    
    public function getParaCalendario(string $fechaDesde, string $fechaHasta): array {
        $reuniones = $this->getAll([
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta
        ]);
        
        $eventos = [];
        foreach ($reuniones as $r) {
            $color = match($r['tipo']) {
                'virtual' => '#17a2b8',
                'hibrida' => '#ffc107',
                default => '#55A5C8'
            };
            
            $eventos[] = [
                'id' => $r['id'],
                'title' => $r['titulo'],
                'start' => $r['fecha'] . 'T' . $r['hora_inicio'],
                'end' => $r['fecha'] . 'T' . date('H:i:s', strtotime($r['hora_inicio']) + ($r['duracion_minutos'] * 60)),
                'color' => $color,
                'extendedProps' => [
                    'tipo' => $r['tipo'],
                    'ubicacion' => $r['ubicacion'],
                    'empresa' => $r['empresa_nombre'],
                    'proyecto' => $r['proyecto_nombre']
                ]
            ];
        }
        
        return $eventos;
    }
    
    // ====== PARTICIPANTES ======
    
    public function agregarParticipante(int $reunionId, int $usuarioId): bool {
        $sql = "INSERT IGNORE INTO proyectos_reunion_participantes (reunion_id, usuario_id) VALUES (:reunion_id, :usuario_id)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['reunion_id' => $reunionId, 'usuario_id' => $usuarioId]);
    }
    
    public function quitarParticipante(int $reunionId, int $usuarioId): bool {
        $sql = "DELETE FROM proyectos_reunion_participantes WHERE reunion_id = :reunion_id AND usuario_id = :usuario_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['reunion_id' => $reunionId, 'usuario_id' => $usuarioId]);
    }
    
    public function getParticipantes(int $reunionId): array {
        $sql = "SELECT u.* FROM proyectos_usuarios u
                INNER JOIN proyectos_reunion_participantes rp ON u.id = rp.usuario_id
                WHERE rp.reunion_id = :reunion_id
                ORDER BY u.nombre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['reunion_id' => $reunionId]);
        return $stmt->fetchAll();
    }
    
    public function sincronizarParticipantes(int $reunionId, array $usuarioIds): void {
        // Eliminar todos
        $sql = "DELETE FROM proyectos_reunion_participantes WHERE reunion_id = :reunion_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['reunion_id' => $reunionId]);
        
        // Agregar nuevos
        foreach ($usuarioIds as $usuarioId) {
            $this->agregarParticipante($reunionId, (int)$usuarioId);
        }
    }
    
    // ====== ESTADÍSTICAS ======
    
    public function getEstadisticasMes(int $mes, int $anio): array {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(duracion_minutos) as minutos_totales,
                    SUM(CASE WHEN tipo = 'presencial' THEN 1 ELSE 0 END) as presenciales,
                    SUM(CASE WHEN tipo = 'virtual' THEN 1 ELSE 0 END) as virtuales,
                    SUM(CASE WHEN tipo = 'hibrida' THEN 1 ELSE 0 END) as hibridas
                FROM {$this->table}
                WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mes' => $mes, 'anio' => $anio]);
        return $stmt->fetch();
    }
}
