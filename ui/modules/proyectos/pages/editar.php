<?php
/**
 * AND PROJECTS APP - Editar Proyecto
 */

if (!hasPermission('proyectos', 'editar')) {
    setFlashMessage('error', 'No tiene permisos para editar proyectos');
    header('Location: ' . uiModuleUrl('proyectos'));
    exit;
}

require_once __DIR__ . '/../models/ProyectoModel.php';
require_once __DIR__ . '/../../empresas/models/EmpresaModel.php';

$model = new ProyectoModel();
$empresaModel = new EmpresaModel();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setFlashMessage('error', 'Proyecto no especificado');
    header('Location: ' . uiModuleUrl('proyectos'));
    exit;
}

$proyecto = $model->getById($id);

if (!$proyecto) {
    setFlashMessage('error', 'Proyecto no encontrado');
    header('Location: ' . uiModuleUrl('proyectos'));
    exit;
}

$pageTitle = 'Editar: ' . $proyecto['nombre'];
$pageSubtitle = 'Modificar información del proyecto';

$empresas = $empresaModel->getActiveForSelect();

$errors = [];
$formData = [
    'empresa_id' => $proyecto['empresa_id'] ?? '',
    'nombre' => $proyecto['nombre'] ?? '',
    'descripcion' => $proyecto['descripcion'] ?? '',
    'color' => $proyecto['color'] ?? '#55A5C8',
    'fecha_inicio' => $proyecto['fecha_inicio'] ?? '',
    'fecha_fin_estimada' => $proyecto['fecha_fin_estimada'] ?? '',
    'fecha_fin_real' => $proyecto['fecha_fin_real'] ?? '',
    'estado' => $proyecto['estado'] ?? 1
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_merge($formData, $_POST);
    
    // Validaciones
    if (empty($formData['empresa_id'])) {
        $errors[] = 'Debe seleccionar una empresa';
    }
    
    if (empty($formData['nombre'])) {
        $errors[] = 'El nombre del proyecto es obligatorio';
    }
    
    // Si no hay errores, guardar
    if (empty($errors)) {
        try {
            $model->update($id, [
                'empresa_id' => $formData['empresa_id'],
                'nombre' => $formData['nombre'],
                'descripcion' => $formData['descripcion'] ?: null,
                'color' => $formData['color'] ?: '#55A5C8',
                'fecha_inicio' => $formData['fecha_inicio'] ?: null,
                'fecha_fin_estimada' => $formData['fecha_fin_estimada'] ?: null,
                'fecha_fin_real' => $formData['fecha_fin_real'] ?: null,
                'estado' => $formData['estado']
            ]);
            
            setFlashMessage('success', 'Proyecto actualizado correctamente');
            header('Location: ' . uiModuleUrl('proyectos', 'ver', ['id' => $id]));
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Error al actualizar el proyecto: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-pencil me-2"></i>Editar Proyecto</h6>
                <a href="<?= uiModuleUrl('proyectos', 'ver', ['id' => $id]) ?>" class="btn btn-sm btn-outline-secondary">
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
                            <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-2"></i>Información del Proyecto</h6>
                        </div>
                        
                        <div class="col-md-8">
                            <label class="form-label">Empresa *</label>
                            <select name="empresa_id" id="empresa_id" class="form-select" required>
                                <option value="">Seleccione una empresa...</option>
                                <?php foreach ($empresas as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $formData['empresa_id'] == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Color del Proyecto</label>
                            <input type="color" name="color" class="form-control form-control-color w-100" value="<?= htmlspecialchars($formData['color']) ?>">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Nombre del Proyecto *</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($formData['nombre']) ?>" required placeholder="Ej: Rediseño de sitio web">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción general del proyecto..."><?= htmlspecialchars($formData['descripcion']) ?></textarea>
                        </div>
                        
                        <!-- Fechas -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-calendar me-2"></i>Planificación</h6>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($formData['fecha_inicio']) ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Fecha Estimada de Finalización</label>
                            <input type="date" name="fecha_fin_estimada" class="form-control" value="<?= htmlspecialchars($formData['fecha_fin_estimada']) ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Fecha Real de Finalización</label>
                            <input type="date" name="fecha_fin_real" class="form-control" value="<?= htmlspecialchars($formData['fecha_fin_real']) ?>">
                        </div>
                        
                        <!-- Estado -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-2"></i>Estado</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Estado del Proyecto</label>
                            <select name="estado" class="form-select">
                                <option value="1" <?= $formData['estado'] == 1 ? 'selected' : '' ?>>⚪ Pendiente</option>
                                <option value="2" <?= $formData['estado'] == 2 ? 'selected' : '' ?>>🔵 En Progreso</option>
                                <option value="3" <?= $formData['estado'] == 3 ? 'selected' : '' ?>>🟢 Finalizado</option>
                                <option value="4" <?= $formData['estado'] == 4 ? 'selected' : '' ?>>🟠 Pausado</option>
                                <option value="0" <?= $formData['estado'] == 0 ? 'selected' : '' ?>>🔴 Cancelado</option>
                            </select>
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 mt-4">
                            <hr class="my-3">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= uiModuleUrl('proyectos', 'ver', ['id' => $id]) ?>" class="btn btn-outline-secondary">Cancelar</a>
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

