<?php
/**
 * AND PROJECTS APP - Editar Usuario
 */

if (!hasPermission('usuarios', 'editar')) {
    setFlashMessage('error', 'No tiene permisos para editar usuarios');
    header('Location: ' . uiModuleUrl('usuarios'));
    exit;
}

require_once __DIR__ . '/../../../models/UserModel.php';
require_once __DIR__ . '/../../empresas/models/EmpresaModel.php';

$model = new UserModel();
$empresaModel = new EmpresaModel();

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

// Solo admins pueden editar otros admins
if ($usuario['rol'] === 'admin' && !isAdmin()) {
    setFlashMessage('error', 'No tiene permisos para editar administradores');
    header('Location: ' . uiModuleUrl('usuarios'));
    exit;
}

$pageTitle = 'Editar: ' . $usuario['nombre'];
$pageSubtitle = 'Modificar información del usuario';

$empresas = $empresaModel->getActiveForSelect();

$errors = [];
$formData = [
    'nombre' => $usuario['nombre'],
    'email' => $usuario['email'],
    'rol' => $usuario['rol'],
    'empresa_id' => $usuario['empresa_id'] ?? '',
    'cargo' => $usuario['cargo'] ?? '',
    'telefono' => $usuario['telefono'] ?? '',
    'estado' => $usuario['estado']
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_merge($formData, $_POST);
    $formData['estado'] = isset($_POST['estado']) ? 1 : 0;
    
    // Validaciones
    if (empty($formData['nombre'])) {
        $errors[] = 'El nombre es obligatorio';
    }
    
    if (empty($formData['email'])) {
        $errors[] = 'El email es obligatorio';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del email no es válido';
    } elseif ($model->emailExists($formData['email'], $id)) {
        $errors[] = 'Este email ya está registrado por otro usuario';
    }
    
    if ($formData['rol'] === 'cliente' && empty($formData['empresa_id'])) {
        $errors[] = 'Los clientes deben tener una empresa asignada';
    }
    
    // No permitir quitar rol admin al propio usuario
    if ($id == getCurrentUserId() && $usuario['rol'] === 'admin' && $formData['rol'] !== 'admin') {
        $errors[] = 'No puede quitarse el rol de administrador a sí mismo';
    }
    
    // Si no hay errores, guardar
    if (empty($errors)) {
        try {
            $updateData = [
                'nombre' => $formData['nombre'],
                'email' => $formData['email'],
                'rol' => $formData['rol'],
                'empresa_id' => $formData['empresa_id'] ?: null,
                'cargo' => $formData['cargo'] ?: null,
                'telefono' => $formData['telefono'] ?: null,
                'estado' => $formData['estado']
            ];
            
            // Si se cambió a cliente, marcar que requiere cambio de clave
            if ($formData['rol'] === 'cliente' && $usuario['rol'] !== 'cliente') {
                $updateData['requiere_cambio_clave'] = 1;
            }
            
            $model->update($id, $updateData);
            
            setFlashMessage('success', 'Usuario actualizado correctamente');
            header('Location: ' . uiModuleUrl('usuarios', 'ver', ['id' => $id]));
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Error al actualizar el usuario: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-person-gear me-2"></i>Editar Usuario</h6>
                <a href="<?= uiModuleUrl('usuarios', 'ver', ['id' => $id]) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row g-3">
                        <!-- Información básica -->
                        <div class="col-12">
                            <h6 class="text-muted mb-3"><i class="bi bi-person me-2"></i>Información del Usuario</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($formData['nombre']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($formData['email']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Rol *</label>
                            <select name="rol" id="rol" class="form-select" required <?= $id == getCurrentUserId() && $usuario['rol'] === 'admin' ? 'disabled' : '' ?>>
                                <option value="colaborador" <?= $formData['rol'] === 'colaborador' ? 'selected' : '' ?>>Colaborador</option>
                                <?php if (isAdmin()): ?>
                                <option value="admin" <?= $formData['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                <?php endif; ?>
                                <option value="cliente" <?= $formData['rol'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                            </select>
                            <?php if ($id == getCurrentUserId() && $usuario['rol'] === 'admin'): ?>
                            <input type="hidden" name="rol" value="admin">
                            <small class="text-muted">No puede cambiar su propio rol de administrador</small>
                            <?php else: ?>
                            <small class="text-muted">Los clientes acceden a la interfaz de visualización</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Empresa</label>
                            <select name="empresa_id" id="empresa_id" class="form-select">
                                <option value="">Sin empresa asignada</option>
                                <?php foreach ($empresas as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $formData['empresa_id'] == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted" id="empresa_help">Obligatorio para clientes</small>
                        </div>
                        
                        <!-- Información adicional -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-2"></i>Información Adicional</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Cargo</label>
                            <input type="text" name="cargo" class="form-control" value="<?= htmlspecialchars($formData['cargo']) ?>" placeholder="Ej: Gerente de Proyecto">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control" value="<?= htmlspecialchars($formData['telefono']) ?>" placeholder="+57 300 123 4567">
                        </div>
                        
                        <!-- Estado -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-toggle-on me-2"></i>Estado de la Cuenta</h6>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="estado" name="estado" 
                                       <?= $formData['estado'] ? 'checked' : '' ?>
                                       <?= $id == getCurrentUserId() ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="estado">
                                    Usuario activo
                                </label>
                            </div>
                            <?php if ($id == getCurrentUserId()): ?>
                            <input type="hidden" name="estado" value="1">
                            <small class="text-muted">No puede desactivar su propia cuenta</small>
                            <?php else: ?>
                            <small class="text-muted">Los usuarios inactivos no pueden acceder al sistema</small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 mt-4">
                            <hr class="my-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <?php if ($id != getCurrentUserId()): ?>
                                    <button type="button" class="btn btn-outline-warning" onclick="resetPassword()">
                                        <i class="bi bi-key me-2"></i>Restablecer Contraseña
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?= uiModuleUrl('usuarios', 'ver', ['id' => $id]) ?>" class="btn btn-outline-secondary">Cancelar</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar empresa según rol
document.getElementById('rol')?.addEventListener('change', function() {
    const empresaField = document.getElementById('empresa_id');
    const empresaHelp = document.getElementById('empresa_help');
    
    if (this.value === 'cliente') {
        empresaField.required = true;
        empresaHelp.classList.add('text-danger');
        empresaHelp.textContent = 'Obligatorio para clientes';
    } else {
        empresaField.required = false;
        empresaHelp.classList.remove('text-danger');
        empresaHelp.textContent = 'Opcional';
    }
});

// Inicializar según rol actual
document.addEventListener('DOMContentLoaded', function() {
    const rolSelect = document.getElementById('rol');
    if (rolSelect) {
        rolSelect.dispatchEvent(new Event('change'));
    }
});

function resetPassword() {
    Swal.fire({
        title: '¿Restablecer contraseña?',
        text: 'Se generará una nueva contraseña temporal para el usuario',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f0b429',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, restablecer',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= uiModuleUrl('usuarios', 'reset-password', ['id' => $id]) ?>';
        }
    });
}
</script>

