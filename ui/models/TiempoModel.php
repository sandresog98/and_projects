<?php
/**
 * AND PROJECTS APP - Tiempo Model
 * Modelo para gestión y cálculo de tiempos/horas
 */

class TiempoModel {
    private PDO $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registrar tiempo en una subtarea
     */
    public function registrar(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO proyectos_tiempos (
                subtarea_id, usuario_id, fecha, horas, minutos, descripcion
            ) VALUES (
                :subtarea_id, :usuario_id, :fecha, :horas, :minutos, :descripcion
            )
        ");
        
        $stmt->execute([
            'subtarea_id' => $data['subtarea_id'],
            'usuario_id' => $data['usuario_id'],
            'fecha' => $data['fecha'] ?? date('Y-m-d'),
            'horas' => $data['horas'] ?? 0,
            'minutos' => $data['minutos'] ?? 0,
            'descripcion' => $data['descripcion'] ?? null
        ]);
        
        $tiempoId = $this->db->lastInsertId();
        
        // Actualizar horas_reales en la subtarea
        $this->actualizarHorasSubtarea($data['subtarea_id']);
        
        return $tiempoId;
    }
    
    /**
     * Actualizar horas reales de una subtarea
     */
    private function actualizarHorasSubtarea(int $subtareaId): void {
        $stmt = $this->db->prepare("
            UPDATE proyectos_subtareas 
            SET horas_reales = (
                SELECT COALESCE(SUM(horas + minutos/60), 0) 
                FROM proyectos_tiempos 
                WHERE subtarea_id = :id
            )
            WHERE id = :id
        ");
        $stmt->execute(['id' => $subtareaId]);
    }
    
    /**
     * Obtener tiempos de una subtarea
     */
    public function getBySubtarea(int $subtareaId): array {
        $stmt = $this->db->prepare("
            SELECT t.*, u.nombre as usuario_nombre
            FROM proyectos_tiempos t
            LEFT JOIN proyectos_usuarios u ON t.usuario_id = u.id
            WHERE t.subtarea_id = :subtarea_id
            ORDER BY t.fecha DESC, t.fecha_creacion DESC
        ");
        $stmt->execute(['subtarea_id' => $subtareaId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener horas totales de una subtarea
     */
    public function getHorasSubtarea(int $subtareaId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(horas + minutos/60), 0) as horas_reales,
                (SELECT horas_estimadas FROM proyectos_subtareas WHERE id = :id) as horas_estimadas
            FROM proyectos_tiempos
            WHERE subtarea_id = :id
        ");
        $stmt->execute(['id' => $subtareaId]);
        return $stmt->fetch() ?: ['horas_reales' => 0, 'horas_estimadas' => 0];
    }
    
    /**
     * Obtener horas totales de una tarea (suma de sus subtareas)
     */
    public function getHorasTarea(int $tareaId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(s.horas_reales), 0) as horas_reales,
                COALESCE(SUM(s.horas_estimadas), 0) as horas_estimadas
            FROM proyectos_subtareas s
            WHERE s.tarea_id = :tarea_id
        ");
        $stmt->execute(['tarea_id' => $tareaId]);
        return $stmt->fetch() ?: ['horas_reales' => 0, 'horas_estimadas' => 0];
    }
    
    /**
     * Obtener horas totales de un proyecto (suma de todas las subtareas de sus tareas)
     */
    public function getHorasProyecto(int $proyectoId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(s.horas_reales), 0) as horas_reales,
                COALESCE(SUM(s.horas_estimadas), 0) as horas_estimadas
            FROM proyectos_subtareas s
            INNER JOIN proyectos_tareas t ON s.tarea_id = t.id
            WHERE t.proyecto_id = :proyecto_id
        ");
        $stmt->execute(['proyecto_id' => $proyectoId]);
        return $stmt->fetch() ?: ['horas_reales' => 0, 'horas_estimadas' => 0];
    }
    
    /**
     * Obtener horas totales de una empresa (suma de todos sus proyectos)
     */
    public function getHorasEmpresa(int $empresaId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(SUM(s.horas_reales), 0) as horas_reales,
                COALESCE(SUM(s.horas_estimadas), 0) as horas_estimadas
            FROM proyectos_subtareas s
            INNER JOIN proyectos_tareas t ON s.tarea_id = t.id
            INNER JOIN proyectos_proyectos p ON t.proyecto_id = p.id
            WHERE p.empresa_id = :empresa_id
        ");
        $stmt->execute(['empresa_id' => $empresaId]);
        return $stmt->fetch() ?: ['horas_reales' => 0, 'horas_estimadas' => 0];
    }
    
    /**
     * Obtener horas totales generales (todas las empresas/proyectos)
     */
    public function getHorasGeneral(): array {
        $stmt = $this->db->query("
            SELECT 
                COALESCE(SUM(horas_reales), 0) as horas_reales,
                COALESCE(SUM(horas_estimadas), 0) as horas_estimadas
            FROM proyectos_subtareas
        ");
        return $stmt->fetch() ?: ['horas_reales' => 0, 'horas_estimadas' => 0];
    }
    
    /**
     * Obtener resumen de horas por empresa
     */
    public function getResumenPorEmpresa(): array {
        $stmt = $this->db->query("
            SELECT 
                e.id,
                e.nombre,
                e.logo,
                COALESCE(SUM(s.horas_reales), 0) as horas_reales,
                COALESCE(SUM(s.horas_estimadas), 0) as horas_estimadas,
                COUNT(DISTINCT p.id) as total_proyectos
            FROM proyectos_empresas e
            LEFT JOIN proyectos_proyectos p ON e.id = p.empresa_id
            LEFT JOIN proyectos_tareas t ON p.id = t.proyecto_id
            LEFT JOIN proyectos_subtareas s ON t.id = s.tarea_id
            WHERE e.estado = 1
            GROUP BY e.id, e.nombre, e.logo
            ORDER BY horas_reales DESC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener resumen de horas por proyecto
     */
    public function getResumenPorProyecto(?int $empresaId = null): array {
        $sql = "
            SELECT 
                p.id,
                p.nombre,
                p.color,
                e.nombre as empresa_nombre,
                e.logo as empresa_logo,
                COALESCE(SUM(s.horas_reales), 0) as horas_reales,
                COALESCE(SUM(s.horas_estimadas), 0) as horas_estimadas,
                COUNT(DISTINCT t.id) as total_tareas
            FROM proyectos_proyectos p
            LEFT JOIN proyectos_empresas e ON p.empresa_id = e.id
            LEFT JOIN proyectos_tareas t ON p.id = t.proyecto_id
            LEFT JOIN proyectos_subtareas s ON t.id = s.tarea_id
            WHERE p.estado != 5
        ";
        
        $params = [];
        if ($empresaId) {
            $sql .= " AND p.empresa_id = :empresa_id";
            $params['empresa_id'] = $empresaId;
        }
        
        $sql .= " GROUP BY p.id, p.nombre, p.color, e.nombre, e.logo ORDER BY horas_reales DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener resumen de horas por tarea
     */
    public function getResumenPorTarea(?int $proyectoId = null): array {
        $sql = "
            SELECT 
                t.id,
                t.nombre,
                t.estado,
                p.nombre as proyecto_nombre,
                e.nombre as empresa_nombre,
                COALESCE(SUM(s.horas_reales), 0) as horas_reales,
                COALESCE(SUM(s.horas_estimadas), 0) as horas_estimadas,
                COUNT(s.id) as total_subtareas
            FROM proyectos_tareas t
            LEFT JOIN proyectos_proyectos p ON t.proyecto_id = p.id
            LEFT JOIN proyectos_empresas e ON p.empresa_id = e.id
            LEFT JOIN proyectos_subtareas s ON t.id = s.tarea_id
            WHERE t.estado != 5
        ";
        
        $params = [];
        if ($proyectoId) {
            $sql .= " AND t.proyecto_id = :proyecto_id";
            $params['proyecto_id'] = $proyectoId;
        }
        
        $sql .= " GROUP BY t.id, t.nombre, t.estado, p.nombre, e.nombre ORDER BY horas_reales DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Formatear horas para mostrar
     */
    public static function formatHoras(float $horas): string {
        $horasEnteras = floor($horas);
        $minutos = round(($horas - $horasEnteras) * 60);
        
        if ($horasEnteras == 0 && $minutos == 0) {
            return '0h';
        } elseif ($minutos == 0) {
            return $horasEnteras . 'h';
        } elseif ($horasEnteras == 0) {
            return $minutos . 'm';
        }
        
        return $horasEnteras . 'h ' . $minutos . 'm';
    }
    
    /**
     * Calcular porcentaje de horas
     */
    public static function calcularPorcentaje(float $reales, float $estimadas): int {
        if ($estimadas <= 0) return 0;
        return min(100, round(($reales / $estimadas) * 100));
    }
}

