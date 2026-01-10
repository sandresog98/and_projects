<?php
/**
 * AND PROJECTS APP - Listado de Tareas
 */

$pageTitle = 'Tareas';
$pageSubtitle = 'Gestión de tareas';

require_once __DIR__ . '/../models/TareaModel.php';
require_once __DIR__ . '/../../proyectos/models/ProyectoModel.php';
require_once __DIR__ . '/../../empresas/models/EmpresaModel.php';

$model = new TareaModel();
$proyectoModel = new ProyectoModel();
$empresaModel = new EmpresaModel();

// Filtros
$search = $_GET['search'] ?? '';
$empresa_id = $_GET['empresa_id'] ?? '';
$proyecto_id = $_GET['proyecto_id'] ?? '';
$estado = $_GET['estado'] ?? '';

$filters = ['exclude_cancelled' => true];
if ($search) $filters['search'] = $search;
if ($empresa_id) $filters['empresa_id'] = (int)$empresa_id;
if ($proyecto_id) $filters['proyecto_id'] = (int)$proyecto_id;
if ($estado !== '') $filters['estado'] = (int)$estado;

$tareas = $model->getAll($filters);

// Obtener empresas y proyectos para filtros
$empresas = $empresaModel->getAll(['estado' => 1]);
$proyectoFilters = ['exclude_cancelled' => true];
if ($empresa_id) $proyectoFilters['empresa_id'] = (int)$empresa_id;
$proyectos = $proyectoModel->getAll($proyectoFilters);

// Si hay proyecto filtrado, obtener info
$proyectoActual = null;
if ($proyecto_id) {
    $proyectoActual = $proyectoModel->getById($proyecto_id);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
    <div>
        <h5 class="mb-0">
            <?php if ($proyectoActual): ?>
            Tareas de: <?= htmlspecialchars($proyectoActual['nombre']) ?>
            <?php else: ?>
            Listado de Tareas
            <?php endif; ?>
        </h5>
        <small class="text-muted"><?= count($tareas) ?> tareas encontradas</small>
    </div>
    <?php if (hasPermission('tareas', 'crear')): ?>
    <a href="<?= uiModuleUrl('tareas', 'crear', $proyecto_id ? ['proyecto_id' => $proyecto_id] : []) ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i>Nueva Tarea
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card mb-4 fade-in-up">
    <div class="card-body">
        <form method="GET" class="row g-3" id="filtrosTareas">
            <input type="hidden" name="module" value="tareas">
            
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Nombre de la tarea..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Empresa</label>
                <select name="empresa_id" class="form-select auto-submit" data-clear="proyecto_id">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= $empresa_id == $emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Proyecto</label>
                <select name="proyecto_id" class="form-select auto-submit" id="proyecto_id">
                    <option value="">Todos</option>
                    <?php foreach ($proyectos as $proy): ?>
                    <option value="<?= $proy['id'] ?>" <?= $proyecto_id == $proy['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($proy['nombre']) ?>
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
                    <option value="3" <?= $estado === '3' ? 'selected' : '' ?>>Finalizada</option>
                    <option value="4" <?= $estado === '4' ? 'selected' : '' ?>>Bloqueada</option>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <?php if ($search || $empresa_id || $proyecto_id || $estado !== ''): ?>
                <a href="<?= uiModuleUrl('tareas') ?>" class="btn btn-outline-secondary w-100" title="Limpiar filtros">
                    <i class="bi bi-x-lg me-1"></i>Limpiar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.auto-submit').forEach(select => {
    select.addEventListener('change', function() {
        // Si tiene data-clear, limpiar ese campo antes de enviar
        const clearField = this.dataset.clear;
        if (clearField) {
            const fieldToClear = document.getElementById(clearField);
            if (fieldToClear) fieldToClear.value = '';
        }
        document.getElementById('filtrosTareas').submit();
    });
});
</script>

<!-- Listado -->
<div class="card fade-in-up">
    <div class="card-body p-0">
        <?php if (empty($tareas)): ?>
        <div class="text-center py-5">
            <i class="bi bi-list-task text-muted" style="font-size: 48px;"></i>
            <p class="text-muted mt-3">No se encontraron tareas</p>
            <?php if (hasPermission('tareas', 'crear')): ?>
            <a href="<?= uiModuleUrl('tareas', 'crear') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Crear Primera Tarea
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tarea</th>
                        <th>Proyecto</th>
                        <th>Avance</th>
                        <th>Prioridad</th>
                        <th>Estado</th>
                        <th>Asignado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tareas as $tarea): ?>
                    <?php 
                        $avance = $tarea['total_subtareas'] > 0 
                            ? round(($tarea['subtareas_completadas'] / $tarea['total_subtareas']) * 100) 
                            : 0;
                        // Obtener info de dependencia
                        $predecesora = $model->getTareaPredecesora($tarea['id']);
                        $estaBloqueada = $predecesora && $predecesora['estado'] != 3;
                    ?>
                    <tr class="<?= $estaBloqueada ? 'opacity-75' : '' ?>">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($estaBloqueada): ?>
                                <i class="bi bi-lock-fill text-warning"></i>
                                <?php else: ?>
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: <?= $tarea['proyecto_color'] ?>;"></div>
                                <?php endif; ?>
                                <div>
                                    <strong><?= htmlspecialchars($tarea['nombre']) ?></strong>
                                    <?php if ($predecesora): ?>
                                    <br><small class="<?= $estaBloqueada ? 'text-warning' : 'text-muted' ?>">
                                        <i class="bi bi-arrow-return-right me-1"></i>
                                        Depende de: <?= htmlspecialchars($predecesora['nombre']) ?>
                                        <?php if ($estaBloqueada): ?>
                                        <span class="badge bg-warning text-dark ms-1" style="font-size: 10px;">Bloqueada</span>
                                        <?php endif; ?>
                                    </small>
                                    <?php else: ?>
                                    <br><small class="text-muted"><?= $tarea['total_subtareas'] ?> subtareas</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($tarea['empresa_logo'])): ?>
                                <img src="<?= UPLOADS_URL . '/' . $tarea['empresa_logo'] ?>" 
                                     alt="<?= htmlspecialchars($tarea['empresa_nombre']) ?>" 
                                     class="rounded" style="width: 24px; height: 24px; object-fit: contain; background: #fff;">
                                <?php else: ?>
                                <div class="rounded d-flex align-items-center justify-content-center" 
                                     style="width: 24px; height: 24px; background: var(--bg-tertiary);">
                                    <i class="bi bi-building text-muted" style="font-size: 12px;"></i>
                                </div>
                                <?php endif; ?>
                                <a href="<?= uiModuleUrl('proyectos', 'ver', ['id' => $tarea['proyecto_id']]) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($tarea['proyecto_nombre']) ?>
                                </a>
                            </div>
                        </td>
                        <td style="min-width: 120px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar" style="width: <?= $avance ?>%"></div>
                                </div>
                                <small class="text-muted"><?= $avance ?>%</small>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-priority-<?= $tarea['prioridad'] ?>">
                                <?= getPriorityText($tarea['prioridad']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-status-<?= $tarea['estado'] ?>">
                                <?= getStatusText($tarea['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $tarea['asignado_nombre'] ? htmlspecialchars($tarea['asignado_nombre']) : '<span class="text-muted">Sin asignar</span>' ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= uiModuleUrl('tareas', 'ver', ['id' => $tarea['id']]) ?>" 
                                   class="btn-icon btn-icon-sm" title="Ver detalles" data-bs-toggle="tooltip">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (hasPermission('tareas', 'editar')): ?>
                                <a href="<?= uiModuleUrl('tareas', 'editar', ['id' => $tarea['id']]) ?>" 
                                   class="btn-icon btn-icon-sm btn-icon-primary" title="Editar" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($proyecto_id && $proyectoActual): ?>
<!-- Árbol de dependencias -->
<div class="card mt-4 fade-in-up">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Árbol de Dependencias</h6>
    </div>
    <div class="card-body">
        <?php 
        $arbol = $model->getArbolDependencias($proyecto_id);
        
        function renderArbol($nodos, $nivel = 0) {
            if (empty($nodos)) return;
            echo '<ul class="list-unstyled" style="margin-left: ' . ($nivel * 25) . 'px;">';
            foreach ($nodos as $nodo) {
                $colorClass = match($nodo['estado']) {
                    3 => 'text-success',
                    2 => 'text-primary',
                    4 => 'text-warning',
                    default => 'text-secondary'
                };
                $icon = match($nodo['estado']) {
                    3 => 'check-circle-fill',
                    2 => 'arrow-right-circle',
                    4 => 'pause-circle',
                    default => 'circle'
                };
                echo '<li class="mb-2">';
                echo '<i class="bi bi-' . $icon . ' ' . $colorClass . ' me-2"></i>';
                echo '<span class="' . $colorClass . '">' . htmlspecialchars($nodo['nombre']) . '</span>';
                echo ' <span class="badge badge-status-' . $nodo['estado'] . ' ms-2">' . $nodo['avance'] . '%</span>';
                
                if (!empty($nodo['hijos'])) {
                    renderArbol($nodo['hijos'], $nivel + 1);
                }
                echo '</li>';
            }
            echo '</ul>';
        }
        
        if (empty($arbol)): ?>
        <p class="text-muted mb-0">No hay tareas con dependencias definidas</p>
        <?php else:
            renderArbol($arbol);
        endif; ?>
    </div>
</div>
<?php endif; ?>

