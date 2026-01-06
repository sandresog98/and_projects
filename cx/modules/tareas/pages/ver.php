<?php
/**
 * AND PROJECTS APP - Ver Tarea para Clientes (CX)
 */

require_once __DIR__ . '/../../../../ui/modules/tareas/models/TareaModel.php';
require_once __DIR__ . '/../../../../ui/modules/subtareas/models/SubtareaModel.php';
require_once __DIR__ . '/../../../../ui/modules/comentarios/models/ComentarioModel.php';

$tareaModel = new TareaModel();
$subtareaModel = new SubtareaModel();
$comentarioModel = new ComentarioModel();

$id = (int)($_GET['id'] ?? 0);
$empresaId = getCurrentClientEmpresaId();

if (!$id) {
    setFlashMessage('error', 'Tarea no especificada');
    header('Location: ' . cxModuleUrl('proyectos'));
    exit;
}

$tarea = $tareaModel->getById($id);

if (!$tarea) {
    setFlashMessage('error', 'Tarea no encontrada');
    header('Location: ' . cxModuleUrl('proyectos'));
    exit;
}

// Verificar que la tarea pertenece a la empresa del cliente
if ($tarea['empresa_id'] != $empresaId) {
    setFlashMessage('error', 'No tienes acceso a esta tarea');
    header('Location: ' . cxModuleUrl('proyectos'));
    exit;
}

$pageTitle = $tarea['nombre'];
$pageSubtitle = 'Detalles de la tarea';

// Obtener subtareas
$subtareas = $subtareaModel->getByTarea($id);

// Obtener comentarios
$comentarios = $comentarioModel->getByEntidad('tarea', $id);

// Procesar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
    $textoComentario = trim($_POST['comentario']);
    if (!empty($textoComentario)) {
        $comentarioModel->create([
            'tipo_entidad' => 'tarea',
            'entidad_id' => $id,
            'usuario_id' => getCurrentClientId(),
            'comentario' => $textoComentario
        ]);
        setFlashMessage('success', 'Comentario agregado');
        header('Location: ' . cxModuleUrl('tareas', 'ver', ['id' => $id]));
        exit;
    }
}

// Calcular avance
if ($tarea['estado'] == 3) {
    // Si la tarea está completada, el avance es 100%
    $avanceTarea = 100;
} elseif ($tarea['total_subtareas'] > 0) {
    // Si tiene subtareas, calcular basado en ellas
    $avanceTarea = round(($tarea['subtareas_completadas'] / $tarea['total_subtareas']) * 100);
} else {
    // Sin subtareas y no completada
    $avanceTarea = $tarea['estado'] == 2 ? 50 : 0; // En progreso = 50%, pendiente = 0%
}

// Funciones helper
if (!function_exists('getStatusText')) {
    function getStatusText($estado): string {
        return match((int)$estado) {
            1 => 'Pendiente',
            2 => 'En Progreso',
            3 => 'Completado',
            4 => 'Bloqueado',
            default => 'Desconocido'
        };
    }
}

if (!function_exists('getStatusClass')) {
    function getStatusClass($estado): string {
        return match((int)$estado) {
            1 => 'pending',
            2 => 'in-progress',
            3 => 'completed',
            4 => 'blocked',
            default => 'pending'
        };
    }
}

if (!function_exists('getPriorityText')) {
    function getPriorityText($prioridad): string {
        return match((int)$prioridad) {
            1 => 'Baja',
            2 => 'Media',
            3 => 'Alta',
            4 => 'Urgente',
            default => 'Media'
        };
    }
}

if (!function_exists('getPriorityClass')) {
    function getPriorityClass($prioridad): string {
        return match((int)$prioridad) {
            1 => 'success',
            2 => 'info',
            3 => 'warning',
            4 => 'danger',
            default => 'info'
        };
    }
}
?>

<div class="mb-4 fade-in-up">
    <a href="<?= cxModuleUrl('proyectos', 'ver', ['id' => $tarea['proyecto_id']]) ?>" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="bi bi-arrow-left me-2"></i>Volver al Proyecto
    </a>
    
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h4 class="mb-2"><?= htmlspecialchars($tarea['nombre']) ?></h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge badge-status-<?= $tarea['estado'] ?>">
                    <?= getStatusText($tarea['estado']) ?>
                </span>
                <span class="badge bg-<?= getPriorityClass($tarea['prioridad']) ?>">
                    <?= getPriorityText($tarea['prioridad']) ?>
                </span>
                <small class="text-muted">
                    <i class="bi bi-folder me-1"></i>
                    <a href="<?= cxModuleUrl('proyectos', 'ver', ['id' => $tarea['proyecto_id']]) ?>" class="text-muted">
                        <?= htmlspecialchars($tarea['proyecto_nombre']) ?>
                    </a>
                </small>
            </div>
        </div>
        <div class="text-end">
            <div class="display-5 fw-bold" style="color: var(--secondary-green);"><?= $avanceTarea ?>%</div>
            <small class="text-muted">Avance</small>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Información principal -->
    <div class="col-lg-8">
        <!-- Descripción -->
        <div class="card mb-4 fade-in-up">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Descripción</h6>
            </div>
            <div class="card-body">
                <?php if ($tarea['descripcion']): ?>
                <p class="mb-0"><?= nl2br(htmlspecialchars($tarea['descripcion'])) ?></p>
                <?php else: ?>
                <p class="text-muted mb-0">Sin descripción</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Subtareas -->
        <div class="card mb-4 fade-in-up">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-check2-square me-2"></i>Subtareas</h6>
                <span class="badge bg-secondary"><?= count($subtareas) ?> items</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($subtareas)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-check2-square text-muted" style="font-size: 36px;"></i>
                    <p class="text-muted mt-2 mb-0">No hay subtareas registradas</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($subtareas as $subtarea): ?>
                    <div class="list-group-item bg-transparent" style="border-color: var(--border-color);">
                        <div class="d-flex align-items-start gap-3">
                            <div class="pt-1">
                                <?php if ($subtarea['estado'] == 3): ?>
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 20px;"></i>
                                <?php elseif ($subtarea['estado'] == 2): ?>
                                <i class="bi bi-play-circle text-primary" style="font-size: 20px;"></i>
                                <?php elseif ($subtarea['estado'] == 4): ?>
                                <i class="bi bi-exclamation-circle text-danger" style="font-size: 20px;"></i>
                                <?php else: ?>
                                <i class="bi bi-circle text-muted" style="font-size: 20px;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong class="<?= $subtarea['estado'] == 3 ? 'text-decoration-line-through text-muted' : '' ?>">
                                            <?= htmlspecialchars($subtarea['nombre']) ?>
                                        </strong>
                                        <?php if ($subtarea['descripcion']): ?>
                                        <p class="text-muted small mb-0 mt-1"><?= htmlspecialchars($subtarea['descripcion']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge badge-status-<?= $subtarea['estado'] ?> ms-2">
                                        <?= getStatusText($subtarea['estado']) ?>
                                    </span>
                                </div>
                                <div class="d-flex gap-3 mt-2 small text-muted">
                                    <?php if ($subtarea['realizado_por_nombre']): ?>
                                    <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($subtarea['realizado_por_nombre']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($subtarea['fecha_fin_estimada']): ?>
                                    <span><i class="bi bi-calendar me-1"></i><?= formatDate($subtarea['fecha_fin_estimada']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($subtarea['horas_estimadas']): ?>
                                    <span><i class="bi bi-clock me-1"></i><?= $subtarea['horas_estimadas'] ?>h estimadas</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Comentarios -->
        <div class="card fade-in-up">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Comentarios</h6>
            </div>
            <div class="card-body">
                <!-- Formulario de comentario -->
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <textarea name="comentario" class="form-control" rows="3" placeholder="Escribe un comentario o pregunta..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-2"></i>Enviar Comentario
                    </button>
                </form>
                
                <?php if (empty($comentarios)): ?>
                <p class="text-muted text-center mb-0">No hay comentarios aún</p>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($comentarios as $comentario): ?>
                    <div class="d-flex gap-3 mb-4 pb-4" style="border-bottom: 1px solid var(--border-color);">
                        <div class="user-avatar" style="width: 40px; height: 40px; font-size: 14px; flex-shrink: 0;">
                            <?= strtoupper(substr($comentario['usuario_nombre'], 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong><?= htmlspecialchars($comentario['usuario_nombre']) ?></strong>
                                <small class="text-muted"><?= formatDateTime($comentario['fecha_creacion']) ?></small>
                            </div>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($comentario['comentario'])) ?></p>
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
        <!-- Info de la tarea -->
        <div class="card mb-4 fade-in-up">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-calendar me-2"></i>Información</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Proyecto</small>
                    <strong>
                        <a href="<?= cxModuleUrl('proyectos', 'ver', ['id' => $tarea['proyecto_id']]) ?>">
                            <?= htmlspecialchars($tarea['proyecto_nombre']) ?>
                        </a>
                    </strong>
                </div>
                <?php if ($tarea['asignado_nombre']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Asignado a</small>
                    <strong><?= htmlspecialchars($tarea['asignado_nombre']) ?></strong>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha de inicio</small>
                    <strong><?= $tarea['fecha_inicio_estimada'] ? formatDate($tarea['fecha_inicio_estimada']) : 'No definida' ?></strong>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha estimada de entrega</small>
                    <strong><?= $tarea['fecha_fin_estimada'] ? formatDate($tarea['fecha_fin_estimada']) : 'No definida' ?></strong>
                </div>
                <?php if ($tarea['fecha_fin_real']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha de finalización</small>
                    <strong class="text-success"><?= formatDate($tarea['fecha_fin_real']) ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Progreso visual -->
        <div class="card fade-in-up">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Progreso</h6>
            </div>
            <div class="card-body text-center">
                <div class="position-relative d-inline-block mb-3">
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--bg-tertiary)" stroke-width="10"/>
                        <circle cx="60" cy="60" r="50" fill="none" stroke="var(--secondary-green)" stroke-width="10"
                                stroke-dasharray="<?= 314 * ($avanceTarea / 100) ?> 314"
                                stroke-linecap="round"
                                transform="rotate(-90 60 60)"/>
                    </svg>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <div class="h4 fw-bold mb-0" style="color: var(--text-primary);"><?= $avanceTarea ?>%</div>
                    </div>
                </div>
                <div class="d-flex justify-content-center gap-4 text-center">
                    <div>
                        <div class="h5 mb-0" style="color: var(--secondary-green);"><?= $tarea['subtareas_completadas'] ?></div>
                        <small class="text-muted">Completadas</small>
                    </div>
                    <div>
                        <div class="h5 mb-0" style="color: var(--text-primary);"><?= $tarea['total_subtareas'] - $tarea['subtareas_completadas'] ?></div>
                        <small class="text-muted">Pendientes</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

