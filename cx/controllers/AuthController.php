<?php
/**
 * AND PROJECTS APP - Controlador de autenticación para CX (Clientes)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../../ui/models/UserModel.php';
require_once __DIR__ . '/../../ui/models/VerificacionModel.php';

class AuthController {
    private PDO $db;
    private UserModel $userModel;
    private VerificacionModel $verificacionModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new UserModel();
        $this->verificacionModel = new VerificacionModel();
    }
    
    /**
     * Intentar login de cliente
     */
    public function login(string $email, string $password): array {
        $response = ['success' => false, 'message' => ''];
        
        if (empty($email) || empty($password)) {
            $response['message'] = 'Por favor complete todos los campos';
            return $response;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'El formato del email no es válido';
            return $response;
        }
        
        try {
            $user = $this->userModel->getByEmail($email);
            
            if (!$user) {
                $response['message'] = 'Credenciales incorrectas';
                return $response;
            }
            
            // Solo permitir clientes en esta interfaz
            if ($user['rol'] !== 'cliente') {
                $response['message'] = 'Acceso no autorizado. Esta interfaz es solo para clientes.';
                return $response;
            }
            
            if ($user['estado'] != 1) {
                $response['message'] = 'Esta cuenta está desactivada';
                return $response;
            }
            
            if (empty($user['password'])) {
                $response['message'] = 'Debe establecer una contraseña primero. Contacte al administrador.';
                return $response;
            }
            
            if (!password_verify($password, $user['password'])) {
                $response['message'] = 'Credenciales incorrectas';
                return $response;
            }
            
            $this->userModel->updateLastAccess($user['id']);
            $this->createSession($user);
            
            $response['success'] = true;
            $response['message'] = 'Login exitoso';
            $response['requiere_cambio_clave'] = (bool)$user['requiere_cambio_clave'];
            
        } catch (PDOException $e) {
            $response['message'] = APP_DEBUG ? $e->getMessage() : 'Error al procesar la solicitud';
        }
        
        return $response;
    }
    
    /**
     * Cambio de contraseña obligatorio en primer login
     */
    public function cambiarClaveInicial(string $claveActual, string $nuevaClave, string $confirmarClave): array {
        $response = ['success' => false, 'message' => ''];
        
        $client = getCurrentClient();
        if (!$client) {
            $response['message'] = 'Sesión no válida';
            return $response;
        }
        
        if (strlen($nuevaClave) < 6) {
            $response['message'] = 'La contraseña debe tener al menos 6 caracteres';
            return $response;
        }
        
        if ($nuevaClave !== $confirmarClave) {
            $response['message'] = 'Las contraseñas no coinciden';
            return $response;
        }
        
        try {
            $user = $this->userModel->getById($client['id']);
            
            if (!$user || !password_verify($claveActual, $user['password'])) {
                $response['message'] = 'La contraseña actual es incorrecta';
                return $response;
            }
            
            $this->userModel->update($client['id'], [
                'password' => password_hash($nuevaClave, PASSWORD_DEFAULT),
                'requiere_cambio_clave' => 0
            ]);
            
            // Actualizar sesión
            $_SESSION['client']['requiere_cambio_clave'] = false;
            
            $response['success'] = true;
            $response['message'] = 'Contraseña actualizada correctamente';
            
        } catch (PDOException $e) {
            $response['message'] = APP_DEBUG ? $e->getMessage() : 'Error al cambiar contraseña';
        }
        
        return $response;
    }
    
    /**
     * Crear sesión de cliente
     */
    private function createSession(array $user): void {
        $_SESSION['client'] = [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'avatar' => $user['avatar'],
            'rol' => $user['rol'],
            'empresa_id' => $user['empresa_id'],
            'requiere_cambio_clave' => (bool)$user['requiere_cambio_clave']
        ];
    }
    
    /**
     * Cerrar sesión
     */
    public function logout(): void {
        destroyClientSession();
    }
    
    /**
     * Verificar si hay sesión activa
     */
    public function checkSession(): bool {
        return isClientAuthenticated();
    }
}

