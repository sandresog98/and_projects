<?php
/**
 * AND PROJECTS APP - Dashboard para Colaboradores (UI)
 */

require_once __DIR__ . '/../modules/proyectos/models/ProyectoModel.php';
require_once __DIR__ . '/../modules/tareas/models/TareaModel.php';
require_once __DIR__ . '/../modules/reuniones/models/ReunionModel.php';
require_once __DIR__ . '/../modules/empresas/models/EmpresaModel.php';

$proyectoModel = new ProyectoModel();
$tareaModel = new TareaModel();
$reunionModel = new ReunionModel();
$empresaModel = new EmpresaModel();

$currentUser = getCurrentUser();

// Estadísticas generales
$proyectos = $proyectoModel->getAll(['exclude_cancelled' => true]);
$tareas = $tareaModel->getAll(['exclude_cancelled' => true]);
$empresas = $empresaModel->getActiveForSelect();

// Contar estados
$proyectosActivos = count(array_filter($proyectos, fn($p) => $p['estado'] == 2));
$proyectosCompletados = count(array_filter($proyectos, fn($p) => $p['estado'] == 3));
$tareasEnProgreso = count(array_filter($tareas, fn($t) => $t['estado'] == 2));
$tareasCompletadasHoy = 0; // Calcular si es necesario

// Reuniones de hoy
$reunionesHoy = $reunionModel->getHoy();
$reunionesProximas = $reunionModel->getProximas(7);

// Proyectos recientes
$proyectosRecientes = array_slice($proyectos, 0, 5);
?>

<!-- Bienvenida -->
<div class="row mb-4 fade-in-up">
    <div class="col-12">
        <h4 class="mb-1">¡Hola, <?= htmlspecialchars(explode(' ', $currentUser['nombre'])[0]) ?>!</h4>
        <p class="text-muted mb-0">Aquí tienes el resumen de tus proyectos y actividades</p>
    </div>
</div>

<!-- Estadísticas principales -->
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.1s">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value text-primary"><?= count($proyectos) ?></div>
                        <div class="stat-label">Proyectos</div>
                    </div>
                    <div class="p-2 rounded-3" style="background: rgba(85, 165, 200, 0.15);">
                        <i class="bi bi-briefcase text-primary" style="font-size: 24px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.2s">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value" style="color: var(--secondary-green);"><?= $proyectosActivos ?></div>
                        <div class="stat-label">En Progreso</div>
                    </div>
                    <div class="p-2 rounded-3" style="background: rgba(154, 208, 130, 0.15);">
                        <i class="bi bi-play-circle" style="font-size: 24px; color: var(--secondary-green);"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.3s">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value"><?= count($tareas) ?></div>
                        <div class="stat-label">Tareas</div>
                    </div>
                    <div class="p-2 rounded-3" style="background: rgba(106, 13, 173, 0.15);">
                        <i class="bi bi-list-task" style="font-size: 24px; color: var(--purple-accent);"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.4s">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value"><?= count($empresas) ?></div>
                        <div class="stat-label">Empresas</div>
                    </div>
                    <div class="p-2 rounded-3" style="background: rgba(177, 188, 191, 0.15);">
                        <i class="bi bi-building" style="font-size: 24px; color: var(--tertiary-gray);"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Proyectos recientes -->
    <div class="col-lg-8 fade-in-up" style="animation-delay: 0.5s">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-kanban me-2"></i>Proyectos Recientes</h6>
                <a href="<?= uiModuleUrl('proyectos') ?>" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($proyectosRecientes)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-folder-x text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">No hay proyectos</p>
                    <?php if (hasPermission('proyectos', 'crear')): ?>
                    <a href="<?= uiModuleUrl('proyectos', 'crear') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>Crear Proyecto
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Proyecto</th>
                                <th>Empresa</th>
                                <th>Avance</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proyectosRecientes as $proyecto): ?>
                            <tr class="cursor-pointer" onclick="window.location='<?= uiModuleUrl('proyectos', 'ver', ['id' => $proyecto['id']]) ?>'">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 10px; height: 10px; border-radius: 50%; background: <?= $proyecto['color'] ?? '#55A5C8' ?>;"></div>
                                        <strong><?= htmlspecialchars($proyecto['nombre']) ?></strong>
                                    </div>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($proyecto['empresa_nombre'] ?? '-') ?></td>
                                <td style="width: 150px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1">
                                            <div class="progress-bar" style="width: <?= $proyecto['avance'] ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $proyecto['avance'] ?>%</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-status-<?= $proyecto['estado'] ?>">
                                        <?= getStatusText($proyecto['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Reuniones -->
    <div class="col-lg-4 fade-in-up" style="animation-delay: 0.6s">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Reuniones Hoy</h6>
                <a href="<?= uiModuleUrl('reuniones') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-calendar3"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($reunionesHoy)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-calendar-check text-muted" style="font-size: 36px;"></i>
                    <p class="text-muted mt-2 mb-0 small">Sin reuniones para hoy</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($reunionesHoy as $reunion): ?>
                    <a href="<?= uiModuleUrl('reuniones', 'ver', ['id' => $reunion['id']]) ?>" class="list-group-item list-group-item-action bg-transparent">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="d-block"><?= htmlspecialchars($reunion['titulo']) ?></strong>
                                <small class="text-muted"><?= $reunion['empresa_nombre'] ?? $reunion['proyecto_nombre'] ?? 'General' ?></small>
                            </div>
                            <span class="badge bg-primary"><?= date('H:i', strtotime($reunion['hora_inicio'])) ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($reunionesProximas)): ?>
            <div class="card-footer">
                <small class="text-muted">
                    <i class="bi bi-clock me-1"></i>
                    <?= count($reunionesProximas) ?> reuniones en los próximos 7 días
                </small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Accesos rápidos -->
<?php if (hasPermission('proyectos', 'crear') || hasPermission('tareas', 'crear') || hasPermission('reuniones', 'crear')): ?>
<div class="row mt-4">
    <div class="col-12 fade-in-up" style="animation-delay: 0.7s">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap gap-2">
                    <span class="text-muted me-2 d-flex align-items-center">
                        <i class="bi bi-lightning me-1"></i> Accesos rápidos:
                    </span>
                    <?php if (hasPermission('proyectos', 'crear')): ?>
                    <a href="<?= uiModuleUrl('proyectos', 'crear') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus me-1"></i>Nuevo Proyecto
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('tareas', 'crear')): ?>
                    <a href="<?= uiModuleUrl('tareas', 'crear') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus me-1"></i>Nueva Tarea
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('reuniones', 'crear')): ?>
                    <a href="<?= uiModuleUrl('reuniones', 'crear') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus me-1"></i>Nueva Reunión
                    </a>
                    <?php endif; ?>
                    <?php if (hasPermission('empresas', 'crear')): ?>
                    <a href="<?= uiModuleUrl('empresas', 'crear') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-plus me-1"></i>Nueva Empresa
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
