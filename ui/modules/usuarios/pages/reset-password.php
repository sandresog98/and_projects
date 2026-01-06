<?php
/**
 * AND PROJECTS APP - Restablecer Contraseña de Usuario
 */

if (!hasPermission('usuarios', 'editar')) {
    setFlashMessage('error', 'No tiene permisos para esta acción');
    header('Location: ' . uiModuleUrl('usuarios'));
    exit;
}

require_once __DIR__ . '/../../../models/UserModel.php';

$model = new UserModel();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setFlashMessage('error', 'Usuario no especificado');
    header('Location: ' . uiModuleUrl('usuarios'));
    exit;
}

$usuario = $model->getById($id);

if (!$usuario) {
    setFlashMessage('error', 'Usuario no encontrado');
    header('Location: ' . uiModuleUrl('usuarios'));
    exit;
}

// No permitir restablecer la propia contraseña desde aquí
if ($id == getCurrentUserId()) {
    setFlashMessage('error', 'No puede restablecer su propia contraseña desde aquí');
    header('Location: ' . uiModuleUrl('usuarios', 'ver', ['id' => $id]));
    exit;
}

// Generar nueva contraseña temporal
$passwordTemp = bin2hex(random_bytes(4)); // 8 caracteres aleatorios

try {
    $model->update($id, [
        'password' => password_hash($passwordTemp, PASSWORD_DEFAULT),
        'requiere_cambio_clave' => 1
    ]);
    
    setFlashMessage('success', "Contraseña restablecida correctamente. Nueva contraseña temporal: <strong>$passwordTemp</strong> (comunicar al usuario)");
} catch (Exception $e) {
    setFlashMessage('error', 'Error al restablecer la contraseña: ' . $e->getMessage());
}

header('Location: ' . uiModuleUrl('usuarios', 'ver', ['id' => $id]));
exit;

