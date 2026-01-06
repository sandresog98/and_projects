<?php
/**
 * AND PROJECTS APP - Ver Reunión
 */

require_once __DIR__ . '/../models/ReunionModel.php';

$model = new ReunionModel();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setFlashMessage('error', 'Reunión no especificada');
    header('Location: ' . uiModuleUrl('reuniones'));
    exit;
}

$reunion = $model->getById($id);

if (!$reunion) {
    setFlashMessage('error', 'Reunión no encontrada');
    header('Location: ' . uiModuleUrl('reuniones'));
    exit;
}

$pageTitle = $reunion['titulo'];
$pageSubtitle = 'Detalles de la reunión';

// Determinar si la reunión es pasada, hoy o futura
$hoy = date('Y-m-d');
$esHoy = $reunion['fecha'] === $hoy;
$esPasada = $reunion['fecha'] < $hoy;
$esFutura = $reunion['fecha'] > $hoy;

// Procesar guardar insights
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insights'])) {
    $insights = trim($_POST['insights']);
    $model->update($id, ['insights' => $insights]);
    setFlashMessage('success', 'Insights guardados correctamente');
    header('Location: ' . uiModuleUrl('reuniones', 'ver', ['id' => $id]));
    exit;
}
?>

<div class="d-flex justify-content-between align-items-start mb-4 fade-in-up">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <h4 class="mb-0"><?= htmlspecialchars($reunion['titulo']) ?></h4>
            <?php if ($esHoy): ?>
            <span class="badge bg-success">Hoy</span>
            <?php elseif ($esPasada): ?>
            <span class="badge bg-secondary">Pasada</span>
            <?php else: ?>
            <span class="badge bg-primary">Próxima</span>
            <?php endif; ?>
            
            <?php
            $tipoClass = match($reunion['tipo']) {
                'virtual' => 'info',
                'hibrida' => 'warning',
                default => 'primary'
            };
            ?>
            <span class="badge bg-<?= $tipoClass ?>"><?= ucfirst($reunion['tipo']) ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($reunion['empresa_nombre']): ?>
                <?php if (!empty($reunion['empresa_logo'])): ?>
                <img src="<?= UPLOADS_URL . '/' . $reunion['empresa_logo'] ?>" 
                     alt="<?= htmlspecialchars($reunion['empresa_nombre']) ?>" 
                     class="rounded" style="width: 24px; height: 24px; object-fit: contain; background: #fff;">
                <?php else: ?>
                <div class="rounded d-flex align-items-center justify-content-center" 
                     style="width: 24px; height: 24px; background: var(--bg-tertiary);">
                    <i class="bi bi-building text-muted" style="font-size: 12px;"></i>
                </div>
                <?php endif; ?>
                <span class="text-muted"><?= htmlspecialchars($reunion['empresa_nombre']) ?></span>
            <?php endif; ?>
            <?php if ($reunion['proyecto_nombre']): ?>
            <span class="text-muted mx-1">•</span>
            <span class="text-muted"><i class="bi bi-folder me-1"></i><?= htmlspecialchars($reunion['proyecto_nombre']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2">
        <?php if (hasPermission('reuniones', 'editar')): ?>
        <a href="<?= uiModuleUrl('reuniones', 'editar', ['id' => $id]) ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil me-2"></i>Editar
        </a>
        <?php endif; ?>
        <a href="<?= uiModuleUrl('reuniones') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Información principal -->
    <div class="col-lg-8">
        <!-- Fecha y hora destacada -->
        <div class="card mb-4 fade-in-up" style="animation-delay: 0.1s">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="text-center p-3 rounded" style="background: var(--bg-tertiary); min-width: 100px;">
                            <div class="text-muted small text-uppercase"><?= strftime('%B', strtotime($reunion['fecha'])) ?></div>
                            <div class="display-4 fw-bold" style="color: var(--primary-blue);">
                                <?= date('d', strtotime($reunion['fecha'])) ?>
                            </div>
                            <div class="text-muted small"><?= strftime('%A', strtotime($reunion['fecha'])) ?></div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="d-flex align-items-center gap-4">
                            <div>
                                <div class="text-muted small">Hora de inicio</div>
                                <div class="h4 mb-0"><?= date('H:i', strtotime($reunion['hora_inicio'])) ?></div>
                            </div>
                            <div>
                                <div class="text-muted small">Duración</div>
                                <div class="h4 mb-0"><?= formatDuration($reunion['duracion_minutos']) ?></div>
                            </div>
                            <?php if ($reunion['ubicacion']): ?>
                            <div class="flex-grow-1">
                                <div class="text-muted small">Ubicación</div>
                                <div>
                                    <?php 
                                    // Detectar si es un enlace
                                    if (filter_var($reunion['ubicacion'], FILTER_VALIDATE_URL) || strpos($reunion['ubicacion'], 'http') === 0): 
                                    ?>
                                    <a href="<?= htmlspecialchars($reunion['ubicacion']) ?>" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="bi bi-camera-video me-1"></i>Unirse a la reunión
                                    </a>
                                    <?php else: ?>
                                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($reunion['ubicacion']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Descripción -->
        <?php if ($reunion['descripcion']): ?>
        <div class="card mb-4 fade-in-up" style="animation-delay: 0.2s">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Descripción</h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(htmlspecialchars($reunion['descripcion'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Finalidad -->
        <?php if ($reunion['finalidad']): ?>
        <div class="card mb-4 fade-in-up" style="animation-delay: 0.3s">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-bullseye me-2"></i>Finalidad / Objetivos</h6>
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(htmlspecialchars($reunion['finalidad'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Insights (notas de la reunión) -->
        <div class="card fade-in-up" style="animation-delay: 0.4s">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Insights / Notas de la Reunión</h6>
            </div>
            <div class="card-body">
                <?php if (hasPermission('reuniones', 'editar')): ?>
                <form method="POST">
                    <textarea name="insights" class="form-control mb-3" rows="5" placeholder="Agrega aquí los puntos importantes, decisiones tomadas, tareas asignadas y cualquier insight relevante de la reunión..."><?= htmlspecialchars($reunion['insights'] ?? '') ?></textarea>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Guardar Insights
                    </button>
                </form>
                <?php else: ?>
                    <?php if ($reunion['insights']): ?>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($reunion['insights'])) ?></p>
                    <?php else: ?>
                    <p class="text-muted mb-0">No hay insights registrados</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Detalles -->
        <div class="card mb-4 fade-in-up" style="animation-delay: 0.2s">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Detalles</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Tipo de Reunión</small>
                    <strong>
                        <?php if ($reunion['tipo'] === 'presencial'): ?>
                        🏢 Presencial
                        <?php elseif ($reunion['tipo'] === 'virtual'): ?>
                        💻 Virtual
                        <?php else: ?>
                        🔄 Híbrida
                        <?php endif; ?>
                    </strong>
                </div>
                
                <?php if ($reunion['empresa_nombre']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Empresa</small>
                    <strong><?= htmlspecialchars($reunion['empresa_nombre']) ?></strong>
                </div>
                <?php endif; ?>
                
                <?php if ($reunion['proyecto_nombre']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Proyecto</small>
                    <a href="<?= uiModuleUrl('proyectos', 'ver', ['id' => $reunion['proyecto_id']]) ?>">
                        <?= htmlspecialchars($reunion['proyecto_nombre']) ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <small class="text-muted d-block">Creada por</small>
                    <strong><?= htmlspecialchars($reunion['creador_nombre'] ?? 'Sistema') ?></strong>
                </div>
                
                <div>
                    <small class="text-muted d-block">Fecha de creación</small>
                    <strong><?= formatDateTime($reunion['fecha_creacion']) ?></strong>
                </div>
            </div>
        </div>
        
        <!-- Acciones rápidas -->
        <?php if ($esFutura || $esHoy): ?>
        <div class="card mt-4 fade-in-up" style="animation-delay: 0.3s">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Acciones Rápidas</h6>
            </div>
            <div class="card-body d-grid gap-2">
                <?php 
                // Detectar si la ubicación contiene un enlace
                $esEnlace = $reunion['ubicacion'] && (filter_var($reunion['ubicacion'], FILTER_VALIDATE_URL) || strpos($reunion['ubicacion'], 'http') !== false);
                if ($esEnlace): 
                ?>
                <a href="<?= htmlspecialchars($reunion['ubicacion']) ?>" target="_blank" class="btn btn-primary">
                    <i class="bi bi-camera-video me-2"></i>Unirse a la reunión
                </a>
                <?php endif; ?>
                <?php if (hasPermission('reuniones', 'editar')): ?>
                <a href="<?= uiModuleUrl('reuniones', 'editar', ['id' => $id]) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-pencil me-2"></i>Editar Reunión
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

