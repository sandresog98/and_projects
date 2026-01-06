<?php
/**
 * AND PROJECTS APP - Router principal CX (Clientes)
 */

ob_start();

require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/utils/session.php';

// Verificar autenticación
requireClientAuth();

$currentClient = getCurrentClient();

// Si requiere cambio de clave, redirigir
if ($currentClient['requiere_cambio_clave']) {
    ob_end_clean();
    header('Location: cambiar-clave.php');
    exit;
}

// Obtener módulo y página solicitados
$module = $_GET['module'] ?? '';
$page = $_GET['page'] ?? 'index';

// Variable para el módulo actual (para el nav)
$currentModule = $module;

// Si no hay módulo, mostrar dashboard
if (empty($module)) {
    $pageTitle = 'Dashboard';
    $pageSubtitle = 'Resumen de tus proyectos';
    require_once __DIR__ . '/views/layouts/header.php';
    require_once __DIR__ . '/pages/dashboard.php';
    require_once __DIR__ . '/views/layouts/footer.php';
    ob_end_flush();
    exit;
}

// Validar que el módulo exista
$modulePath = CX_MODULES . '/' . $module;
if (!is_dir($modulePath)) {
    setFlashMessage('error', 'El módulo solicitado no existe');
    ob_end_clean();
    header('Location: index.php');
    exit;
}

// Determinar el archivo a cargar
$pageFile = $modulePath . '/pages/' . $page . '.php';
if (!file_exists($pageFile)) {
    $pageFile = $modulePath . '/pages/index.php';
    if (!file_exists($pageFile)) {
        setFlashMessage('error', 'La página solicitada no existe');
        ob_end_clean();
        header('Location: index.php');
        exit;
    }
}

// Cargar el header, la página y el footer
require_once __DIR__ . '/views/layouts/header.php';
require_once $pageFile;
require_once __DIR__ . '/views/layouts/footer.php';

ob_end_flush();

