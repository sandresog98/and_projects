<?php
/**
 * AND PROJECTS APP - UI Paths Configuration
 * Configuración de rutas y URLs para la interfaz de colaboradores
 */

// Prevenir acceso directo
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/database.php';
}

// Rutas del sistema de archivos
define('UI_ROOT', dirname(__DIR__));
define('UI_MODULES', UI_ROOT . '/modules');
define('UI_VIEWS', UI_ROOT . '/views');
define('UI_CONTROLLERS', UI_ROOT . '/controllers');

// Rutas de la aplicación (si no están definidas)
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(UI_ROOT));
}
if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', APP_ROOT . '/assets');
}
if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', APP_ROOT . '/uploads');
}

// URLs base - Detección automática
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Detectar la ruta base automáticamente desde SCRIPT_NAME
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
// Buscar /ui/ en la ruta para extraer la base
if (preg_match('#^(.*?)/ui(?:/|$)#', $scriptName, $matches)) {
    $appBasePath = $matches[1];
} else {
    // Fallback
    $appBasePath = dirname(dirname($scriptName));
    if ($appBasePath === '/' || $appBasePath === '\\') {
        $appBasePath = '';
    }
}

define('UI_URL', $protocol . '://' . $host . $appBasePath . '/ui');

if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', $protocol . '://' . $host . $appBasePath);
}
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', APP_BASE_URL . '/assets');
}
if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', APP_BASE_URL . '/uploads');
}

/**
 * Función helper para generar URLs del UI
 */
function uiUrl(string $path = ''): string {
    return UI_URL . '/' . ltrim($path, '/');
}

/**
 * Función helper para assets
 */
if (!function_exists('assetUrl')) {
    function assetUrl(string $path = ''): string {
        return ASSETS_URL . '/' . ltrim($path, '/');
    }
}

/**
 * Función helper para URLs de módulos
 */
function uiModuleUrl(string $module, string $page = '', array $params = []): string {
    $url = UI_URL . '/index.php?module=' . $module;
    if ($page) {
        $url .= '&page=' . $page;
    }
    foreach ($params as $key => $value) {
        $url .= '&' . urlencode($key) . '=' . urlencode($value);
    }
    return $url;
}

/**
 * Función para formatear fecha
 */
if (!function_exists('formatDate')) {
    function formatDate(string $date, string $format = 'd/m/Y'): string {
        if (empty($date)) return '-';
        return date($format, strtotime($date));
    }
}

/**
 * Función para formatear fecha y hora
 */
if (!function_exists('formatDateTime')) {
    function formatDateTime(string $datetime, string $format = 'd/m/Y H:i'): string {
        if (empty($datetime)) return '-';
        return date($format, strtotime($datetime));
    }
}

/**
 * Función para formatear duración en minutos a horas y minutos
 */
if (!function_exists('formatDuration')) {
    function formatDuration(int $minutes): string {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours . 'h ' . ($mins > 0 ? $mins . 'm' : '');
    }
}

/**
 * Obtener color de estado
 */
if (!function_exists('getStatusColor')) {
    function getStatusColor(int $status): string {
        $colors = [
            0 => 'danger',      // Cancelado
            1 => 'secondary',   // Pendiente
            2 => 'primary',     // En progreso
            3 => 'success',     // Finalizado
            4 => 'warning'      // Pausado/Bloqueado
        ];
        return $colors[$status] ?? 'secondary';
    }
}

/**
 * Obtener texto de estado
 */
if (!function_exists('getStatusText')) {
    function getStatusText(int $status): string {
        $texts = [
            0 => 'Cancelado',
            1 => 'Pendiente',
            2 => 'En Progreso',
            3 => 'Finalizado',
            4 => 'Pausado'
        ];
        return $texts[$status] ?? 'Desconocido';
    }
}

/**
 * Obtener color de prioridad
 */
if (!function_exists('getPriorityColor')) {
    function getPriorityColor(int $priority): string {
        $colors = [
            1 => 'success',   // Baja
            2 => 'info',      // Media
            3 => 'warning',   // Alta
            4 => 'danger'     // Urgente
        ];
        return $colors[$priority] ?? 'secondary';
    }
}

/**
 * Obtener texto de prioridad
 */
if (!function_exists('getPriorityText')) {
    function getPriorityText(int $priority): string {
        $texts = [
            1 => 'Baja',
            2 => 'Media',
            3 => 'Alta',
            4 => 'Urgente'
        ];
        return $texts[$priority] ?? 'Media';
    }
}

