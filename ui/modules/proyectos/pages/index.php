<?php
/**
 * AND PROJECTS APP - Listado de Proyectos
 */

$pageTitle = 'Proyectos';
$pageSubtitle = 'Gestión de proyectos';

require_once __DIR__ . '/../models/ProyectoModel.php';
require_once __DIR__ . '/../../empresas/models/EmpresaModel.php';

$model = new ProyectoModel();
$empresaModel = new EmpresaModel();

// Filtros
$search = $_GET['search'] ?? '';
$empresa_id = $_GET['empresa_id'] ?? '';
$estado = $_GET['estado'] ?? '';

$filters = ['exclude_cancelled' => true];
if ($search) $filters['search'] = $search;
if ($empresa_id) $filters['empresa_id'] = (int)$empresa_id;
if ($estado !== '') $filters['estado'] = (int)$estado;

$proyectos = $model->getAll($filters);
$empresas = $empresaModel->getActiveForSelect();
?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
    <div>
        <h5 class="mb-0">Listado de Proyectos</h5>
        <small class="text-muted"><?= count($proyectos) ?> proyectos encontrados</small>
    </div>
    <?php if (hasPermission('proyectos', 'crear')): ?>
    <a href="<?= uiModuleUrl('proyectos', 'crear') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i>Nuevo Proyecto
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card mb-4 fade-in-up">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="module" value="proyectos">
            
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-control" placeholder="Nombre o código..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Empresa</label>
                <select name="empresa_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= $empresa_id == $emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="2" <?= $estado === '2' ? 'selected' : '' ?>>En Progreso</option>
                    <option value="3" <?= $estado === '3' ? 'selected' : '' ?>>Finalizado</option>
                    <option value="4" <?= $estado === '4' ? 'selected' : '' ?>>Pausado</option>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Listado de proyectos como cards -->
<div class="row g-4">
    <?php if (empty($proyectos)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-folder text-muted" style="font-size: 48px;"></i>
                <p class="text-muted mt-3">No se encontraron proyectos</p>
                <?php if (hasPermission('proyectos', 'crear')): ?>
                <a href="<?= uiModuleUrl('proyectos', 'crear') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Crear Primer Proyecto
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($proyectos as $proyecto): ?>
    <?php 
        $totalTareas = $proyecto['total_tareas'] ?? 0;
        $tareasCompletadas = $proyecto['tareas_completadas'] ?? 0;
        $avance = $totalTareas > 0 
            ? round(($tareasCompletadas / $totalTareas) * 100) 
            : (float)($proyecto['avance'] ?? 0);
    ?>
    <div class="col-md-6 col-xl-4 fade-in-up">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-start" style="border-left: 4px solid <?= $proyecto['color'] ?? '#55A5C8' ?>;">
                <div>
                    <h6 class="mb-1"><?= htmlspecialchars($proyecto['nombre']) ?></h6>
                    <small class="text-muted">ID: <?= $proyecto['id'] ?></small>
                </div>
                <span class="badge badge-status-<?= $proyecto['estado'] ?>">
                    <?= getStatusText($proyecto['estado']) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <?php if (!empty($proyecto['empresa_logo'])): ?>
                    <img src="<?= UPLOADS_URL . '/' . $proyecto['empresa_logo'] ?>" 
                         alt="<?= htmlspecialchars($proyecto['empresa_nombre']) ?>" 
                         class="rounded" style="width: 28px; height: 28px; object-fit: contain; background: #fff;">
                    <?php else: ?>
                    <div class="rounded d-flex align-items-center justify-content-center" 
                         style="width: 28px; height: 28px; background: var(--bg-tertiary);">
                        <i class="bi bi-building text-muted small"></i>
                    </div>
                    <?php endif; ?>
                    <span class="text-muted small"><?= htmlspecialchars($proyecto['empresa_nombre'] ?? 'Sin empresa') ?></span>
                </div>
                
                <!-- Avance -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Avance</small>
                        <small class="text-muted"><?= $avance ?>%</small>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= $avance ?>%"></div>
                    </div>
                </div>
                
                <!-- Info adicional -->
                <div class="d-flex gap-3 text-muted small mb-3">
                    <span><i class="bi bi-list-task me-1"></i><?= $proyecto['total_tareas'] ?? 0 ?> tareas</span>
                    <span><i class="bi bi-check-circle me-1"></i><?= $proyecto['tareas_completadas'] ?? 0 ?> completadas</span>
                </div>
                
                <?php if (!empty($proyecto['fecha_fin_estimada'])): ?>
                <div class="text-muted small">
                    <i class="bi bi-calendar me-1"></i>
                    Entrega: <?= formatDate($proyecto['fecha_fin_estimada']) ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent border-top d-flex justify-content-between">
                <a href="<?= uiModuleUrl('proyectos', 'ver', ['id' => $proyecto['id']]) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>Ver
                </a>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?= uiModuleUrl('tareas', 'index', ['proyecto_id' => $proyecto['id']]) ?>">
                                <i class="bi bi-list-task me-2"></i>Ver tareas
                            </a>
                        </li>
                        <?php if (hasPermission('proyectos', 'editar')): ?>
                        <li>
                            <a class="dropdown-item" href="<?= uiModuleUrl('proyectos', 'editar', ['id' => $proyecto['id']]) ?>">
                                <i class="bi bi-pencil me-2"></i>Editar
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

