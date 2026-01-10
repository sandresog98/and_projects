<?php
/**
 * AND PROJECTS APP - Crear Tarea
 */

if (!hasPermission('tareas', 'crear')) {
    setFlashMessage('error', 'No tiene permisos para crear tareas');
    header('Location: ' . uiModuleUrl('tareas'));
    exit;
}

$pageTitle = 'Nueva Tarea';
$pageSubtitle = 'Crear una nueva tarea';

require_once __DIR__ . '/../models/TareaModel.php';
require_once __DIR__ . '/../../proyectos/models/ProyectoModel.php';

$model = new TareaModel();
$proyectoModel = new ProyectoModel();

$proyectos = $proyectoModel->getActiveForSelect();
$colaboradores = $model->getColaboradoresSelect();

$errors = [];
$formData = [
    'proyecto_id' => $_GET['proyecto_id'] ?? '',
    'nombre' => '',
    'descripcion' => '',
    'fecha_inicio_estimada' => date('Y-m-d'),
    'fecha_fin_estimada' => '',
    'prioridad' => '2',
    'asignado_id' => '',
    'predecesora_id' => ''
];

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
            $id = $model->create([
                'proyecto_id' => $formData['proyecto_id'],
                'nombre' => $formData['nombre'],
                'descripcion' => $formData['descripcion'] ?: null,
                'fecha_inicio_estimada' => $formData['fecha_inicio_estimada'] ?: null,
                'fecha_fin_estimada' => $formData['fecha_fin_estimada'] ?: null,
                'prioridad' => $formData['prioridad'] ?? 2,
                'asignado_id' => $formData['asignado_id'] ?: null,
                'creado_por' => getCurrentUserId()
            ]);
            
            // Guardar dependencia si se seleccionó una tarea predecesora
            if (!empty($formData['predecesora_id'])) {
                $model->setDependencia($id, (int)$formData['predecesora_id']);
            }
            
            setFlashMessage('success', 'Tarea creada correctamente');
            
            // Redirigir al proyecto si vino desde allí
            if (!empty($formData['proyecto_id'])) {
                header('Location: ' . uiModuleUrl('proyectos', 'ver', ['id' => $formData['proyecto_id']]));
            } else {
                header('Location: ' . uiModuleUrl('tareas'));
            }
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Error al crear la tarea: ' . $e->getMessage();
        }
    }
}

// Obtener tareas predecesoras si ya hay un proyecto seleccionado
$tareasPredecesoras = [];
if (!empty($formData['proyecto_id'])) {
    $tareasPredecesoras = $model->getTareasPredecesoras((int)$formData['proyecto_id']);
}
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-list-task me-2"></i>Nueva Tarea</h6>
                <a href="<?= uiModuleUrl('tareas') ?>" class="btn btn-sm btn-outline-secondary">
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
                        
                        <div class="col-md-8">
                            <label class="form-label">Proyecto *</label>
                            <select name="proyecto_id" class="form-select" required>
                                <option value="">Seleccione un proyecto...</option>
                                <?php foreach ($proyectos as $proy): ?>
                                <option value="<?= $proy['id'] ?>" <?= $formData['proyecto_id'] == $proy['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proy['nombre']) ?>
                                    <?php if (!empty($proy['empresa_nombre'])): ?>
                                    (<?= htmlspecialchars($proy['empresa_nombre']) ?>)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Prioridad</label>
                            <select name="prioridad" class="form-select">
                                <option value="1" <?= $formData['prioridad'] == '1' ? 'selected' : '' ?>>🟢 Baja</option>
                                <option value="2" <?= $formData['prioridad'] == '2' ? 'selected' : '' ?>>🔵 Media</option>
                                <option value="3" <?= $formData['prioridad'] == '3' ? 'selected' : '' ?>>🟠 Alta</option>
                                <option value="4" <?= $formData['prioridad'] == '4' ? 'selected' : '' ?>>🔴 Urgente</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Nombre de la Tarea *</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($formData['nombre']) ?>" required placeholder="Ej: Diseñar mockups de la página principal">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción detallada de la tarea..."><?= htmlspecialchars($formData['descripcion']) ?></textarea>
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
                                <?php foreach ($tareasPredecesoras as $tarea): ?>
                                <option value="<?= $tarea['id'] ?>" <?= $formData['predecesora_id'] == $tarea['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tarea['nombre']) ?> 
                                    <span class="text-muted">(<?= $tarea['estado_texto'] ?>)</span>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Si selecciona una tarea predecesora, esta tarea no podrá iniciarse hasta que la predecesora esté completada.
                            </small>
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
                                <a href="<?= uiModuleUrl('tareas') ?>" class="btn btn-outline-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-2"></i>Crear Tarea
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
document.addEventListener('DOMContentLoaded', function() {
    const proyectoSelect = document.querySelector('select[name="proyecto_id"]');
    const predecesoraSelect = document.getElementById('predecesora_id');
    
    proyectoSelect.addEventListener('change', function() {
        const proyectoId = this.value;
        
        // Limpiar select de predecesoras
        predecesoraSelect.innerHTML = '<option value="">Cargando...</option>';
        
        if (!proyectoId) {
            predecesoraSelect.innerHTML = '<option value="">Sin dependencia - Tarea independiente</option>';
            return;
        }
        
        // Cargar tareas del proyecto
        fetch('<?= uiModuleUrl('tareas', 'api/predecesoras') ?>&proyecto_id=' + proyectoId)
            .then(response => response.json())
            .then(data => {
                predecesoraSelect.innerHTML = '<option value="">Sin dependencia - Tarea independiente</option>';
                data.forEach(tarea => {
                    const option = document.createElement('option');
                    option.value = tarea.id;
                    option.textContent = tarea.nombre + ' (' + tarea.estado_texto + ')';
                    predecesoraSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                predecesoraSelect.innerHTML = '<option value="">Sin dependencia - Tarea independiente</option>';
            });
    });
});
</script>

