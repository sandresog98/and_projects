<?php
/**
 * AND PROJECTS APP - Gestión de sesiones para CX (Clientes)
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_name('and_projects_cx');
    session_start();
}

/**
 * Verificar si el cliente está autenticado
 */
function isClientAuthenticated(): bool {
    return isset($_SESSION['client']) && 
           isset($_SESSION['client']['id']) &&
           $_SESSION['client']['rol'] === 'cliente';
}

/**
 * Requerir autenticación de cliente
 */
function requireClientAuth(): void {
    if (!isClientAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Obtener datos del cliente actual
 */
function getCurrentClient(): ?array {
    return $_SESSION['client'] ?? null;
}

/**
 * Obtener ID del cliente actual
 */
function getCurrentClientId(): ?int {
    return $_SESSION['client']['id'] ?? null;
}

/**
 * Obtener empresa del cliente actual
 */
function getCurrentClientEmpresaId(): ?int {
    return $_SESSION['client']['empresa_id'] ?? null;
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
 * Destruir sesión de cliente
 */
function destroyClientSession(): void {
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

