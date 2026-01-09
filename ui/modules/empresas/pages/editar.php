<?php
/**
 * AND PROJECTS APP - Editar Empresa
 */

if (!hasPermission('empresas', 'editar')) {
    setFlashMessage('error', 'No tiene permisos para editar empresas');
    header('Location: ' . uiModuleUrl('empresas'));
    exit;
}

$id = $_GET['id'] ?? 0;
if (!$id) {
    setFlashMessage('error', 'Empresa no especificada');
    header('Location: ' . uiModuleUrl('empresas'));
    exit;
}

require_once __DIR__ . '/../models/EmpresaModel.php';
$model = new EmpresaModel();

$empresa = $model->getById($id);
if (!$empresa) {
    setFlashMessage('error', 'Empresa no encontrada');
    header('Location: ' . uiModuleUrl('empresas'));
    exit;
}

$pageTitle = 'Editar Empresa';
$pageSubtitle = $empresa['nombre'];

$errors = [];
$formData = $empresa;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_merge($formData, $_POST);
    
    // Validaciones
    if (empty($formData['nombre'])) {
        $errors[] = 'El nombre de la empresa es obligatorio';
    }
    
    // Procesar logo si se subió uno nuevo
    $newLogo = null;
    if (!empty($_FILES['logo']['name'])) {
        $uploadDir = UPLOADS_PATH . '/empresas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
            $errors[] = 'El logo debe ser una imagen (JPG, PNG, GIF o WEBP)';
        } elseif ($_FILES['logo']['size'] > $maxSize) {
            $errors[] = 'El logo no puede superar 5MB';
        } else {
            $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'empresa_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $filepath)) {
                $newLogo = 'empresas/' . $filename;
                
                // Eliminar logo anterior si existe
                if ($empresa['logo'] && file_exists(UPLOADS_PATH . '/' . $empresa['logo'])) {
                    unlink(UPLOADS_PATH . '/' . $empresa['logo']);
                }
            } else {
                $errors[] = 'Error al subir el logo';
            }
        }
    }
    
    // Si no hay errores, actualizar
    if (empty($errors)) {
        try {
            $dataToUpdate = [
                'nombre' => $formData['nombre'],
                'razon_social' => $formData['razon_social'] ?: null,
                'nit' => $formData['nit'] ?: null,
                'email' => $formData['email'] ?: null,
                'telefono' => $formData['telefono'] ?: null,
                'direccion' => $formData['direccion'] ?: null,
                'ciudad' => $formData['ciudad'] ?: null,
                'pais' => $formData['pais'] ?: 'Colombia',
                'sitio_web' => $formData['sitio_web'] ?: null,
                'descripcion' => $formData['descripcion'] ?: null,
                'color_primario' => $formData['color_primario'] ?: '#55A5C8',
                'estado' => isset($formData['estado']) ? (int)$formData['estado'] : 1
            ];
            
            if ($newLogo) {
                $dataToUpdate['logo'] = $newLogo;
            }
            
            $model->update($id, $dataToUpdate);
            
            setFlashMessage('success', 'Empresa actualizada correctamente');
            header('Location: ' . uiModuleUrl('empresas', 'ver', ['id' => $id]));
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Error al actualizar la empresa: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-pencil me-2"></i>Editar Empresa</h6>
                <a href="<?= uiModuleUrl('empresas', 'ver', ['id' => $id]) ?>" class="btn btn-sm btn-outline-secondary">
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
                        <!-- Logo actual -->
                        <?php if ($empresa['logo']): ?>
                        <div class="col-12">
                            <label class="form-label">Logo Actual</label>
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= UPLOADS_URL . '/' . $empresa['logo'] ?>" alt="Logo" 
                                     style="width: 80px; height: 80px; object-fit: contain; border-radius: 12px; background: rgba(255,255,255,0.05); padding: 8px;">
                                <small class="text-muted">Sube una nueva imagen para reemplazar el logo actual</small>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Información básica -->
                        <div class="col-12">
                            <h6 class="text-muted mb-3"><i class="bi bi-building me-2"></i>Información Básica</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Nombre de la Empresa *</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($formData['nombre']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Razón Social</label>
                            <input type="text" name="razon_social" class="form-control" value="<?= htmlspecialchars($formData['razon_social'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">NIT</label>
                            <input type="text" name="nit" class="form-control" value="<?= htmlspecialchars($formData['nit'] ?? '') ?>" placeholder="123456789-0">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                            <small class="form-text">JPG, PNG, GIF o WEBP. Máximo 5MB</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Color Primario</label>
                            <input type="color" name="color_primario" class="form-control form-control-color w-100" value="<?= htmlspecialchars($formData['color_primario'] ?? '#55A5C8') ?>">
                        </div>
                        
                        <!-- Contacto -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-telephone me-2"></i>Contacto</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" placeholder="contacto@empresa.com">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control" value="<?= htmlspecialchars($formData['telefono'] ?? '') ?>" placeholder="+57 300 123 4567">
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Sitio Web</label>
                            <input type="url" name="sitio_web" class="form-control" value="<?= htmlspecialchars($formData['sitio_web'] ?? '') ?>" placeholder="https://www.empresa.com">
                        </div>
                        
                        <!-- Ubicación -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-geo-alt me-2"></i>Ubicación</h6>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($formData['direccion'] ?? '') ?>" placeholder="Calle 123 # 45-67, Oficina 890">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ciudad</label>
                            <input type="text" name="ciudad" class="form-control" value="<?= htmlspecialchars($formData['ciudad'] ?? '') ?>" placeholder="Bogotá">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">País</label>
                            <input type="text" name="pais" class="form-control" value="<?= htmlspecialchars($formData['pais'] ?? 'Colombia') ?>">
                        </div>
                        
                        <!-- Descripción -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-text-paragraph me-2"></i>Descripción</h6>
                        </div>
                        
                        <div class="col-12">
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción de la empresa..."><?= htmlspecialchars($formData['descripcion'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Estado -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-toggle-on me-2"></i>Estado</h6>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="estado" value="1" id="estado" <?= ($formData['estado'] ?? 1) == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="estado">Empresa activa</label>
                            </div>
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 mt-4">
                            <hr class="my-3">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= uiModuleUrl('empresas', 'ver', ['id' => $id]) ?>" class="btn btn-outline-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

