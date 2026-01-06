<?php
/**
 * AND PROJECTS APP - Crear Usuario
 */

if (!hasPermission('usuarios', 'crear')) {
    setFlashMessage('error', 'No tiene permisos para crear usuarios');
    header('Location: ' . uiModuleUrl('usuarios'));
    exit;
}

$pageTitle = 'Nuevo Usuario';
$pageSubtitle = 'Crear un nuevo usuario';

require_once __DIR__ . '/../../../models/UserModel.php';
require_once __DIR__ . '/../../empresas/models/EmpresaModel.php';

$model = new UserModel();
$empresaModel = new EmpresaModel();

$empresas = $empresaModel->getActiveForSelect();

$errors = [];
$formData = [
    'nombre' => '',
    'email' => '',
    'rol' => 'colaborador',
    'empresa_id' => $_GET['empresa_id'] ?? '',
    'cargo' => '',
    'telefono' => ''
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_merge($formData, $_POST);
    
    // Validaciones
    if (empty($formData['nombre'])) {
        $errors[] = 'El nombre es obligatorio';
    }
    
    if (empty($formData['email'])) {
        $errors[] = 'El email es obligatorio';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del email no es válido';
    } elseif ($model->emailExists($formData['email'])) {
        $errors[] = 'Este email ya está registrado';
    }
    
    if ($formData['rol'] === 'cliente' && empty($formData['empresa_id'])) {
        $errors[] = 'Los clientes deben tener una empresa asignada';
    }
    
    // Generar contraseña temporal
    $passwordTemp = bin2hex(random_bytes(4)); // 8 caracteres aleatorios
    
    // Si no hay errores, guardar
    if (empty($errors)) {
        try {
            $id = $model->create([
                'nombre' => $formData['nombre'],
                'email' => $formData['email'],
                'password' => password_hash($passwordTemp, PASSWORD_DEFAULT),
                'rol' => $formData['rol'],
                'empresa_id' => $formData['empresa_id'] ?: null,
                'cargo' => $formData['cargo'] ?: null,
                'telefono' => $formData['telefono'] ?: null,
                'requiere_cambio_clave' => $formData['rol'] === 'cliente' ? 1 : 0
            ]);
            
            // Mostrar contraseña temporal
            setFlashMessage('success', "Usuario creado correctamente. Contraseña temporal: <strong>$passwordTemp</strong> (comunicar al usuario)");
            header('Location: ' . uiModuleUrl('usuarios', 'ver', ['id' => $id]));
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Error al crear el usuario: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>Nuevo Usuario</h6>
                <a href="<?= uiModuleUrl('usuarios') ?>" class="btn btn-sm btn-outline-secondary">
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
                            <select name="rol" id="rol" class="form-select" required>
                                <option value="colaborador" <?= $formData['rol'] === 'colaborador' ? 'selected' : '' ?>>Colaborador</option>
                                <?php if (isAdmin()): ?>
                                <option value="admin" <?= $formData['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                <?php endif; ?>
                                <option value="cliente" <?= $formData['rol'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                            </select>
                            <small class="text-muted">Los clientes acceden a la interfaz de visualización</small>
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
                            <input type="text" name="cargo" class="form-control" value="<?= htmlspecialchars($formData['cargo']) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($formData['telefono']) ?>">
                        </div>
                        
                        <!-- Nota sobre contraseña -->
                        <div class="col-12 mt-4">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Se generará una contraseña temporal automáticamente. El usuario deberá cambiarla en su primer acceso (especialmente los clientes).
                            </div>
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 mt-4">
                            <hr class="my-3">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= uiModuleUrl('usuarios') ?>" class="btn btn-outline-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>Crear Usuario
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Hacer empresa obligatoria cuando el rol es cliente
document.getElementById('rol').addEventListener('change', function() {
    const empresaSelect = document.getElementById('empresa_id');
    const empresaHelp = document.getElementById('empresa_help');
    
    if (this.value === 'cliente') {
        empresaSelect.setAttribute('required', 'required');
        empresaHelp.classList.add('text-danger');
        empresaHelp.textContent = 'Obligatorio para clientes';
    } else {
        empresaSelect.removeAttribute('required');
        empresaHelp.classList.remove('text-danger');
        empresaHelp.textContent = 'Opcional para colaboradores';
    }
});
</script>

