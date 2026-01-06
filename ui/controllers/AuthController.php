<?php
/**
 * AND PROJECTS APP - UI Auth Controller
 * Controlador de autenticación para colaboradores
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../utils/session.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    private PDO $db;
    private UserModel $userModel;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new UserModel();
    }
    
    /**
     * Intentar login de usuario
     */
    public function login(string $email, string $password): array {
        $response = ['success' => false, 'message' => ''];
        
        // Validar campos
        if (empty($email) || empty($password)) {
            $response['message'] = 'Por favor complete todos los campos';
            return $response;
        }
        
        // Validar formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'El formato del email no es válido';
            return $response;
        }
        
        try {
            // Buscar usuario
            $user = $this->userModel->getByEmail($email);
            
            // Verificar si existe
            if (!$user) {
                $response['message'] = 'Credenciales incorrectas';
                return $response;
            }
            
            // Verificar que sea colaborador o admin
            if (!in_array($user['rol'], ['admin', 'colaborador'])) {
                $response['message'] = 'No tiene acceso a esta interfaz';
                return $response;
            }
            
            // Verificar estado
            if ($user['estado'] != 1) {
                $response['message'] = 'Esta cuenta está desactivada';
                return $response;
            }
            
            // Verificar contraseña
            if (!password_verify($password, $user['password'])) {
                $response['message'] = 'Credenciales incorrectas';
                return $response;
            }
            
            // Actualizar último acceso
            $this->userModel->updateLastAccess($user['id']);
            
            // Crear sesión
            $this->createSession($user);
            
            $response['success'] = true;
            $response['message'] = 'Login exitoso';
            
        } catch (PDOException $e) {
            $response['message'] = APP_DEBUG ? $e->getMessage() : 'Error al procesar la solicitud';
        }
        
        return $response;
    }
    
    /**
     * Crear sesión de usuario
     */
    private function createSession(array $user): void {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'avatar' => $user['avatar'],
            'rol' => $user['rol'],
            'empresa_id' => $user['empresa_id']
        ];
    }
    
    /**
     * Cerrar sesión
     */
    public function logout(): void {
        destroyUserSession();
    }
    
    /**
     * Verificar si hay sesión activa
     */
    public function checkSession(): bool {
        return isUserAuthenticated();
    }
    
    /**
     * Cambiar contraseña
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array {
        $response = ['success' => false, 'message' => ''];
        
        // Validar nueva contraseña
        if (strlen($newPassword) < 6) {
            $response['message'] = 'La contraseña debe tener al menos 6 caracteres';
            return $response;
        }
        
        if ($newPassword !== $confirmPassword) {
            $response['message'] = 'Las contraseñas no coinciden';
            return $response;
        }
        
        try {
            // Obtener usuario
            $user = $this->userModel->getById($userId);
            
            if (!$user) {
                $response['message'] = 'Usuario no encontrado';
                return $response;
            }
            
            // Verificar contraseña actual
            if (!password_verify($currentPassword, $user['password'])) {
                $response['message'] = 'La contraseña actual es incorrecta';
                return $response;
            }
            
            // Actualizar contraseña
            $this->userModel->update($userId, [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);
            
            $response['success'] = true;
            $response['message'] = 'Contraseña actualizada correctamente';
            
        } catch (Exception $e) {
            $response['message'] = APP_DEBUG ? $e->getMessage() : 'Error al cambiar la contraseña';
        }
        
        return $response;
    }
}

