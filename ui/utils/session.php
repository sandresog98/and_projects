<?php
/**
 * AND PROJECTS APP - Session Management (UI)
 * Manejo de sesiones para la interfaz de colaboradores
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_name('and_projects_ui');
    session_start();
}

/**
 * Verificar si el usuario está autenticado
 */
function isUserAuthenticated(): bool {
    return isset($_SESSION['user']) && 
           isset($_SESSION['user']['id']) &&
           in_array($_SESSION['user']['rol'], ['admin', 'colaborador']);
}

/**
 * Requerir autenticación de usuario
 */
function requireUserAuth(): void {
    if (!isUserAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Obtener datos del usuario actual
 */
function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Obtener ID del usuario actual
 */
function getCurrentUserId(): ?int {
    return $_SESSION['user']['id'] ?? null;
}

/**
 * Verificar si el usuario es administrador
 */
function isAdmin(): bool {
    return isset($_SESSION['user']['rol']) && $_SESSION['user']['rol'] === 'admin';
}

/**
 * Verificar permiso de módulo
 */
function hasPermission(string $module, string $action = 'ver'): bool {
    $rolesFile = dirname(dirname(__DIR__)) . '/roles.json';
    if (!file_exists($rolesFile)) return false;
    
    $roles = json_decode(file_get_contents($rolesFile), true);
    $userRole = $_SESSION['user']['rol'] ?? '';
    
    if (!isset($roles[$userRole])) return false;
    if (!isset($roles[$userRole]['modulos'][$module])) return false;
    
    return $roles[$userRole]['modulos'][$module][$action] ?? false;
}

/**
 * Establecer mensaje flash
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obtener y limpiar mensaje flash
 */
function getFlashMessage(): ?array {
    $message = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return $message;
}

/**
 * Destruir sesión de usuario
 */
function destroyUserSession(): void {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

