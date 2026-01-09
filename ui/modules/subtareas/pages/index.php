<?php
/**
 * AND PROJECTS APP - Listado de Subtareas
 */

$pageTitle = 'Subtareas';
$pageSubtitle = 'Gestión de subtareas y tracking de tiempo';

require_once __DIR__ . '/../models/SubtareaModel.php';
require_once __DIR__ . '/../../tareas/models/TareaModel.php';
require_once __DIR__ . '/../../proyectos/models/ProyectoModel.php';
require_once __DIR__ . '/../../empresas/models/EmpresaModel.php';

$model = new SubtareaModel();
$tareaModel = new TareaModel();
$proyectoModel = new ProyectoModel();
$empresaModel = new EmpresaModel();

// Filtros
$search = $_GET['search'] ?? '';
$empresa_id = $_GET['empresa_id'] ?? '';
$proyecto_id = $_GET['proyecto_id'] ?? '';
$tarea_id = $_GET['tarea_id'] ?? '';
$estado = $_GET['estado'] ?? '';

$filters = ['exclude_cancelled' => true];
if ($search) $filters['search'] = $search;
if ($empresa_id) $filters['empresa_id'] = (int)$empresa_id;
if ($proyecto_id) $filters['proyecto_id'] = (int)$proyecto_id;
if ($tarea_id) $filters['tarea_id'] = (int)$tarea_id;
if ($estado !== '') $filters['estado'] = (int)$estado;

$subtareas = $model->getAll($filters);

// Obtener datos para filtros
$empresas = $empresaModel->getAll(['estado' => 1]);

$proyectoFilters = ['exclude_cancelled' => true];
if ($empresa_id) $proyectoFilters['empresa_id'] = (int)$empresa_id;
$proyectos = $proyectoModel->getAll($proyectoFilters);

$tareaFilters = ['exclude_cancelled' => true];
if ($proyecto_id) $tareaFilters['proyecto_id'] = (int)$proyecto_id;
$tareas = $tareaModel->getAll($tareaFilters);

// Si hay tarea filtrada, obtener info
$tareaActual = null;
if ($tarea_id) {
    $tareaActual = $tareaModel->getById($tarea_id);
    $estadisticas = $model->getEstadisticasPorTarea($tarea_id);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
    <div>
        <h5 class="mb-0">
            <?php if ($tareaActual): ?>
            Subtareas de: <?= htmlspecialchars($tareaActual['nombre']) ?>
            <?php else: ?>
            Listado de Subtareas
            <?php endif; ?>
        </h5>
        <small class="text-muted"><?= count($subtareas) ?> subtareas</small>
    </div>
    <div class="d-flex gap-2">
        <?php if ($tareaActual): ?>
        <a href="<?= uiModuleUrl('tareas', 'ver', ['id' => $tarea_id]) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver a Tarea
        </a>
        <?php endif; ?>
        <?php if (hasPermission('subtareas', 'crear')): ?>
        <a href="<?= uiModuleUrl('subtareas', 'crear', $tarea_id ? ['tarea_id' => $tarea_id] : []) ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Nueva Subtarea
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4 fade-in-up">
    <div class="card-body">
        <form method="GET" class="row g-3" id="filtrosSubtareas">
            <input type="hidden" name="module" value="subtareas">
            
            <div class="col-md-2">
                <label class="form-label">Buscar</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Nombre..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Empresa</label>
                <select name="empresa_id" class="form-select auto-submit" data-clear="proyecto_id,tarea_id">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= $empresa_id == $emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Proyecto</label>
                <select name="proyecto_id" class="form-select auto-submit" id="proyecto_id" data-clear="tarea_id">
                    <option value="">Todos</option>
                    <?php foreach ($proyectos as $proy): ?>
                    <option value="<?= $proy['id'] ?>" <?= $proyecto_id == $proy['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($proy['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Tarea</label>
                <select name="tarea_id" class="form-select auto-submit" id="tarea_id">
                    <option value="">Todas</option>
                    <?php foreach ($tareas as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $tarea_id == $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select auto-submit">
                    <option value="">Todos</option>
                    <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="2" <?= $estado === '2' ? 'selected' : '' ?>>En Progreso</option>
                    <option value="3" <?= $estado === '3' ? 'selected' : '' ?>>Completada</option>
                    <option value="4" <?= $estado === '4' ? 'selected' : '' ?>>Bloqueada</option>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <?php if ($search || $empresa_id || $proyecto_id || $tarea_id || $estado !== ''): ?>
                <a href="<?= uiModuleUrl('subtareas') ?>" class="btn btn-outline-secondary w-100" title="Limpiar filtros">
                    <i class="bi bi-x-lg me-1"></i>Limpiar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($tareaActual && isset($estadisticas)): ?>
<!-- Estadísticas de la tarea -->
<div class="row g-3 mb-4 fade-in-up">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value"><?= $estadisticas['total'] ?></div>
                <div class="stat-label">Total Subtareas</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value text-success"><?= $estadisticas['completadas'] ?></div>
                <div class="stat-label">Completadas</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value text-primary"><?= $estadisticas['en_progreso'] ?></div>
                <div class="stat-label">En Progreso</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value"><?= round($estadisticas['horas_reales_total'], 1) ?>h</div>
                <div class="stat-label">Horas Registradas</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Listado -->
<div class="card fade-in-up">
    <div class="card-body p-0">
        <?php if (empty($subtareas)): ?>
        <div class="text-center py-5">
            <i class="bi bi-list-check text-muted" style="font-size: 48px;"></i>
            <p class="text-muted mt-3">No hay subtareas</p>
            <?php if (hasPermission('subtareas', 'crear') && $tarea_id): ?>
            <a href="<?= uiModuleUrl('subtareas', 'crear', ['tarea_id' => $tarea_id]) ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Crear Primera Subtarea
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($subtareas as $subtarea): ?>
            <div class="list-group-item bg-transparent">
                <div class="d-flex align-items-start">
                    <!-- Checkbox de completado -->
                    <div class="me-3">
                        <?php if ($subtarea['estado'] == 3): ?>
                        <button class="btn btn-sm btn-success rounded-circle" style="width: 32px; height: 32px;" 
                                onclick="cambiarEstado(<?= $subtarea['id'] ?>, 2)" title="Marcar como pendiente">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary rounded-circle" style="width: 32px; height: 32px;"
                                onclick="cambiarEstado(<?= $subtarea['id'] ?>, 3)" title="Marcar como completada">
                            <i class="bi bi-check"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contenido -->
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 <?= $subtarea['estado'] == 3 ? 'text-decoration-line-through text-muted' : '' ?>">
                                    <?= htmlspecialchars($subtarea['nombre']) ?>
                                </h6>
                                <?php if (!$tarea_id): ?>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <?php if (!empty($subtarea['empresa_logo'])): ?>
                                    <img src="<?= UPLOADS_URL . '/' . $subtarea['empresa_logo'] ?>" 
                                         alt="<?= htmlspecialchars($subtarea['empresa_nombre']) ?>" 
                                         class="rounded" style="width: 18px; height: 18px; object-fit: contain; background: #fff;">
                                    <?php else: ?>
                                    <div class="rounded d-flex align-items-center justify-content-center" 
                                         style="width: 18px; height: 18px; background: var(--bg-tertiary);">
                                        <i class="bi bi-building text-muted" style="font-size: 10px;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($subtarea['tarea_nombre']) ?> &bull; <?= htmlspecialchars($subtarea['proyecto_nombre']) ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge badge-status-<?= $subtarea['estado'] ?>">
                                    <?= getStatusText($subtarea['estado']) ?>
                                </span>
                                
                                <!-- Botones de acción -->
                                <div class="d-flex gap-1">
                                    <a href="<?= uiModuleUrl('subtareas', 'ver', ['id' => $subtarea['id']]) ?>" 
                                       class="btn-icon btn-icon-sm" title="Ver detalles" data-bs-toggle="tooltip">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (hasPermission('subtareas', 'editar')): ?>
                                    <a href="<?= uiModuleUrl('subtareas', 'editar', ['id' => $subtarea['id']]) ?>" 
                                       class="btn-icon btn-icon-sm btn-icon-primary" title="Editar" data-bs-toggle="tooltip">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn-icon btn-icon-sm btn-icon-success" 
                                            data-bs-toggle="modal" data-bs-target="#modalTiempo<?= $subtarea['id'] ?>" title="Registrar tiempo">
                                        <i class="bi bi-clock"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Info adicional -->
                        <div class="mt-2 d-flex flex-wrap gap-3 text-muted small">
                            <?php if ($subtarea['fecha_fin_estimada']): ?>
                            <span>
                                <i class="bi bi-calendar me-1"></i>
                                Entrega: <?= formatDate($subtarea['fecha_fin_estimada']) ?>
                            </span>
                            <?php endif; ?>
                            
                            <span>
                                <i class="bi bi-clock me-1"></i>
                                <?= $subtarea['horas_reales'] ? round($subtarea['horas_reales'], 1) . 'h registradas' : 'Sin tiempo' ?>
                                <?php if ($subtarea['horas_estimadas']): ?>
                                / <?= $subtarea['horas_estimadas'] ?>h estimadas
                                <?php endif; ?>
                            </span>
                            
                            <?php if ($subtarea['realizado_por_nombre']): ?>
                            <span>
                                <i class="bi bi-person me-1"></i>
                                <?= htmlspecialchars($subtarea['realizado_por_nombre']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal de registro de tiempo -->
            <div class="modal fade" id="modalTiempo<?= $subtarea['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="<?= uiModuleUrl('subtareas', 'registrar-tiempo') ?>" method="POST">
                            <input type="hidden" name="subtarea_id" value="<?= $subtarea['id'] ?>">
                            <input type="hidden" name="redirect" value="<?= $_SERVER['REQUEST_URI'] ?>">
                            
                            <div class="modal-header">
                                <h5 class="modal-title">Registrar Tiempo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small">Subtarea: <?= htmlspecialchars($subtarea['nombre']) ?></p>
                                
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label">Horas</label>
                                        <input type="number" name="horas" class="form-control" min="0" max="24" value="0" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Minutos</label>
                                        <input type="number" name="minutos" class="form-control" min="0" max="59" value="0" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Fecha</label>
                                        <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Descripción (opcional)</label>
                                        <textarea name="descripcion" class="form-control" rows="2" placeholder="¿Qué hiciste?"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-clock me-2"></i>Registrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-submit en filtros
document.querySelectorAll('.auto-submit').forEach(select => {
    select.addEventListener('change', function() {
        // Si tiene data-clear, limpiar esos campos antes de enviar
        const clearFields = this.dataset.clear;
        if (clearFields) {
            clearFields.split(',').forEach(fieldId => {
                const fieldToClear = document.getElementById(fieldId.trim());
                if (fieldToClear) fieldToClear.value = '';
            });
        }
        document.getElementById('filtrosSubtareas').submit();
    });
});

function cambiarEstado(subtareaId, nuevoEstado) {
    fetch('<?= uiModuleUrl('subtareas', 'cambiar-estado') ?>&id=' + subtareaId + '&estado=' + nuevoEstado, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showToast(data.message || 'Error al cambiar estado', 'error');
        }
    })
    .catch(err => {
        showToast('Error de conexión', 'error');
    });
}
</script>

