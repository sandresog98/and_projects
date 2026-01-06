<?php
/**
 * AND PROJECTS APP - Configuración de rutas para CX (Clientes)
 */

// Prevenir acceso directo
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/database.php';
}

// Rutas del sistema de archivos
define('CX_ROOT', dirname(__DIR__));
define('CX_MODULES', CX_ROOT . '/modules');
define('CX_VIEWS', CX_ROOT . '/views');
define('CX_CONTROLLERS', CX_ROOT . '/controllers');

// Rutas de la aplicación (si no están definidas)
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(CX_ROOT));
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

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (preg_match('#^(.*?)/cx(?:/|$)#', $scriptName, $matches)) {
    $appBasePath = $matches[1];
} else {
    $appBasePath = dirname(dirname($scriptName));
    if ($appBasePath === '/' || $appBasePath === '\\') {
        $appBasePath = '';
    }
}

define('CX_URL', $protocol . '://' . $host . $appBasePath . '/cx');

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
 * Función helper para generar URLs del CX
 */
function cxUrl(string $path = ''): string {
    return CX_URL . '/' . ltrim($path, '/');
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
 * Función helper para URLs de módulos CX
 */
function cxModuleUrl(string $module, string $page = '', array $params = []): string {
    $url = CX_URL . '/index.php?module=' . $module;
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
        return date($format, strtotime($date));
    }
}

/**
 * Función para formatear fecha y hora
 */
if (!function_exists('formatDateTime')) {
    function formatDateTime(string $datetime, string $format = 'd/m/Y H:i'): string {
        return date($format, strtotime($datetime));
    }
}

