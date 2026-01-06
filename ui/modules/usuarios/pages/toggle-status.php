<?php
/**
 * AND PROJECTS APP - Cambiar Estado de Usuario
 */

if (!hasPermission('usuarios', 'editar')) {
    setFlashMessage('error', 'No tiene permisos para esta acción');
    header('Location: ' . uiModuleUrl('usuarios'));
    exit;
}

require_once __DIR__ . '/../../../models/UserModel.php';

$model = new UserModel();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$nuevoEstado = isset($_GET['estado']) ? (int)$_GET['estado'] : -1;

if (!$id || $nuevoEstado < 0 || $nuevoEstado > 1) {
    setFlashMessage('error', 'Parámetros inválidos');
    header('Location: ' . uiModuleUrl('usuarios'));
    exit;
}

$usuario = $model->getById($id);

if (!$usuario) {
    setFlashMessage('error', 'Usuario no encontrado');
    header('Location: ' . uiModuleUrl('usuarios'));
    exit;
}

// No permitir desactivar el propio usuario
if ($id == getCurrentUserId()) {
    setFlashMessage('error', 'No puede cambiar el estado de su propia cuenta');
    header('Location: ' . uiModuleUrl('usuarios', 'ver', ['id' => $id]));
    exit;
}

try {
    $model->update($id, ['estado' => $nuevoEstado]);
    
    $accion = $nuevoEstado == 1 ? 'activado' : 'desactivado';
    setFlashMessage('success', "Usuario $accion correctamente");
} catch (Exception $e) {
    setFlashMessage('error', 'Error al cambiar el estado: ' . $e->getMessage());
}

header('Location: ' . uiModuleUrl('usuarios', 'ver', ['id' => $id]));
exit;

