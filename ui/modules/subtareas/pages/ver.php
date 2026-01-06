<?php
/**
 * AND PROJECTS APP - Ver Subtarea
 */

require_once __DIR__ . '/../models/SubtareaModel.php';
require_once __DIR__ . '/../../comentarios/models/ComentarioModel.php';

$model = new SubtareaModel();
$comentarioModel = new ComentarioModel();

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

$pageTitle = $subtarea['nombre'];
$pageSubtitle = 'Detalles de la subtarea';

// Obtener tiempos registrados
$tiempos = $model->getTiemposSubtarea($id);

// Obtener comentarios
$comentarios = $comentarioModel->getByEntidad('subtarea', $id);

// Calcular total de horas
$totalHoras = array_reduce($tiempos, function($carry, $t) {
    return $carry + $t['horas'] + ($t['minutos'] / 60);
}, 0);

// Procesar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['comentario'])) {
        $comentarioTexto = trim($_POST['comentario']);
        if (!empty($comentarioTexto)) {
            $comentarioModel->create([
                'tipo_entidad' => 'subtarea',
                'entidad_id' => $id,
                'usuario_id' => getCurrentUserId(),
                'comentario' => $comentarioTexto
            ]);
            setFlashMessage('success', 'Comentario agregado');
            header('Location: ' . uiModuleUrl('subtareas', 'ver', ['id' => $id]));
            exit;
        }
    }
    
    // Registrar tiempo
    if (isset($_POST['registrar_tiempo'])) {
        $horas = (int)($_POST['horas'] ?? 0);
        $minutos = (int)($_POST['minutos'] ?? 0);
        $descripcionTiempo = trim($_POST['descripcion_tiempo'] ?? '');
        
        if ($horas > 0 || $minutos > 0) {
            $model->registrarTiempo([
                'subtarea_id' => $id,
                'usuario_id' => getCurrentUserId(),
                'horas' => $horas,
                'minutos' => $minutos,
                'descripcion' => $descripcionTiempo ?: null
            ]);
            setFlashMessage('success', 'Tiempo registrado correctamente');
            header('Location: ' . uiModuleUrl('subtareas', 'ver', ['id' => $id]));
            exit;
        }
    }
    
    // Completar subtarea
    if (isset($_POST['completar'])) {
        $model->completar($id, getCurrentUserId());
        setFlashMessage('success', 'Subtarea marcada como completada');
        header('Location: ' . uiModuleUrl('subtareas', 'ver', ['id' => $id]));
        exit;
    }
    
    // Reabrir subtarea
    if (isset($_POST['reabrir'])) {
        $model->reabrir($id);
        setFlashMessage('success', 'Subtarea reabierta');
        header('Location: ' . uiModuleUrl('subtareas', 'ver', ['id' => $id]));
        exit;
    }
}
?>

<div class="d-flex justify-content-between align-items-start mb-4 fade-in-up">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <h4 class="mb-0"><?= htmlspecialchars($subtarea['nombre']) ?></h4>
            <span class="badge badge-status-<?= $subtarea['estado'] ?>"><?= getStatusText($subtarea['estado']) ?></span>
        </div>
        <p class="text-muted mb-0">
            <i class="bi bi-list-task me-1"></i>
            <a href="<?= uiModuleUrl('tareas', 'ver', ['id' => $subtarea['tarea_id']]) ?>">
                <?= htmlspecialchars($subtarea['tarea_nombre'] ?? 'Sin tarea') ?>
            </a>
            <span class="mx-2">•</span>
            <i class="bi bi-folder me-1"></i>
            <a href="<?= uiModuleUrl('proyectos', 'ver', ['id' => $subtarea['proyecto_id']]) ?>">
                <?= htmlspecialchars($subtarea['proyecto_nombre'] ?? 'Sin proyecto') ?>
            </a>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($subtarea['estado'] != 3 && hasPermission('subtareas', 'editar')): ?>
        <form method="POST" class="d-inline">
            <input type="hidden" name="completar" value="1">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check-lg me-2"></i>Completar
            </button>
        </form>
        <?php elseif ($subtarea['estado'] == 3 && hasPermission('subtareas', 'editar')): ?>
        <form method="POST" class="d-inline">
            <input type="hidden" name="reabrir" value="1">
            <button type="submit" class="btn btn-outline-warning">
                <i class="bi bi-arrow-counterclockwise me-2"></i>Reabrir
            </button>
        </form>
        <?php endif; ?>
        
        <?php if (hasPermission('subtareas', 'editar')): ?>
        <a href="<?= uiModuleUrl('subtareas', 'editar', ['id' => $id]) ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-2"></i>Editar
        </a>
        <?php endif; ?>
        <a href="<?= uiModuleUrl('tareas', 'ver', ['id' => $subtarea['tarea_id']]) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<!-- Estadísticas -->
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.1s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value"><?= number_format($subtarea['horas_estimadas'] ?? 0, 1) ?>h</div>
                <div class="stat-label">Horas Estimadas</div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.2s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value text-primary"><?= number_format($totalHoras, 1) ?>h</div>
                <div class="stat-label">Horas Registradas</div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.3s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value"><?= count($tiempos) ?></div>
                <div class="stat-label">Registros</div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.4s">
        <div class="card h-100">
            <div class="card-body text-center">
                <?php if (!empty($subtarea['realizado_por_nombre'])): ?>
                <div class="user-avatar mx-auto mb-2" style="width: 36px; height: 36px; font-size: 12px;">
                    <?= strtoupper(substr($subtarea['realizado_por_nombre'], 0, 1)) ?>
                </div>
                <div class="stat-label"><?= htmlspecialchars($subtarea['realizado_por_nombre']) ?></div>
                <?php else: ?>
                <i class="bi bi-person text-muted" style="font-size: 24px;"></i>
                <div class="stat-label">Sin asignar</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Información de la subtarea -->
    <div class="col-lg-8">
        <!-- Descripción -->
        <div class="card mb-4 fade-in-up" style="animation-delay: 0.5s">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Descripción</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($subtarea['descripcion'])): ?>
                <p class="mb-0"><?= nl2br(htmlspecialchars($subtarea['descripcion'])) ?></p>
                <?php else: ?>
                <p class="text-muted mb-0">Sin descripción</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Registro de Tiempo -->
        <div class="card mb-4 fade-in-up" style="animation-delay: 0.6s">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Registro de Tiempo</h6>
            </div>
            <div class="card-body">
                <?php if (hasPermission('subtareas', 'editar') && $subtarea['estado'] != 3): ?>
                <form method="POST" class="mb-4">
                    <input type="hidden" name="registrar_tiempo" value="1">
                    <div class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label class="form-label small">Horas</label>
                            <input type="number" name="horas" class="form-control" value="0" min="0" max="24" style="width: 80px;">
                        </div>
                        <div class="col-auto">
                            <label class="form-label small">Minutos</label>
                            <input type="number" name="minutos" class="form-control" value="0" min="0" max="59" step="15" style="width: 80px;">
                        </div>
                        <div class="col">
                            <label class="form-label small">Descripción (opcional)</label>
                            <input type="text" name="descripcion_tiempo" class="form-control" placeholder="¿En qué trabajaste?">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-1"></i>Registrar
                            </button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
                
                <?php if (empty($tiempos)): ?>
                <p class="text-muted text-center mb-0">No hay tiempo registrado</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tiempo</th>
                                <th>Usuario</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tiempos as $tiempo): ?>
                            <tr>
                                <td><?= formatDate($tiempo['fecha']) ?></td>
                                <td>
                                    <strong><?= $tiempo['horas'] ?>h <?= $tiempo['minutos'] ?>m</strong>
                                </td>
                                <td><?= htmlspecialchars($tiempo['usuario_nombre'] ?? 'Usuario') ?></td>
                                <td class="text-muted"><?= htmlspecialchars($tiempo['descripcion'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                    <strong><?= !empty($subtarea['fecha_inicio_estimada']) ? formatDate($subtarea['fecha_inicio_estimada']) : 'No definida' ?></strong>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha de Fin Estimada</small>
                    <strong><?= !empty($subtarea['fecha_fin_estimada']) ? formatDate($subtarea['fecha_fin_estimada']) : 'No definida' ?></strong>
                </div>
                
                <?php if (!empty($subtarea['fecha_fin_real'])): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha Real de Fin</small>
                    <strong class="text-success"><?= formatDate($subtarea['fecha_fin_real']) ?></strong>
                </div>
                <?php endif; ?>
                
                <div>
                    <small class="text-muted d-block">Última actualización</small>
                    <strong><?= formatDateTime($subtarea['fecha_actualizacion']) ?></strong>
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

