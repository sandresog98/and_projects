<?php
/**
 * AND PROJECTS APP - Crear Subtarea
 */

if (!hasPermission('subtareas', 'crear')) {
    setFlashMessage('error', 'No tiene permisos para crear subtareas');
    header('Location: ' . uiModuleUrl('subtareas'));
    exit;
}

$pageTitle = 'Nueva Subtarea';
$pageSubtitle = 'Crear una nueva subtarea';

require_once __DIR__ . '/../models/SubtareaModel.php';
require_once __DIR__ . '/../../tareas/models/TareaModel.php';

$model = new SubtareaModel();
$tareaModel = new TareaModel();

$tareas = $tareaModel->getTareasActivas();
$colaboradores = $model->getColaboradoresSelect();

$errors = [];
$formData = [
    'tarea_id' => $_GET['tarea_id'] ?? '',
    'nombre' => '',
    'descripcion' => '',
    'fecha_inicio_estimada' => date('Y-m-d'),
    'fecha_fin_estimada' => '',
    'horas_estimadas' => '',
    'realizado_por' => ''
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_merge($formData, $_POST);
    
    // Validaciones
    if (empty($formData['tarea_id'])) {
        $errors[] = 'Debe seleccionar una tarea';
    }
    
    if (empty($formData['nombre'])) {
        $errors[] = 'El nombre de la subtarea es obligatorio';
    }
    
    // Si no hay errores, guardar
    if (empty($errors)) {
        try {
            $id = $model->create([
                'tarea_id' => $formData['tarea_id'],
                'nombre' => $formData['nombre'],
                'descripcion' => $formData['descripcion'] ?: null,
                'fecha_inicio_estimada' => $formData['fecha_inicio_estimada'] ?: null,
                'fecha_fin_estimada' => $formData['fecha_fin_estimada'] ?: null,
                'horas_estimadas' => $formData['horas_estimadas'] ?: null,
                'realizado_por' => $formData['realizado_por'] ?: null
            ]);
            
            setFlashMessage('success', 'Subtarea creada correctamente');
            
            // Redirigir a la tarea si vino desde allí
            if (!empty($formData['tarea_id'])) {
                header('Location: ' . uiModuleUrl('tareas', 'ver', ['id' => $formData['tarea_id']]));
            } else {
                header('Location: ' . uiModuleUrl('subtareas'));
            }
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Error al crear la subtarea: ' . $e->getMessage();
        }
    }
}

// Si viene de una tarea, obtener info
$tareaSeleccionada = null;
if (!empty($formData['tarea_id'])) {
    $tareaSeleccionada = $tareaModel->getById($formData['tarea_id']);
}
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Nueva Subtarea</h6>
                <?php if ($tareaSeleccionada): ?>
                <a href="<?= uiModuleUrl('tareas', 'ver', ['id' => $formData['tarea_id']]) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver a la Tarea
                </a>
                <?php else: ?>
                <a href="<?= uiModuleUrl('subtareas') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($tareaSeleccionada): ?>
                <div class="alert alert-info d-flex align-items-center mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <div>
                        <strong>Tarea:</strong> <?= htmlspecialchars($tareaSeleccionada['nombre']) ?>
                        <span class="mx-2">|</span>
                        <strong>Proyecto:</strong> <?= htmlspecialchars($tareaSeleccionada['proyecto_nombre'] ?? 'Sin proyecto') ?>
                    </div>
                </div>
                <?php endif; ?>
                
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
                            <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-2"></i>Información de la Subtarea</h6>
                        </div>
                        
                        <?php if (!$tareaSeleccionada): ?>
                        <div class="col-12">
                            <label class="form-label">Tarea *</label>
                            <select name="tarea_id" class="form-select" required>
                                <option value="">Seleccione una tarea...</option>
                                <?php foreach ($tareas as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $formData['tarea_id'] == $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['nombre']) ?>
                                    <?php if (!empty($t['proyecto_nombre'])): ?>
                                    (<?= htmlspecialchars($t['proyecto_nombre']) ?>)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="tarea_id" value="<?= $formData['tarea_id'] ?>">
                        <?php endif; ?>
                        
                        <div class="col-12">
                            <label class="form-label">Nombre de la Subtarea *</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($formData['nombre']) ?>" required placeholder="Ej: Crear wireframe de la página de inicio">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción detallada de la subtarea..."><?= htmlspecialchars($formData['descripcion']) ?></textarea>
                        </div>
                        
                        <!-- Asignación -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-person me-2"></i>Asignación</h6>
                        </div>
                        
                        <div class="col-md-8">
                            <label class="form-label">Asignar a</label>
                            <select name="realizado_por" class="form-select">
                                <option value="">Sin asignar</option>
                                <?php foreach ($colaboradores as $col): ?>
                                <option value="<?= $col['id'] ?>" <?= $formData['realizado_por'] == $col['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($col['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Horas Estimadas</label>
                            <div class="input-group">
                                <input type="number" name="horas_estimadas" class="form-control" value="<?= htmlspecialchars($formData['horas_estimadas']) ?>" min="0" step="0.5" placeholder="0">
                                <span class="input-group-text">horas</span>
                            </div>
                        </div>
                        
                        <!-- Fechas -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-calendar me-2"></i>Planificación</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Inicio Estimada</label>
                            <input type="date" name="fecha_inicio_estimada" class="form-control" value="<?= htmlspecialchars($formData['fecha_inicio_estimada']) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Fin Estimada</label>
                            <input type="date" name="fecha_fin_estimada" class="form-control" value="<?= htmlspecialchars($formData['fecha_fin_estimada']) ?>">
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 mt-4">
                            <hr class="my-3">
                            <div class="d-flex justify-content-end gap-2">
                                <?php if ($tareaSeleccionada): ?>
                                <a href="<?= uiModuleUrl('tareas', 'ver', ['id' => $formData['tarea_id']]) ?>" class="btn btn-outline-secondary">Cancelar</a>
                                <?php else: ?>
                                <a href="<?= uiModuleUrl('subtareas') ?>" class="btn btn-outline-secondary">Cancelar</a>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>Crear Subtarea
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

