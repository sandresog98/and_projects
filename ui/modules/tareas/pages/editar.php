<?php
/**
 * AND PROJECTS APP - Editar Tarea
 */

if (!hasPermission('tareas', 'editar')) {
    setFlashMessage('error', 'No tiene permisos para editar tareas');
    header('Location: ' . uiModuleUrl('tareas'));
    exit;
}

require_once __DIR__ . '/../models/TareaModel.php';
require_once __DIR__ . '/../../proyectos/models/ProyectoModel.php';

$model = new TareaModel();
$proyectoModel = new ProyectoModel();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setFlashMessage('error', 'Tarea no especificada');
    header('Location: ' . uiModuleUrl('tareas'));
    exit;
}

$tarea = $model->getById($id);

if (!$tarea) {
    setFlashMessage('error', 'Tarea no encontrada');
    header('Location: ' . uiModuleUrl('tareas'));
    exit;
}

$pageTitle = 'Editar: ' . $tarea['nombre'];
$pageSubtitle = 'Modificar información de la tarea';

$proyectos = $proyectoModel->getActiveForSelect();
$colaboradores = $model->getColaboradoresSelect();

$errors = [];
// Obtener dependencia actual
$predecesoraActual = $model->getTareaPredecesora($id);

$formData = [
    'proyecto_id' => $tarea['proyecto_id'],
    'nombre' => $tarea['nombre'],
    'descripcion' => $tarea['descripcion'] ?? '',
    'fecha_inicio_estimada' => $tarea['fecha_inicio_estimada'],
    'fecha_fin_estimada' => $tarea['fecha_fin_estimada'] ?? '',
    'fecha_fin_real' => $tarea['fecha_fin_real'] ?? '',
    'prioridad' => $tarea['prioridad'] ?? '2',
    'estado' => $tarea['estado'],
    'asignado_id' => $tarea['asignado_id'] ?? '',
    'predecesora_id' => $predecesoraActual ? $predecesoraActual['id'] : ''
];

// Obtener tareas predecesoras disponibles
$tareasPredecesoras = $model->getTareasPredecesoras((int)$tarea['proyecto_id'], $id);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_merge($formData, $_POST);
    
    // Validaciones
    if (empty($formData['proyecto_id'])) {
        $errors[] = 'Debe seleccionar un proyecto';
    }
    
    if (empty($formData['nombre'])) {
        $errors[] = 'El nombre de la tarea es obligatorio';
    }
    
    // Si no hay errores, guardar
    if (empty($errors)) {
        try {
            $model->update($id, [
                'proyecto_id' => $formData['proyecto_id'],
                'nombre' => $formData['nombre'],
                'descripcion' => $formData['descripcion'] ?: null,
                'fecha_inicio_estimada' => $formData['fecha_inicio_estimada'] ?: null,
                'fecha_fin_estimada' => $formData['fecha_fin_estimada'] ?: null,
                'fecha_fin_real' => $formData['fecha_fin_real'] ?: null,
                'prioridad' => $formData['prioridad'] ?? 2,
                'estado' => $formData['estado'],
                'asignado_id' => $formData['asignado_id'] ?: null
            ]);
            
            // Actualizar dependencia
            $predecesoraId = !empty($formData['predecesora_id']) ? (int)$formData['predecesora_id'] : null;
            $model->setDependencia($id, $predecesoraId);
            
            setFlashMessage('success', 'Tarea actualizada correctamente');
            header('Location: ' . uiModuleUrl('tareas', 'ver', ['id' => $id]));
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Error al actualizar la tarea: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-pencil me-2"></i>Editar Tarea</h6>
                <a href="<?= uiModuleUrl('tareas', 'ver', ['id' => $id]) ?>" class="btn btn-sm btn-outline-secondary">
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
                            <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-2"></i>Información de la Tarea</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Proyecto *</label>
                            <select name="proyecto_id" class="form-select" required>
                                <option value="">Seleccione un proyecto...</option>
                                <?php foreach ($proyectos as $proy): ?>
                                <option value="<?= $proy['id'] ?>" <?= $formData['proyecto_id'] == $proy['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proy['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="1" <?= $formData['estado'] == 1 ? 'selected' : '' ?>>⚪ Pendiente</option>
                                <option value="2" <?= $formData['estado'] == 2 ? 'selected' : '' ?>>🔵 En Progreso</option>
                                <option value="3" <?= $formData['estado'] == 3 ? 'selected' : '' ?>>🟢 Completada</option>
                                <option value="4" <?= $formData['estado'] == 4 ? 'selected' : '' ?>>🟠 Bloqueada</option>
                                <option value="5" <?= $formData['estado'] == 5 ? 'selected' : '' ?>>🔴 Cancelada</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Prioridad</label>
                            <select name="prioridad" class="form-select">
                                <option value="1" <?= $formData['prioridad'] == 1 ? 'selected' : '' ?>>🟢 Baja</option>
                                <option value="2" <?= $formData['prioridad'] == 2 ? 'selected' : '' ?>>🔵 Media</option>
                                <option value="3" <?= $formData['prioridad'] == 3 ? 'selected' : '' ?>>🟠 Alta</option>
                                <option value="4" <?= $formData['prioridad'] == 4 ? 'selected' : '' ?>>🔴 Urgente</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Nombre de la Tarea *</label>
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
                        
                        <div class="col-md-12">
                            <label class="form-label">Asignar a</label>
                            <select name="asignado_id" class="form-select">
                                <option value="">Sin asignar</option>
                                <?php foreach ($colaboradores as $col): ?>
                                <option value="<?= $col['id'] ?>" <?= $formData['asignado_id'] == $col['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($col['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Dependencias -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-diagram-3 me-2"></i>Dependencias</h6>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Depende de (Tarea Predecesora)</label>
                            <select name="predecesora_id" id="predecesora_id" class="form-select">
                                <option value="">Sin dependencia - Tarea independiente</option>
                                <?php foreach ($tareasPredecesoras as $tareaP): ?>
                                <option value="<?= $tareaP['id'] ?>" <?= $formData['predecesora_id'] == $tareaP['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tareaP['nombre']) ?> 
                                    (<?= $tareaP['estado_texto'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Si selecciona una tarea predecesora, esta tarea no podrá iniciarse hasta que la predecesora esté completada.
                            </small>
                            <?php if ($predecesoraActual && $predecesoraActual['estado'] != 3): ?>
                            <div class="alert alert-warning mt-2 py-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <strong>Bloqueada:</strong> Esta tarea depende de "<?= htmlspecialchars($predecesoraActual['nombre']) ?>" que aún no está completada.
                            </div>
                            <?php endif; ?>
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
                                    <?php if (hasPermission('tareas', 'eliminar')): ?>
                                    <button type="button" class="btn btn-outline-danger" onclick="confirmarEliminar()">
                                        <i class="bi bi-trash me-1"></i>Eliminar
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?= uiModuleUrl('tareas', 'ver', ['id' => $id]) ?>" class="btn btn-outline-secondary">Cancelar</a>
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
        title: '¿Eliminar tarea?',
        text: 'Esta acción marcará la tarea como cancelada',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= uiModuleUrl('tareas', 'eliminar', ['id' => $id]) ?>';
        }
    });
}
</script>

