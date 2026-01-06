<?php
/**
 * AND PROJECTS APP - Ver Proyecto para Clientes (CX)
 */

require_once __DIR__ . '/../../../../ui/modules/proyectos/models/ProyectoModel.php';
require_once __DIR__ . '/../../../../ui/modules/tareas/models/TareaModel.php';
require_once __DIR__ . '/../../../../ui/modules/subtareas/models/SubtareaModel.php';
require_once __DIR__ . '/../../../../ui/modules/comentarios/models/ComentarioModel.php';

$proyectoModel = new ProyectoModel();
$tareaModel = new TareaModel();
$subtareaModel = new SubtareaModel();
$comentarioModel = new ComentarioModel();

$id = (int)($_GET['id'] ?? 0);
$empresaId = getCurrentClientEmpresaId();

if (!$id) {
    setFlashMessage('error', 'Proyecto no especificado');
    header('Location: ' . cxModuleUrl('proyectos'));
    exit;
}

$proyecto = $proyectoModel->getById($id);

if (!$proyecto) {
    setFlashMessage('error', 'Proyecto no encontrado');
    header('Location: ' . cxModuleUrl('proyectos'));
    exit;
}

// Verificar que el proyecto pertenece a la empresa del cliente
if ($proyecto['empresa_id'] != $empresaId) {
    setFlashMessage('error', 'No tienes acceso a este proyecto');
    header('Location: ' . cxModuleUrl('proyectos'));
    exit;
}

$pageTitle = $proyecto['nombre'];
$pageSubtitle = 'Detalles del proyecto';

// Obtener tareas
$tareas = $tareaModel->getByProyecto($id);

// Calcular avance real del proyecto
if ($proyecto['estado'] == 3) {
    // Proyecto completado = 100%
    $avanceProyecto = 100;
} elseif (count($tareas) > 0) {
    // Calcular basado en tareas
    $tareasCompletadas = count(array_filter($tareas, fn($t) => $t['estado'] == 3));
    $avanceProyecto = round(($tareasCompletadas / count($tareas)) * 100);
} elseif ($proyecto['estado'] == 2) {
    // En progreso sin tareas
    $avanceProyecto = 50;
} else {
    // Usar valor de BD o 0
    $avanceProyecto = $proyecto['avance'] ?? 0;
}

// Obtener comentarios
$comentarios = $comentarioModel->getByEntidad('proyecto', $id);

// Procesar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
    $textoComentario = trim($_POST['comentario']);
    if (!empty($textoComentario)) {
        $comentarioModel->create([
            'tipo_entidad' => 'proyecto',
            'entidad_id' => $id,
            'usuario_id' => getCurrentClientId(),
            'comentario' => $textoComentario
        ]);
        setFlashMessage('success', 'Comentario agregado');
        header('Location: ' . cxModuleUrl('proyectos', 'ver', ['id' => $id]));
        exit;
    }
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
?>

<div class="mb-4 fade-in-up">
    <a href="<?= cxModuleUrl('proyectos') ?>" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="bi bi-arrow-left me-2"></i>Volver a Proyectos
    </a>
    
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h4 class="mb-2"><?= htmlspecialchars($proyecto['nombre']) ?></h4>
            <span class="badge badge-status-<?= $proyecto['estado'] ?> me-2">
                <?= getStatusText($proyecto['estado']) ?>
            </span>
        </div>
        <div class="text-end">
            <div class="display-5 fw-bold" style="color: var(--secondary-green);"><?= $avanceProyecto ?>%</div>
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
                <?php if ($proyecto['descripcion']): ?>
                <p class="mb-0"><?= nl2br(htmlspecialchars($proyecto['descripcion'])) ?></p>
                <?php else: ?>
                <p class="text-muted mb-0">Sin descripción</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tareas -->
        <div class="card mb-4 fade-in-up">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-list-task me-2"></i>Tareas del Proyecto</h6>
                <span class="badge bg-secondary"><?= count($tareas) ?> tareas</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tareas)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-list text-muted" style="font-size: 36px;"></i>
                    <p class="text-muted mt-2 mb-0">No hay tareas registradas</p>
                </div>
                <?php else: ?>
                <div class="accordion accordion-flush" id="accordionTareas">
                    <?php foreach ($tareas as $index => $tarea): ?>
                    <?php 
                        // Calcular avance de la tarea
                        if ($tarea['estado'] == 3) {
                            $avanceTarea = 100;
                        } elseif ($tarea['total_subtareas'] > 0) {
                            $avanceTarea = round(($tarea['subtareas_completadas'] / $tarea['total_subtareas']) * 100);
                        } else {
                            $avanceTarea = $tarea['estado'] == 2 ? 50 : 0;
                        }
                        $subtareas = $subtareaModel->getByTarea($tarea['id']);
                    ?>
                    <div class="accordion-item" style="background: transparent; border-color: var(--border-color);">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#tarea<?= $tarea['id'] ?>"
                                    style="background: transparent; color: var(--text-primary); box-shadow: none;">
                                <div class="d-flex align-items-center gap-3 flex-grow-1 me-3">
                                    <div class="status-dot <?= getStatusClass($tarea['estado']) ?>"></div>
                                    <div class="flex-grow-1">
                                        <strong><?= htmlspecialchars($tarea['nombre']) ?></strong>
                                        <div class="d-flex align-items-center gap-3 mt-1">
                                            <small class="text-muted"><?= $tarea['total_subtareas'] ?> subtareas</small>
                                            <div class="d-flex align-items-center gap-2" style="width: 80px;">
                                                <div class="progress flex-grow-1" style="height: 4px;">
                                                    <div class="progress-bar" style="width: <?= $avanceTarea ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?= $avanceTarea ?>%</small>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge badge-status-<?= $tarea['estado'] ?>">
                                        <?= getStatusText($tarea['estado']) ?>
                                    </span>
                                </div>
                            </button>
                        </h2>
                        <div id="tarea<?= $tarea['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#accordionTareas">
                            <div class="accordion-body" style="background: var(--bg-tertiary); border-top: 1px solid var(--border-color);">
                                <?php if ($tarea['descripcion']): ?>
                                <p class="small mb-3"><?= htmlspecialchars($tarea['descripcion']) ?></p>
                                <?php endif; ?>
                                
                                <?php if (empty($subtareas)): ?>
                                <p class="text-muted small mb-3">No hay subtareas definidas</p>
                                <?php else: ?>
                                <div class="mb-3">
                                    <?php foreach ($subtareas as $subtarea): ?>
                                    <div class="d-flex align-items-center gap-2 py-2" style="border-bottom: 1px solid var(--border-color);">
                                        <?php if ($subtarea['estado'] == 3): ?>
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                        <?php elseif ($subtarea['estado'] == 2): ?>
                                        <i class="bi bi-play-circle text-primary"></i>
                                        <?php elseif ($subtarea['estado'] == 4): ?>
                                        <i class="bi bi-exclamation-circle text-danger"></i>
                                        <?php else: ?>
                                        <i class="bi bi-circle text-muted"></i>
                                        <?php endif; ?>
                                        <span class="flex-grow-1 <?= $subtarea['estado'] == 3 ? 'text-decoration-line-through text-muted' : '' ?>">
                                            <?= htmlspecialchars($subtarea['nombre']) ?>
                                        </span>
                                        <span class="badge badge-status-<?= $subtarea['estado'] ?> badge-sm">
                                            <?= getStatusText($subtarea['estado']) ?>
                                        </span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <a href="<?= cxModuleUrl('tareas', 'ver', ['id' => $tarea['id']]) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Ver detalle completo
                                </a>
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
                        <textarea name="comentario" class="form-control" rows="3" placeholder="Escribe un comentario o pregunta..." required style="background: var(--bg-tertiary); border-color: var(--border-color); color: var(--text-primary);"></textarea>
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
        <!-- Info del proyecto -->
        <div class="card mb-4 fade-in-up">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-calendar me-2"></i>Información</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha de inicio</small>
                    <strong><?= $proyecto['fecha_inicio'] ? formatDate($proyecto['fecha_inicio']) : 'No definida' ?></strong>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha estimada de entrega</small>
                    <strong><?= $proyecto['fecha_fin_estimada'] ? formatDate($proyecto['fecha_fin_estimada']) : 'No definida' ?></strong>
                </div>
                <?php if ($proyecto['fecha_fin_real']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Fecha de entrega real</small>
                    <strong class="text-success"><?= formatDate($proyecto['fecha_fin_real']) ?></strong>
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
                    <svg width="150" height="150" viewBox="0 0 150 150">
                        <circle cx="75" cy="75" r="60" fill="none" stroke="var(--bg-tertiary)" stroke-width="12"/>
                        <circle cx="75" cy="75" r="60" fill="none" stroke="var(--secondary-green)" stroke-width="12"
                                stroke-dasharray="<?= 377 * ($avanceProyecto / 100) ?> 377"
                                stroke-linecap="round"
                                transform="rotate(-90 75 75)"/>
                    </svg>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <div class="display-6 fw-bold" style="color: var(--text-primary);"><?= $avanceProyecto ?>%</div>
                    </div>
                </div>
                <p class="text-muted small mb-0">Progreso general del proyecto</p>
            </div>
        </div>
    </div>
</div>

