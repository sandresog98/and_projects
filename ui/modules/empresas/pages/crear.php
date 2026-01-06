<?php
/**
 * AND PROJECTS APP - Crear Empresa
 */

if (!hasPermission('empresas', 'crear')) {
    setFlashMessage('error', 'No tiene permisos para crear empresas');
    header('Location: ' . uiModuleUrl('empresas'));
    exit;
}

$pageTitle = 'Nueva Empresa';
$pageSubtitle = 'Registrar una nueva empresa';

require_once __DIR__ . '/../models/EmpresaModel.php';
$model = new EmpresaModel();

$errors = [];
$formData = [
    'nombre' => '',
    'razon_social' => '',
    'nit' => '',
    'email' => '',
    'telefono' => '',
    'direccion' => '',
    'ciudad' => '',
    'pais' => 'Colombia',
    'sitio_web' => '',
    'descripcion' => '',
    'color_primario' => '#55A5C8'
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_merge($formData, $_POST);
    
    // Validaciones
    if (empty($formData['nombre'])) {
        $errors[] = 'El nombre es obligatorio';
    }
    
    if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del email no es válido';
    }
    
    // Procesar logo
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = UPLOAD_MAX_IMAGE_SIZE;
        
        if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
            $errors[] = 'El logo debe ser una imagen (JPG, PNG, GIF, WEBP)';
        } elseif ($_FILES['logo']['size'] > $maxSize) {
            $errors[] = 'El logo no puede superar 5MB';
        } else {
            $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $nombreArchivo = 'empresa_' . uniqid() . '.' . $extension;
            $uploadDir = UPLOADS_PATH . '/empresas/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $nombreArchivo)) {
                $logoPath = 'empresas/' . $nombreArchivo;
            } else {
                $errors[] = 'Error al subir el logo';
            }
        }
    }
    
    // Si no hay errores, guardar
    if (empty($errors)) {
        try {
            $id = $model->create([
                'nombre' => $formData['nombre'],
                'razon_social' => $formData['razon_social'] ?: null,
                'nit' => $formData['nit'] ?: null,
                'logo' => $logoPath,
                'email' => $formData['email'] ?: null,
                'telefono' => $formData['telefono'] ?: null,
                'direccion' => $formData['direccion'] ?: null,
                'ciudad' => $formData['ciudad'] ?: null,
                'pais' => $formData['pais'] ?: 'Colombia',
                'sitio_web' => $formData['sitio_web'] ?: null,
                'descripcion' => $formData['descripcion'] ?: null,
                'color_primario' => $formData['color_primario'] ?: '#55A5C8'
            ]);
            
            setFlashMessage('success', 'Empresa creada correctamente');
            header('Location: ' . uiModuleUrl('empresas', 'ver', ['id' => $id]));
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Error al crear la empresa: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-building me-2"></i>Nueva Empresa</h6>
                <a href="<?= uiModuleUrl('empresas') ?>" class="btn btn-sm btn-outline-secondary">
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
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <!-- Información básica -->
                        <div class="col-12">
                            <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-2"></i>Información Básica</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Nombre de la Empresa *</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($formData['nombre']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Razón Social</label>
                            <input type="text" name="razon_social" class="form-control" value="<?= htmlspecialchars($formData['razon_social']) ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">NIT</label>
                            <input type="text" name="nit" class="form-control" value="<?= htmlspecialchars($formData['nit']) ?>" placeholder="900.123.456-7">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Color Corporativo</label>
                            <input type="color" name="color_primario" class="form-control form-control-color w-100" value="<?= htmlspecialchars($formData['color_primario']) ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <small class="text-muted">JPG, PNG, GIF, WEBP. Máx. 5MB</small>
                        </div>
                        
                        <!-- Contacto -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-telephone me-2"></i>Información de Contacto</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($formData['email']) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($formData['telefono']) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Sitio Web</label>
                            <input type="url" name="sitio_web" class="form-control" value="<?= htmlspecialchars($formData['sitio_web']) ?>" placeholder="https://...">
                        </div>
                        
                        <!-- Ubicación -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-geo-alt me-2"></i>Ubicación</h6>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($formData['direccion']) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ciudad</label>
                            <input type="text" name="ciudad" class="form-control" value="<?= htmlspecialchars($formData['ciudad']) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">País</label>
                            <input type="text" name="pais" class="form-control" value="<?= htmlspecialchars($formData['pais']) ?>">
                        </div>
                        
                        <!-- Descripción -->
                        <div class="col-12 mt-4">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($formData['descripcion']) ?></textarea>
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 mt-4">
                            <hr class="my-3">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= uiModuleUrl('empresas') ?>" class="btn btn-outline-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>Crear Empresa
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

