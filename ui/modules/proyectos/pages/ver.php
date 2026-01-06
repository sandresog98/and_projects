<?php
/**
 * AND PROJECTS APP - Ver Proyecto
 */

require_once __DIR__ . '/../models/ProyectoModel.php';
require_once __DIR__ . '/../../tareas/models/TareaModel.php';
require_once __DIR__ . '/../../subtareas/models/SubtareaModel.php';
require_once __DIR__ . '/../../comentarios/models/ComentarioModel.php';

$model = new ProyectoModel();
$tareaModel = new TareaModel();
$subtareaModel = new SubtareaModel();
$comentarioModel = new ComentarioModel();

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

$pageTitle = $proyecto['nombre'];
$pageSubtitle = 'Detalles del proyecto';

// Obtener tareas del proyecto
$tareas = $tareaModel->getByProyecto($id);

// Obtener comentarios
$comentarios = $comentarioModel->getByEntidad('proyecto', $id);

// Calcular estadísticas
$totalTareas = count($tareas);
$tareasCompletadas = count(array_filter($tareas, fn($t) => $t['estado'] == 3));
$tareasEnProgreso = count(array_filter($tareas, fn($t) => $t['estado'] == 2));
$tareasPendientes = count(array_filter($tareas, fn($t) => $t['estado'] == 1));
$avance = $totalTareas > 0 ? round(($tareasCompletadas / $totalTareas) * 100) : 0;

// Procesar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
    $comentarioTexto = trim($_POST['comentario']);
    if (!empty($comentarioTexto)) {
        $comentarioModel->create([
            'tipo_entidad' => 'proyecto',
            'entidad_id' => $id,
            'usuario_id' => getCurrentUserId(),
            'comentario' => $comentarioTexto
        ]);
        setFlashMessage('success', 'Comentario agregado');
        header('Location: ' . uiModuleUrl('proyectos', 'ver', ['id' => $id]));
        exit;
    }
}
?>

<div class="d-flex justify-content-between align-items-start mb-4 fade-in-up">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <div style="width: 16px; height: 16px; border-radius: 4px; background: <?= $proyecto['color'] ?? '#55A5C8' ?>;"></div>
            <h4 class="mb-0"><?= htmlspecialchars($proyecto['nombre']) ?></h4>
            <span class="badge badge-status-<?= $proyecto['estado'] ?>"><?= getStatusText($proyecto['estado']) ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($proyecto['empresa_logo'])): ?>
            <img src="<?= UPLOADS_URL . '/' . $proyecto['empresa_logo'] ?>" 
                 alt="<?= htmlspecialchars($proyecto['empresa_nombre']) ?>" 
                 class="rounded" style="width: 24px; height: 24px; object-fit: contain; background: #fff;">
            <?php else: ?>
            <div class="rounded d-flex align-items-center justify-content-center" 
                 style="width: 24px; height: 24px; background: var(--bg-tertiary);">
                <i class="bi bi-building text-muted" style="font-size: 12px;"></i>
            </div>
            <?php endif; ?>
            <span class="text-muted"><?= htmlspecialchars($proyecto['empresa_nombre'] ?? 'Sin empresa') ?></span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('proyectos', 'editar')): ?>
        <a href="<?= uiModuleUrl('proyectos', 'editar', ['id' => $id]) ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-2"></i>Editar
        </a>
        <?php endif; ?>
        <a href="<?= uiModuleUrl('proyectos') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<!-- Estadísticas -->
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.1s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value text-primary"><?= $avance ?>%</div>
                <div class="stat-label">Avance</div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar" style="width: <?= $avance ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.2s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value"><?= $totalTareas ?></div>
                <div class="stat-label">Total Tareas</div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.3s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value" style="color: var(--secondary-green);"><?= $tareasCompletadas ?></div>
                <div class="stat-label">Completadas</div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.4s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value" style="color: var(--primary-blue);"><?= $tareasEnProgreso ?></div>
                <div class="stat-label">En Progreso</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Información del proyecto -->
    <div class="col-lg-8">
        <!-- Descripción -->
        <div class="card mb-4 fade-in-up" style="animation-delay: 0.5s">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Descripción</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($proyecto['descripcion'])): ?>
                <p class="mb-0"><?= nl2br(htmlspecialchars($proyecto['descripcion'])) ?></p>
                <?php else: ?>
                <p class="text-muted mb-0">Sin descripción</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tareas -->
        <div class="card fade-in-up" style="animation-delay: 0.6s">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-list-task me-2"></i>Tareas</h6>
                <?php if (hasPermission('tareas', 'crear')): ?>
                <a href="<?= uiModuleUrl('tareas', 'crear', ['proyecto_id' => $id]) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Nueva Tarea
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tareas)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-list-task text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">No hay tareas en este proyecto</p>
                    <?php if (hasPermission('tareas', 'crear')): ?>
                    <a href="<?= uiModuleUrl('tareas', 'crear', ['proyecto_id' => $id]) ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>Crear Primera Tarea
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($tareas as $tarea): ?>
                    <?php 
                        $avanceTarea = (float)($tarea['avance'] ?? 0); 
                        $subtareas = $subtareaModel->getByTarea($tarea['id']);
                        $totalSub = count($subtareas);
                        $subCompletadas = count(array_filter($subtareas, fn($s) => $s['estado'] == 3));
                    ?>
                    <div class="list-group-item bg-transparent px-3 py-3">
                        <!-- Tarea Header -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3 flex-grow-1">
                                <?php if ($tarea['estado'] == 3): ?>
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                <?php elseif ($tarea['estado'] == 2): ?>
                                <i class="bi bi-circle-half text-primary fs-5"></i>
                                <?php else: ?>
                                <i class="bi bi-circle text-muted fs-5"></i>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <a href="<?= uiModuleUrl('tareas', 'ver', ['id' => $tarea['id']]) ?>" class="text-decoration-none">
                                        <strong><?= htmlspecialchars($tarea['nombre']) ?></strong>
                                    </a>
                                    <div class="d-flex align-items-center gap-3 mt-1">
                                        <span class="badge badge-status-<?= $tarea['estado'] ?> badge-sm">
                                            <?= getStatusText($tarea['estado']) ?>
                                        </span>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar me-1"></i><?= !empty($tarea['fecha_fin_estimada']) ? formatDate($tarea['fecha_fin_estimada']) : 'Sin fecha' ?>
                                        </small>
                                        <?php if ($totalSub > 0): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-list-check me-1"></i><?= $subCompletadas ?>/<?= $totalSub ?> subtareas
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2" style="width: 100px;">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar" style="width: <?= $avanceTarea ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= round($avanceTarea) ?>%</small>
                                </div>
                                <a href="<?= uiModuleUrl('tareas', 'ver', ['id' => $tarea['id']]) ?>" class="btn btn-sm btn-outline-secondary" title="Ver tarea">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Subtareas -->
                        <?php if (!empty($subtareas)): ?>
                        <div class="subtareas-list mt-3 ms-4 ps-3" style="border-left: 2px solid var(--border-color);">
                            <?php foreach ($subtareas as $subtarea): ?>
                            <div class="d-flex align-items-center justify-content-between py-1">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($subtarea['estado'] == 3): ?>
                                    <i class="bi bi-check-circle-fill text-success small"></i>
                                    <?php elseif ($subtarea['estado'] == 2): ?>
                                    <i class="bi bi-circle-half text-primary small"></i>
                                    <?php else: ?>
                                    <i class="bi bi-circle text-muted small"></i>
                                    <?php endif; ?>
                                    <a href="<?= uiModuleUrl('subtareas', 'ver', ['id' => $subtarea['id']]) ?>" 
                                       class="small text-decoration-none <?= $subtarea['estado'] == 3 ? 'text-muted text-decoration-line-through' : '' ?>">
                                        <?= htmlspecialchars($subtarea['nombre']) ?>
                                    </a>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($subtarea['horas_reales'] > 0): ?>
                                    <small class="text-muted"><?= number_format($subtarea['horas_reales'], 1) ?>h</small>
                                    <?php endif; ?>
                                    <?php if (hasPermission('subtareas', 'editar')): ?>
                                    <a href="<?= uiModuleUrl('subtareas', 'editar', ['id' => $subtarea['id']]) ?>" 
                                       class="btn btn-sm btn-link p-0 text-muted" title="Editar">
                                        <i class="bi bi-pencil small"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Detalles -->
        <div class="card mb-4 fade-in-up" style="animation-delay: 0.5s">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Detalles</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha de Inicio</small>
                    <strong><?= !empty($proyecto['fecha_inicio']) ? formatDate($proyecto['fecha_inicio']) : 'No definida' ?></strong>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha Estimada de Fin</small>
                    <strong><?= !empty($proyecto['fecha_fin_estimada']) ? formatDate($proyecto['fecha_fin_estimada']) : 'No definida' ?></strong>
                </div>
                
                <?php if (!empty($proyecto['fecha_fin_real'])): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha Real de Fin</small>
                    <strong><?= formatDate($proyecto['fecha_fin_real']) ?></strong>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Creado por</small>
                    <strong><?= htmlspecialchars($proyecto['creador_nombre'] ?? 'Sistema') ?></strong>
                </div>
                
                <div>
                    <small class="text-muted d-block">Última actualización</small>
                    <strong><?= formatDateTime($proyecto['fecha_actualizacion']) ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Comentarios -->
        <div class="card fade-in-up" style="animation-delay: 0.6s">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Comentarios (<?= count($comentarios) ?>)</h6>
            </div>
            <div class="card-body">
                <?php if (hasPermission('comentarios', 'crear')): ?>
                <form method="POST" class="mb-4">
                    <textarea name="comentario" class="form-control mb-2" rows="2" placeholder="Escribe un comentario..." required></textarea>
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-send me-1"></i>Enviar
                    </button>
                </form>
                <?php endif; ?>
                
                <?php if (empty($comentarios)): ?>
                <p class="text-muted text-center mb-0">No hay comentarios</p>
                <?php else: ?>
                <div class="comments-list" style="max-height: 400px; overflow-y: auto;">
                    <?php foreach ($comentarios as $comentario): ?>
                    <div class="comment-item mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between mb-1">
                            <strong class="small"><?= htmlspecialchars($comentario['usuario_nombre'] ?? 'Usuario') ?></strong>
                            <small class="text-muted"><?= formatDateTime($comentario['fecha_creacion'], 'd/m H:i') ?></small>
                        </div>
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

