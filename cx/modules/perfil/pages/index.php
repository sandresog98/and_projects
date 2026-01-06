<?php
/**
 * AND PROJECTS APP - Perfil del Cliente (CX)
 */

$pageTitle = 'Mi Perfil';
$pageSubtitle = 'Información de tu cuenta';

require_once __DIR__ . '/../../../../ui/models/UserModel.php';
require_once __DIR__ . '/../../../../ui/modules/empresas/models/EmpresaModel.php';

$model = new UserModel();
$empresaModel = new EmpresaModel();

$clientId = getCurrentClientId();
$cliente = $model->getById($clientId);
$empresa = $cliente['empresa_id'] ? $empresaModel->getById($cliente['empresa_id']) : null;

$error = '';
$success = '';

// Procesar actualización de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_clave'])) {
    $claveActual = $_POST['clave_actual'] ?? '';
    $nuevaClave = $_POST['nueva_clave'] ?? '';
    $confirmarClave = $_POST['confirmar_clave'] ?? '';
    
    if (empty($claveActual) || empty($nuevaClave) || empty($confirmarClave)) {
        $error = 'Todos los campos son obligatorios';
    } elseif (!password_verify($claveActual, $cliente['password'])) {
        $error = 'La contraseña actual es incorrecta';
    } elseif (strlen($nuevaClave) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres';
    } elseif ($nuevaClave !== $confirmarClave) {
        $error = 'Las contraseñas no coinciden';
    } else {
        $model->update($clientId, [
            'password' => password_hash($nuevaClave, PASSWORD_DEFAULT)
        ]);
        $success = 'Contraseña actualizada correctamente';
    }
}
?>

<div class="mb-4 fade-in-up">
    <h4 class="mb-2">Mi Perfil</h4>
    <p class="text-muted mb-0">Información de tu cuenta</p>
</div>

<div class="row g-4">
    <!-- Información del perfil -->
    <div class="col-lg-6 fade-in-up">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-person-circle me-2"></i>Información Personal</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 28px;">
                        <?= strtoupper(substr($cliente['nombre'], 0, 1)) ?>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($cliente['nombre']) ?></h5>
                    <p class="text-muted mb-0"><?= htmlspecialchars($cliente['email']) ?></p>
                </div>
                
                <hr style="border-color: var(--border-color);">
                
                <div class="row g-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Rol</small>
                        <span class="badge bg-info">Cliente</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Estado</small>
                        <span class="badge bg-success">Activo</span>
                    </div>
                    <?php if ($cliente['cargo']): ?>
                    <div class="col-6">
                        <small class="text-muted d-block">Cargo</small>
                        <strong><?= htmlspecialchars($cliente['cargo']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if ($cliente['telefono']): ?>
                    <div class="col-6">
                        <small class="text-muted d-block">Teléfono</small>
                        <strong><?= htmlspecialchars($cliente['telefono']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <small class="text-muted d-block">Miembro desde</small>
                        <strong><?= formatDate($cliente['fecha_creacion'], 'd/m/Y') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Información de la empresa -->
    <?php if ($empresa): ?>
    <div class="col-lg-6 fade-in-up">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-building me-2"></i>Mi Empresa</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <?php if ($empresa['logo']): ?>
                    <img src="<?= UPLOADS_URL . '/' . $empresa['logo'] ?>" alt="<?= htmlspecialchars($empresa['nombre']) ?>" 
                         style="max-height: 80px; width: auto;">
                    <?php else: ?>
                    <div class="user-avatar mx-auto" style="width: 80px; height: 80px; font-size: 28px;">
                        <?= strtoupper(substr($empresa['nombre'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <h5 class="text-center mb-3"><?= htmlspecialchars($empresa['nombre']) ?></h5>
                
                <hr style="border-color: var(--border-color);">
                
                <div class="row g-3">
                    <?php if ($empresa['nit']): ?>
                    <div class="col-6">
                        <small class="text-muted d-block">NIT</small>
                        <strong><?= htmlspecialchars($empresa['nit']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if ($empresa['telefono']): ?>
                    <div class="col-6">
                        <small class="text-muted d-block">Teléfono</small>
                        <strong><?= htmlspecialchars($empresa['telefono']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if ($empresa['email']): ?>
                    <div class="col-12">
                        <small class="text-muted d-block">Email</small>
                        <strong><?= htmlspecialchars($empresa['email']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if ($empresa['direccion']): ?>
                    <div class="col-12">
                        <small class="text-muted d-block">Dirección</small>
                        <strong><?= htmlspecialchars($empresa['direccion']) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Cambiar contraseña -->
    <div class="col-lg-6 fade-in-up">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Cambiar Contraseña</h6>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="cambiar_clave" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Contraseña Actual</label>
                        <input type="password" name="clave_actual" class="form-control" required
                               style="background: var(--bg-tertiary); border-color: var(--border-color); color: var(--text-primary);">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" name="nueva_clave" class="form-control" minlength="6" required
                               style="background: var(--bg-tertiary); border-color: var(--border-color); color: var(--text-primary);">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" name="confirmar_clave" class="form-control" required
                               style="background: var(--bg-tertiary); border-color: var(--border-color); color: var(--text-primary);">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-2"></i>Actualizar Contraseña
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

