<?php
/**
 * AND PROJECTS APP - Ver Usuario
 */

require_once __DIR__ . '/../../../models/UserModel.php';
require_once __DIR__ . '/../../empresas/models/EmpresaModel.php';

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

// Obtener empresa si tiene
$empresa = null;
if ($usuario['empresa_id']) {
    $empresaModel = new EmpresaModel();
    $empresa = $empresaModel->getById($usuario['empresa_id']);
}

$pageTitle = $usuario['nombre'];
$pageSubtitle = 'Perfil de usuario';

// Obtener estadísticas del usuario (tareas asignadas, etc.)
try {
    $db = Database::getInstance();
    
    // Tareas asignadas
    $stmt = $db->prepare("SELECT COUNT(*) FROM proyectos_tareas WHERE asignado_id = :id");
    $stmt->execute(['id' => $id]);
    $tareasAsignadas = $stmt->fetchColumn();
    
    // Subtareas realizadas
    $stmt = $db->prepare("SELECT COUNT(*) FROM proyectos_subtareas WHERE realizado_por = :id AND estado = 3");
    $stmt->execute(['id' => $id]);
    $subtareasCompletadas = $stmt->fetchColumn();
    
    // Comentarios realizados
    $stmt = $db->prepare("SELECT COUNT(*) FROM proyectos_comentarios WHERE usuario_id = :id");
    $stmt->execute(['id' => $id]);
    $totalComentarios = $stmt->fetchColumn();
    
    // Reuniones creadas
    $stmt = $db->prepare("SELECT COUNT(*) FROM proyectos_reuniones WHERE creado_por = :id");
    $stmt->execute(['id' => $id]);
    $reunionesCreadas = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $tareasAsignadas = 0;
    $subtareasCompletadas = 0;
    $totalComentarios = 0;
    $reunionesCreadas = 0;
}

$rolClass = match($usuario['rol']) {
    'admin' => 'danger',
    'colaborador' => 'primary',
    'cliente' => 'info',
    default => 'secondary'
};
?>

<div class="d-flex justify-content-between align-items-start mb-4 fade-in-up">
    <div class="d-flex align-items-center gap-3">
        <div class="user-avatar" style="width: 64px; height: 64px; font-size: 24px;">
            <?php if (!empty($usuario['avatar'])): ?>
            <img src="<?= UPLOADS_URL . '/' . htmlspecialchars($usuario['avatar']) ?>" alt="Avatar">
            <?php else: ?>
            <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="mb-0"><?= htmlspecialchars($usuario['nombre']) ?></h4>
                <span class="badge bg-<?= $rolClass ?>"><?= ucfirst($usuario['rol']) ?></span>
                <?php if ($usuario['estado'] == 1): ?>
                <span class="badge bg-success">Activo</span>
                <?php else: ?>
                <span class="badge bg-danger">Inactivo</span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-0">
                <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($usuario['email']) ?>
                <?php if ($empresa): ?>
                <span class="mx-2">•</span>
                <i class="bi bi-building me-1"></i><?= htmlspecialchars($empresa['nombre']) ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('usuarios', 'editar')): ?>
        <a href="<?= uiModuleUrl('usuarios', 'editar', ['id' => $id]) ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-2"></i>Editar
        </a>
        <?php endif; ?>
        <a href="<?= uiModuleUrl('usuarios') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Estadísticas -->
    <div class="col-12">
        <div class="row g-3 fade-in-up">
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="stat-value text-primary"><?= $tareasAsignadas ?></div>
                        <div class="stat-label">Tareas Asignadas</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="stat-value text-success"><?= $subtareasCompletadas ?></div>
                        <div class="stat-label">Subtareas Completadas</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="stat-value"><?= $totalComentarios ?></div>
                        <div class="stat-label">Comentarios</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="stat-value text-info"><?= $reunionesCreadas ?></div>
                        <div class="stat-label">Reuniones Creadas</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del usuario -->
    <div class="col-lg-6">
        <div class="card fade-in-up h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Información Personal</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Nombre completo</td>
                        <td><strong><?= htmlspecialchars($usuario['nombre']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email</td>
                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Teléfono</td>
                        <td><?= $usuario['telefono'] ? htmlspecialchars($usuario['telefono']) : '<span class="text-muted">No especificado</span>' ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cargo</td>
                        <td><?= $usuario['cargo'] ? htmlspecialchars($usuario['cargo']) : '<span class="text-muted">No especificado</span>' ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Rol</td>
                        <td><span class="badge bg-<?= $rolClass ?>"><?= ucfirst($usuario['rol']) ?></span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Información de cuenta -->
    <div class="col-lg-6">
        <div class="card fade-in-up h-100">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>Información de Cuenta</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Estado</td>
                        <td>
                            <?php if ($usuario['estado'] == 1): ?>
                            <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Empresa</td>
                        <td>
                            <?php if ($empresa): ?>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($empresa['logo'])): ?>
                                <img src="<?= UPLOADS_URL . '/' . $empresa['logo'] ?>" 
                                     alt="<?= htmlspecialchars($empresa['nombre']) ?>" 
                                     class="rounded" style="width: 20px; height: 20px; object-fit: contain; background: #fff;">
                                <?php endif; ?>
                                <a href="<?= uiModuleUrl('empresas', 'ver', ['id' => $empresa['id']]) ?>">
                                    <?= htmlspecialchars($empresa['nombre']) ?>
                                </a>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">Sin empresa asignada</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Fecha de registro</td>
                        <td><?= formatDateTime($usuario['fecha_creacion']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Último acceso</td>
                        <td>
                            <?php if ($usuario['ultimo_acceso']): ?>
                            <?= formatDateTime($usuario['ultimo_acceso']) ?>
                            <?php else: ?>
                            <span class="text-muted">Nunca ha ingresado</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cambio de clave pendiente</td>
                        <td>
                            <?php if ($usuario['requiere_cambio_clave'] ?? 0): ?>
                            <span class="badge bg-warning text-dark">Sí</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <?php if (hasPermission('usuarios', 'editar') && $usuario['id'] != getCurrentUserId()): ?>
    <!-- Acciones rápidas -->
    <div class="col-12">
        <div class="card fade-in-up">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Acciones Rápidas</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-warning" onclick="resetPassword()">
                        <i class="bi bi-key me-2"></i>Restablecer Contraseña
                    </button>
                    <?php if ($usuario['estado'] == 1): ?>
                    <button type="button" class="btn btn-outline-danger" onclick="toggleStatus(0)">
                        <i class="bi bi-person-x me-2"></i>Desactivar Usuario
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn btn-outline-success" onclick="toggleStatus(1)">
                        <i class="bi bi-person-check me-2"></i>Activar Usuario
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
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

function toggleStatus(newStatus) {
    const action = newStatus == 1 ? 'activar' : 'desactivar';
    Swal.fire({
        title: `¿${action.charAt(0).toUpperCase() + action.slice(1)} usuario?`,
        text: `El usuario será ${newStatus == 1 ? 'activado' : 'desactivado'}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: newStatus == 1 ? '#9AD082' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Sí, ${action}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= uiModuleUrl('usuarios', 'toggle-status', ['id' => $id]) ?>&estado=' + newStatus;
        }
    });
}
</script>

