<?php
/**
 * AND PROJECTS APP - Perfil de Usuario
 */

require_once __DIR__ . '/../../../models/UserModel.php';

$userModel = new UserModel();
$currentUser = getCurrentUser();
$user = $userModel->getById($currentUser['id']);

$error = '';
$success = '';

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'cambiar_password') {
        $passwordActual = $_POST['password_actual'] ?? '';
        $passwordNuevo = $_POST['password_nuevo'] ?? '';
        $passwordConfirmar = $_POST['password_confirmar'] ?? '';
        
        // Validar contraseña actual
        if (!password_verify($passwordActual, $user['password'])) {
            $error = 'La contraseña actual es incorrecta';
        } elseif (strlen($passwordNuevo) < 8) {
            $error = 'La nueva contraseña debe tener al menos 8 caracteres';
        } elseif ($passwordNuevo !== $passwordConfirmar) {
            $error = 'Las contraseñas no coinciden';
        } else {
            $hashedPassword = password_hash($passwordNuevo, PASSWORD_DEFAULT);
            $result = $userModel->update($currentUser['id'], ['password' => $hashedPassword]);
            
            if ($result) {
                $success = 'Contraseña actualizada correctamente';
            } else {
                $error = 'Error al actualizar la contraseña';
            }
        }
    } elseif ($_POST['action'] === 'actualizar_perfil') {
        $nombre = trim($_POST['nombre'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $cargo = trim($_POST['cargo'] ?? '');
        
        if (empty($nombre)) {
            $error = 'El nombre es requerido';
        } else {
            $updateData = [
                'nombre' => $nombre,
                'telefono' => $telefono,
                'cargo' => $cargo
            ];
            
            // Procesar avatar si se subió
            if (!empty($_FILES['avatar']['name'])) {
                $file = $_FILES['avatar'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file['type'], $allowedTypes)) {
                    $error = 'Tipo de archivo no permitido. Use JPG, PNG, GIF o WEBP.';
                } elseif ($file['size'] > $maxSize) {
                    $error = 'El archivo es demasiado grande. Máximo 5MB.';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $newName = 'avatar_' . $currentUser['id'] . '_' . uniqid() . '.' . $ext;
                    $uploadPath = UPLOADS_PATH . '/avatars/';
                    
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }
                    
                    if (move_uploaded_file($file['tmp_name'], $uploadPath . $newName)) {
                        $updateData['avatar'] = 'avatars/' . $newName;
                        
                        // Eliminar avatar anterior
                        if (!empty($user['avatar']) && file_exists(UPLOADS_PATH . '/' . $user['avatar'])) {
                            unlink(UPLOADS_PATH . '/' . $user['avatar']);
                        }
                    }
                }
            }
            
            if (empty($error)) {
                $result = $userModel->update($currentUser['id'], $updateData);
                
                if ($result) {
                    $success = 'Perfil actualizado correctamente';
                    // Actualizar datos en sesión
                    $_SESSION['user']['nombre'] = $nombre;
                    if (isset($updateData['avatar'])) {
                        $_SESSION['user']['avatar'] = $updateData['avatar'];
                    }
                    $user = $userModel->getById($currentUser['id']);
                } else {
                    $error = 'Error al actualizar el perfil';
                }
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
    <div>
        <h5 class="mb-0">Mi Perfil</h5>
        <small class="text-muted">Gestiona tu información personal y contraseña</small>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show fade-in-up" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show fade-in-up" role="alert">
    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Información del Perfil -->
    <div class="col-lg-8 fade-in-up" style="animation-delay: 0.1s">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Información Personal</h6>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="actualizar_perfil">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <small class="text-muted">El email no se puede modificar</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Cargo</label>
                            <input type="text" name="cargo" class="form-control" value="<?= htmlspecialchars($user['cargo'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Rol</label>
                            <input type="text" class="form-control text-capitalize" value="<?= htmlspecialchars($user['rol']) ?>" disabled>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Avatar</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                            <small class="text-muted">JPG, PNG, GIF o WEBP. Máx 5MB</small>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4 fade-in-up" style="animation-delay: 0.2s">
        <!-- Avatar actual -->
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="user-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 36px;">
                    <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= UPLOADS_URL . '/' . $user['avatar'] ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                    <?= strtoupper(substr($user['nombre'], 0, 2)) ?>
                    <?php endif; ?>
                </div>
                <h5 class="mb-1"><?= htmlspecialchars($user['nombre']) ?></h5>
                <p class="text-muted mb-0 text-capitalize"><?= htmlspecialchars($user['rol']) ?></p>
                <small class="text-muted">
                    Miembro desde <?= date('d/m/Y', strtotime($user['fecha_creacion'])) ?>
                </small>
            </div>
        </div>
        
        <!-- Cambiar contraseña -->
        <div class="card" id="cambiar-password">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Cambiar Contraseña</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="cambiar_password">
                    
                    <div class="mb-3">
                        <label class="form-label">Contraseña Actual</label>
                        <input type="password" name="password_actual" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" name="password_nuevo" class="form-control" required minlength="8">
                        <small class="text-muted">Mínimo 8 caracteres</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirmar Contraseña</label>
                        <input type="password" name="password_confirmar" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="bi bi-key me-2"></i>Cambiar Contraseña
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

