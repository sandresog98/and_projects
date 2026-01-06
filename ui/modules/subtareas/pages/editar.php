<?php
/**
 * AND PROJECTS APP - Editar Subtarea
 */

if (!hasPermission('subtareas', 'editar')) {
    setFlashMessage('error', 'No tiene permisos para editar subtareas');
    header('Location: ' . uiModuleUrl('subtareas'));
    exit;
}

require_once __DIR__ . '/../models/SubtareaModel.php';
require_once __DIR__ . '/../../tareas/models/TareaModel.php';

$model = new SubtareaModel();
$tareaModel = new TareaModel();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setFlashMessage('error', 'Subtarea no especificada');
    header('Location: ' . uiModuleUrl('subtareas'));
    exit;
}

$subtarea = $model->getById($id);

if (!$subtarea) {
    setFlashMessage('error', 'Subtarea no encontrada');
    header('Location: ' . uiModuleUrl('subtareas'));
    exit;
}

$pageTitle = 'Editar: ' . $subtarea['nombre'];
$pageSubtitle = 'Modificar información de la subtarea';

$tareas = $tareaModel->getTareasActivas();
$colaboradores = $model->getColaboradoresSelect();

$errors = [];
$formData = [
    'tarea_id' => $subtarea['tarea_id'],
    'nombre' => $subtarea['nombre'],
    'descripcion' => $subtarea['descripcion'] ?? '',
    'fecha_inicio_estimada' => $subtarea['fecha_inicio_estimada'] ?? '',
    'fecha_fin_estimada' => $subtarea['fecha_fin_estimada'] ?? '',
    'fecha_fin_real' => $subtarea['fecha_fin_real'] ?? '',
    'estado' => $subtarea['estado'],
    'horas_estimadas' => $subtarea['horas_estimadas'] ?? '',
    'realizado_por' => $subtarea['realizado_por'] ?? ''
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
            $model->update($id, [
                'nombre' => $formData['nombre'],
                'descripcion' => $formData['descripcion'] ?: null,
                'fecha_inicio_estimada' => $formData['fecha_inicio_estimada'] ?: null,
                'fecha_fin_estimada' => $formData['fecha_fin_estimada'] ?: null,
                'fecha_fin_real' => $formData['fecha_fin_real'] ?: null,
                'estado' => $formData['estado'],
                'horas_estimadas' => $formData['horas_estimadas'] ?: null,
                'realizado_por' => $formData['realizado_por'] ?: null
            ]);
            
            setFlashMessage('success', 'Subtarea actualizada correctamente');
            header('Location: ' . uiModuleUrl('subtareas', 'ver', ['id' => $id]));
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Error al actualizar la subtarea: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-pencil me-2"></i>Editar Subtarea</h6>
                <a href="<?= uiModuleUrl('subtareas', 'ver', ['id' => $id]) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver
                </a>
            </div>
            <div class="card-body">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb small">
                        <li class="breadcrumb-item">
                            <a href="<?= uiModuleUrl('proyectos', 'ver', ['id' => $subtarea['proyecto_id']]) ?>">
                                <?= htmlspecialchars($subtarea['proyecto_nombre'] ?? 'Proyecto') ?>
                            </a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="<?= uiModuleUrl('tareas', 'ver', ['id' => $subtarea['tarea_id']]) ?>">
                                <?= htmlspecialchars($subtarea['tarea_nombre'] ?? 'Tarea') ?>
                            </a>
                        </li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($subtarea['nombre']) ?></li>
                    </ol>
                </nav>
                
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
                        
                        <div class="col-md-8">
                            <label class="form-label">Tarea</label>
                            <select name="tarea_id" class="form-select" disabled>
                                <?php foreach ($tareas as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $formData['tarea_id'] == $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="tarea_id" value="<?= $formData['tarea_id'] ?>">
                            <small class="text-muted">La tarea no puede ser modificada</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="1" <?= $formData['estado'] == 1 ? 'selected' : '' ?>>⚪ Pendiente</option>
                                <option value="2" <?= $formData['estado'] == 2 ? 'selected' : '' ?>>🔵 En Progreso</option>
                                <option value="3" <?= $formData['estado'] == 3 ? 'selected' : '' ?>>🟢 Completada</option>
                                <option value="4" <?= $formData['estado'] == 4 ? 'selected' : '' ?>>🟠 Bloqueada</option>
                                <option value="5" <?= $formData['estado'] == 5 ? 'selected' : '' ?>>🔴 Cancelada</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Nombre de la Subtarea *</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($formData['nombre']) ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($formData['descripcion']) ?></textarea>
                        </div>
                        
                        <!-- Asignación -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-person me-2"></i>Asignación</h6>
                        </div>
                        
                        <div class="col-md-8">
                            <label class="form-label">Realizada por</label>
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
                                <input type="number" name="horas_estimadas" class="form-control" value="<?= htmlspecialchars($formData['horas_estimadas']) ?>" min="0" step="0.5">
                                <span class="input-group-text">horas</span>
                            </div>
                        </div>
                        
                        <!-- Fechas -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-calendar me-2"></i>Planificación</h6>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Inicio Estimada</label>
                            <input type="date" name="fecha_inicio_estimada" class="form-control" value="<?= htmlspecialchars($formData['fecha_inicio_estimada']) ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Fin Estimada</label>
                            <input type="date" name="fecha_fin_estimada" class="form-control" value="<?= htmlspecialchars($formData['fecha_fin_estimada']) ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Fin Real</label>
                            <input type="date" name="fecha_fin_real" class="form-control" value="<?= htmlspecialchars($formData['fecha_fin_real']) ?>">
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 mt-4">
                            <hr class="my-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if (hasPermission('subtareas', 'eliminar')): ?>
                                    <button type="button" class="btn btn-outline-danger" onclick="confirmarEliminar()">
                                        <i class="bi bi-trash me-1"></i>Eliminar
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?= uiModuleUrl('subtareas', 'ver', ['id' => $id]) ?>" class="btn btn-outline-secondary">Cancelar</a>
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

<!-- SweetAlert2 para confirmaciones -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmarEliminar() {
    Swal.fire({
        title: '¿Eliminar subtarea?',
        text: 'Esta acción marcará la subtarea como cancelada',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= uiModuleUrl('subtareas', 'eliminar', ['id' => $id]) ?>';
        }
    });
}
</script>

