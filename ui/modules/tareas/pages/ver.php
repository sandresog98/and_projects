<?php
/**
 * AND PROJECTS APP - Ver Tarea
 */

require_once __DIR__ . '/../models/TareaModel.php';
require_once __DIR__ . '/../../subtareas/models/SubtareaModel.php';
require_once __DIR__ . '/../../comentarios/models/ComentarioModel.php';
require_once __DIR__ . '/../../../models/TiempoModel.php';

$model = new TareaModel();
$subtareaModel = new SubtareaModel();
$comentarioModel = new ComentarioModel();
$tiempoModel = new TiempoModel();

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

$pageTitle = $tarea['nombre'];
$pageSubtitle = 'Detalles de la tarea';

// Obtener subtareas
$subtareas = $subtareaModel->getByTarea($id);

// Obtener comentarios
$comentarios = $comentarioModel->getByEntidad('tarea', $id);

// Calcular estadísticas
$totalSubtareas = count($subtareas);
$subtareasCompletadas = count(array_filter($subtareas, fn($s) => $s['estado'] == 3));
$avance = $totalSubtareas > 0 ? round(($subtareasCompletadas / $totalSubtareas) * 100) : (float)($tarea['avance'] ?? 0);

// Obtener horas de la tarea
$horasTarea = $tiempoModel->getHorasTarea($id);
$porcentajeHoras = TiempoModel::calcularPorcentaje($horasTarea['horas_reales'], $horasTarea['horas_estimadas']);

// Procesar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
    $comentarioTexto = trim($_POST['comentario']);
    if (!empty($comentarioTexto)) {
        $comentarioModel->create([
            'tipo_entidad' => 'tarea',
            'entidad_id' => $id,
            'usuario_id' => getCurrentUserId(),
            'comentario' => $comentarioTexto
        ]);
        setFlashMessage('success', 'Comentario agregado');
        header('Location: ' . uiModuleUrl('tareas', 'ver', ['id' => $id]));
        exit;
    }
}
?>

<div class="d-flex justify-content-between align-items-start mb-4 fade-in-up">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="badge badge-priority-<?= $tarea['prioridad'] ?? 2 ?>"><?= getPriorityText($tarea['prioridad'] ?? 2) ?></span>
            <h4 class="mb-0"><?= htmlspecialchars($tarea['nombre']) ?></h4>
            <span class="badge badge-status-<?= $tarea['estado'] ?>"><?= getStatusText($tarea['estado']) ?></span>
        </div>
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
            <span class="text-muted">
                <a href="<?= uiModuleUrl('proyectos', 'ver', ['id' => $tarea['proyecto_id']]) ?>" class="text-muted">
                    <?= htmlspecialchars($tarea['proyecto_nombre'] ?? 'Sin proyecto') ?>
                </a>
                <span class="mx-1">•</span>
                <?= htmlspecialchars($tarea['empresa_nombre'] ?? 'Sin empresa') ?>
            </span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('tareas', 'editar')): ?>
        <a href="<?= uiModuleUrl('tareas', 'editar', ['id' => $id]) ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-2"></i>Editar
        </a>
        <?php endif; ?>
        <a href="<?= uiModuleUrl('tareas') ?>" class="btn btn-outline-secondary">
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
                <div class="stat-value"><?= $totalSubtareas ?></div>
                <div class="stat-label">Subtareas</div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.3s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value" style="color: var(--accent-success);"><?= $subtareasCompletadas ?></div>
                <div class="stat-label">Completadas</div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.4s">
        <div class="card h-100">
            <div class="card-body text-center">
                <?php if (!empty($tarea['asignado_nombre'])): ?>
                <div class="user-avatar mx-auto mb-2" style="width: 36px; height: 36px; font-size: 12px;">
                    <?= strtoupper(substr($tarea['asignado_nombre'], 0, 1)) ?>
                </div>
                <div class="stat-label"><?= htmlspecialchars($tarea['asignado_nombre']) ?></div>
                <?php else: ?>
                <i class="bi bi-person text-muted" style="font-size: 24px;"></i>
                <div class="stat-label">Sin asignar</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Resumen de Horas -->
<div class="row g-4 mb-4">
    <div class="col-12 fade-in-up" style="animation-delay: 0.45s">
        <div class="card">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history fs-4" style="color: var(--accent-info);"></i>
                            <span class="fw-medium" style="color: var(--text-primary);">Horas de la Tarea</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="d-flex align-items-center gap-3">
                            <div class="progress flex-grow-1" style="height: 10px; max-width: 300px;">
                                <div class="progress-bar <?= $porcentajeHoras > 100 ? 'bg-danger' : '' ?>" style="width: <?= min($porcentajeHoras, 100) ?>%"></div>
                            </div>
                            <span class="text-muted"><?= $porcentajeHoras ?>%</span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex gap-4">
                            <div class="text-center">
                                <div class="h5 mb-0" style="color: var(--accent-info);"><?= TiempoModel::formatHoras($horasTarea['horas_reales']) ?></div>
                                <small class="text-muted">Registradas</small>
                            </div>
                            <div class="text-center">
                                <div class="h5 mb-0" style="color: var(--accent-warning);"><?= TiempoModel::formatHoras($horasTarea['horas_estimadas']) ?></div>
                                <small class="text-muted">Estimadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Información de la tarea -->
    <div class="col-lg-8">
        <!-- Descripción -->
        <div class="card mb-4 fade-in-up" style="animation-delay: 0.5s">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Descripción</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($tarea['descripcion'])): ?>
                <p class="mb-0"><?= nl2br(htmlspecialchars($tarea['descripcion'])) ?></p>
                <?php else: ?>
                <p class="text-muted mb-0">Sin descripción</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Subtareas -->
        <div class="card fade-in-up" style="animation-delay: 0.6s">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Subtareas</h6>
                <?php if (hasPermission('subtareas', 'crear')): ?>
                <a href="<?= uiModuleUrl('subtareas', 'crear', ['tarea_id' => $id]) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Nueva Subtarea
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($subtareas)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-list-check text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">No hay subtareas</p>
                    <?php if (hasPermission('subtareas', 'crear')): ?>
                    <a href="<?= uiModuleUrl('subtareas', 'crear', ['tarea_id' => $id]) ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>Crear Primera Subtarea
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($subtareas as $subtarea): ?>
                    <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center gap-3">
                            <?php if ($subtarea['estado'] == 3): ?>
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <?php elseif ($subtarea['estado'] == 2): ?>
                            <i class="bi bi-circle-half text-primary fs-5"></i>
                            <?php else: ?>
                            <i class="bi bi-circle text-muted fs-5"></i>
                            <?php endif; ?>
                            <div>
                                <a href="<?= uiModuleUrl('subtareas', 'ver', ['id' => $subtarea['id']]) ?>" 
                                   class="text-decoration-none <?= $subtarea['estado'] == 3 ? 'text-decoration-line-through text-muted' : '' ?>">
                                    <strong><?= htmlspecialchars($subtarea['nombre']) ?></strong>
                                </a>
                                <?php if (!empty($subtarea['realizado_por_nombre'])): ?>
                                <small class="text-muted d-block">
                                    <i class="bi bi-person me-1"></i><?= htmlspecialchars($subtarea['realizado_por_nombre']) ?>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($subtarea['horas_reales'] > 0): ?>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i><?= number_format($subtarea['horas_reales'], 1) ?>h
                            </small>
                            <?php endif; ?>
                            <span class="badge badge-status-<?= $subtarea['estado'] ?>">
                                <?= getStatusText($subtarea['estado']) ?>
                            </span>
                            <!-- Botones de acción -->
                            <div class="d-flex gap-1 ms-2">
                                <a href="<?= uiModuleUrl('subtareas', 'ver', ['id' => $subtarea['id']]) ?>" 
                                   class="btn-icon btn-icon-sm" title="Ver detalles" data-bs-toggle="tooltip">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (hasPermission('subtareas', 'editar')): ?>
                                <a href="<?= uiModuleUrl('subtareas', 'editar', ['id' => $subtarea['id']]) ?>" 
                                   class="btn-icon btn-icon-sm btn-icon-primary" title="Editar" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
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
                    <small class="text-muted d-block">Fecha de Inicio Estimada</small>
                    <strong><?= !empty($tarea['fecha_inicio_estimada']) ? formatDate($tarea['fecha_inicio_estimada']) : 'No definida' ?></strong>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha de Fin Estimada</small>
                    <strong><?= !empty($tarea['fecha_fin_estimada']) ? formatDate($tarea['fecha_fin_estimada']) : 'No definida' ?></strong>
                </div>
                
                <?php if (!empty($tarea['fecha_fin_real'])): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha Real de Fin</small>
                    <strong><?= formatDate($tarea['fecha_fin_real']) ?></strong>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Creado por</small>
                    <strong><?= htmlspecialchars($tarea['creador_nombre'] ?? 'Sistema') ?></strong>
                </div>
                
                <div>
                    <small class="text-muted d-block">Última actualización</small>
                    <strong><?= formatDateTime($tarea['fecha_actualizacion']) ?></strong>
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

