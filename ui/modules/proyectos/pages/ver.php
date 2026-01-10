<?php
/**
 * AND PROJECTS APP - Ver Proyecto
 */

require_once __DIR__ . '/../models/ProyectoModel.php';
require_once __DIR__ . '/../../tareas/models/TareaModel.php';
require_once __DIR__ . '/../../subtareas/models/SubtareaModel.php';
require_once __DIR__ . '/../../comentarios/models/ComentarioModel.php';
require_once __DIR__ . '/../../../models/TiempoModel.php';

$model = new ProyectoModel();
$tareaModel = new TareaModel();
$subtareaModel = new SubtareaModel();
$comentarioModel = new ComentarioModel();
$tiempoModel = new TiempoModel();

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

// Obtener tareas del proyecto ordenadas por dependencias
$tareas = $tareaModel->getTareasOrdenadas($id);

// Obtener comentarios
$comentarios = $comentarioModel->getByEntidad('proyecto', $id);

// Calcular estadísticas
$totalTareas = count($tareas);
$tareasCompletadas = count(array_filter($tareas, fn($t) => $t['estado'] == 3));
$tareasEnProgreso = count(array_filter($tareas, fn($t) => $t['estado'] == 2));
$tareasPendientes = count(array_filter($tareas, fn($t) => $t['estado'] == 1));
$avance = $totalTareas > 0 ? round(($tareasCompletadas / $totalTareas) * 100) : 0;

// Obtener horas del proyecto
$horasProyecto = $tiempoModel->getHorasProyecto($id);
$porcentajeHoras = TiempoModel::calcularPorcentaje($horasProyecto['horas_reales'], $horasProyecto['horas_estimadas']);

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
                <div class="stat-value" style="color: var(--accent-success);"><?= $tareasCompletadas ?></div>
                <div class="stat-label">Completadas</div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.4s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-value" style="color: var(--accent-info);"><?= $tareasEnProgreso ?></div>
                <div class="stat-label">En Progreso</div>
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
                            <span class="fw-medium" style="color: var(--text-primary);">Horas del Proyecto</span>
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
                                <div class="h5 mb-0" style="color: var(--accent-info);"><?= TiempoModel::formatHoras($horasProyecto['horas_reales']) ?></div>
                                <small class="text-muted">Registradas</small>
                            </div>
                            <div class="text-center">
                                <div class="h5 mb-0" style="color: var(--accent-warning);"><?= TiempoModel::formatHoras($horasProyecto['horas_estimadas']) ?></div>
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
                <div class="d-flex gap-2">
                    <?php if (!empty($tareas)): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalArbolDependencias">
                        <i class="bi bi-diagram-3 me-1"></i>Ver Árbol
                    </button>
                    <?php endif; ?>
                    <?php if (hasPermission('tareas', 'crear')): ?>
                    <a href="<?= uiModuleUrl('tareas', 'crear', ['proyecto_id' => $id]) ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Nueva Tarea
                    </a>
                    <?php endif; ?>
                </div>
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
                        $nivelDep = $tarea['nivel_dependencia'] ?? 0;
                        $estaBloqueada = $tarea['bloqueada'] ?? false;
                        $predecesora = $tarea['predecesora'] ?? null;
                    ?>
                    <div class="list-group-item bg-transparent px-3 py-3 <?= $estaBloqueada ? 'opacity-75' : '' ?>" style="<?= $nivelDep > 0 ? 'margin-left: ' . ($nivelDep * 20) . 'px; border-left: 2px solid var(--border-color);' : '' ?>">
                        <!-- Indicador de dependencia -->
                        <?php if ($nivelDep > 0): ?>
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="bi bi-arrow-return-right me-1"></i>
                                Depende de: <strong><?= htmlspecialchars($predecesora['nombre'] ?? '') ?></strong>
                                <?php if ($estaBloqueada): ?>
                                <span class="badge bg-warning text-dark ms-2"><i class="bi bi-lock-fill me-1"></i>Bloqueada</span>
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Tarea Header -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3 flex-grow-1">
                                <?php if ($estaBloqueada): ?>
                                <i class="bi bi-lock-fill text-warning fs-5"></i>
                                <?php elseif ($tarea['estado'] == 3): ?>
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
                                    <div class="d-flex align-items-center gap-3 mt-1 flex-wrap">
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

<!-- Modal Árbol de Dependencias -->
<div class="modal fade" id="modalArbolDependencias" tabindex="-1" aria-labelledby="modalArbolDependenciasLabel" aria-hidden="true" style="z-index: 9999 !important;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="z-index: 10000 !important;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalArbolDependenciasLabel">
                    <i class="bi bi-diagram-3 me-2"></i>Árbol de Dependencias - <?= htmlspecialchars($proyecto['nombre']) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($tareas)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-diagram-3 text-muted" style="font-size: 48px;"></i>
                        <p class="text-muted mt-3">No hay tareas en este proyecto</p>
                    </div>
                <?php else: ?>
                    <div class="dependency-tree-container">
                        <div class="dependency-tree">
                            <?php
                            // Construir el árbol visual
                            function renderDependencyTreeVisual($tareas, $allTareas, $dependencias, $isChild = false, &$renderedIds = []) {
                                $output = '';
                                
                                foreach ($tareas as $tarea) {
                                    if (in_array($tarea['id'], $renderedIds)) continue;
                                    $renderedIds[] = $tarea['id'];
                                    
                                    $isBlocked = ($tarea['estado'] ?? 1) == 4;
                                    $isCompleted = ($tarea['estado'] ?? 1) == 3;
                                    $isInProgress = ($tarea['estado'] ?? 1) == 2;
                                    
                                    $statusClass = $isCompleted ? 'completed' : ($isInProgress ? 'in-progress' : ($isBlocked ? 'blocked' : 'pending'));
                                    $statusIcon = $isCompleted ? 'bi-check-circle-fill' : ($isBlocked ? 'bi-exclamation-circle-fill' : ($isInProgress ? 'bi-play-circle-fill' : 'bi-circle'));
                                    $statusColor = $isCompleted ? '#9AD082' : ($isBlocked ? '#dc3545' : ($isInProgress ? '#55A5C8' : '#6c757d'));
                                    
                                    // URL de la tarea
                                    $tareaUrl = uiModuleUrl('tareas', 'ver', ['id' => $tarea['id']]);
                                    
                                    // Buscar tareas que dependen de esta
                                    $children = [];
                                    foreach ($dependencias as $dep) {
                                        if ($dep['id_origen'] == $tarea['id']) {
                                            foreach ($allTareas as $t) {
                                                if ($t['id'] == $dep['id_destino'] && !in_array($t['id'], $renderedIds)) {
                                                    $children[] = $t;
                                                }
                                            }
                                        }
                                    }
                                    
                                    $hasChildren = !empty($children);
                                    $parentClass = $isChild ? 'has-parent' : '';
                                    
                                    $output .= '<div class="tree-node ' . $statusClass . ' ' . $parentClass . '">';
                                    $output .= '<a href="' . $tareaUrl . '" class="tree-node-content">';
                                    $output .= '<i class="bi ' . $statusIcon . ' tree-node-icon" style="color: ' . $statusColor . '"></i>';
                                    $output .= '<span class="tree-node-name" title="' . htmlspecialchars($tarea['nombre']) . '">' . htmlspecialchars($tarea['nombre']) . '</span>';
                                    $output .= '<span class="tree-node-badge badge-status-' . ($tarea['estado'] ?? 1) . '">' . getStatusText($tarea['estado'] ?? 1) . '</span>';
                                    if ($isBlocked) {
                                        $output .= '<span class="tree-node-warning" title="Requiere predecesora completada"><i class="bi bi-lock-fill"></i></span>';
                                    }
                                    if ($hasChildren) {
                                        $output .= '<span class="tree-node-children-count" title="Tareas dependientes"><i class="bi bi-arrow-down-short"></i>' . count($children) . '</span>';
                                    }
                                    $output .= '</a>';
                                    
                                    if ($hasChildren) {
                                        $output .= '<div class="tree-children">';
                                        $output .= renderDependencyTreeVisual($children, $allTareas, $dependencias, true, $renderedIds);
                                        $output .= '</div>';
                                    }
                                    
                                    $output .= '</div>';
                                }
                                
                                return $output;
                            }
                            
                            // Obtener dependencias
                            $sqlDeps = "SELECT id_origen, id_destino FROM proyectos_dependencias WHERE tipo_origen = 'tarea' AND tipo_destino = 'tarea'";
                            $stmtDeps = Database::getInstance()->query($sqlDeps);
                            $dependencias = $stmtDeps->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Identificar tareas raíz (no tienen predecesoras)
                            $tareasConPredecesora = array_column($dependencias, 'id_destino');
                            $tareasRaiz = array_filter($tareas, fn($t) => !in_array($t['id'], $tareasConPredecesora));
                            
                            // Si no hay dependencias definidas, mostrar mensaje
                            if (empty($dependencias)) {
                                echo '<div class="no-dependencies-msg">';
                                echo '<i class="bi bi-diagram-3"></i>';
                                echo '<p>No hay dependencias definidas entre las tareas.</p>';
                                echo '<small class="text-muted">Puedes crear dependencias al editar o crear tareas.</small>';
                                echo '</div>';
                            } else {
                                $renderedIds = [];
                                echo renderDependencyTreeVisual($tareasRaiz, $tareas, $dependencias, false, $renderedIds);
                                
                                // Mostrar tareas huérfanas (que no están en ninguna jerarquía)
                                $tareasHuerfanas = array_filter($tareas, fn($t) => !in_array($t['id'], $renderedIds));
                                if (!empty($tareasHuerfanas)) {
                                    echo '<div class="mt-4 pt-3 border-top">';
                                    echo '<h6 class="text-muted mb-3"><i class="bi bi-box me-2"></i>Tareas sin dependencias</h6>';
                                    echo renderDependencyTreeVisual($tareasHuerfanas, $tareas, $dependencias, false, $renderedIds);
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                        
                        <!-- Leyenda -->
                        <div class="tree-legend mt-4 pt-3 border-top">
                            <h6 class="mb-3" style="color: var(--text-secondary)">Leyenda</h6>
                            <div class="d-flex flex-wrap gap-4">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-circle" style="color: #6c757d"></i>
                                    <span>Pendiente</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-play-circle-fill" style="color: #55A5C8"></i>
                                    <span>En Progreso</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill" style="color: #9AD082"></i>
                                    <span>Completada</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-circle-fill" style="color: #dc3545"></i>
                                    <span>Bloqueada</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-lock-fill" style="color: #ffc107"></i>
                                    <span>Requiere predecesora</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Forzar z-index del modal sobre todo */
#modalArbolDependencias {
    z-index: 9999 !important;
}
#modalArbolDependencias .modal-dialog {
    z-index: 10000 !important;
}
#modalArbolDependencias .modal-content {
    z-index: 10001 !important;
}
/* Backdrop personalizado */
.modal-backdrop-custom {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9998 !important;
    backdrop-filter: blur(5px);
}

/* Estilos para el árbol de dependencias */
.dependency-tree-container {
    padding: 20px;
    background: var(--bg-secondary);
    border-radius: 12px;
    overflow-x: auto;
}

.dependency-tree {
    display: flex;
    flex-direction: column;
    gap: 0;
    min-width: fit-content;
    padding-left: 20px;
}

.tree-node {
    position: relative;
    padding: 6px 0;
}

/* Línea horizontal hacia el nodo */
.tree-node.has-parent::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 24px;
    width: 20px;
    height: 2px;
    background: linear-gradient(90deg, var(--border-color), var(--primary-blue));
}

/* Línea vertical conectora */
.tree-children > .tree-node::after {
    content: '';
    position: absolute;
    left: -20px;
    top: 0;
    width: 2px;
    height: 24px;
    background: var(--border-color);
}

.tree-children > .tree-node:last-child::after {
    height: 24px;
}

/* Link clickeable */
a.tree-node-content {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    text-decoration: none;
}

a.tree-node-content:hover {
    transform: translateX(8px);
    border-color: var(--primary-blue);
    box-shadow: 0 4px 20px rgba(85, 165, 200, 0.35);
}

a.tree-node-content:hover .tree-node-name {
    color: var(--primary-blue);
}

.tree-node.completed a.tree-node-content {
    border-color: rgba(154, 208, 130, 0.5);
    background: rgba(154, 208, 130, 0.05);
}

.tree-node.completed a.tree-node-content:hover {
    border-color: #9AD082;
    box-shadow: 0 4px 20px rgba(154, 208, 130, 0.35);
}

.tree-node.blocked a.tree-node-content {
    border-color: rgba(220, 53, 69, 0.5);
    background: rgba(220, 53, 69, 0.05);
}

.tree-node.blocked a.tree-node-content:hover {
    border-color: #dc3545;
    box-shadow: 0 4px 20px rgba(220, 53, 69, 0.35);
}

.tree-node.in-progress a.tree-node-content {
    border-color: rgba(85, 165, 200, 0.5);
    background: rgba(85, 165, 200, 0.05);
}

.tree-node-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.tree-node-name {
    font-weight: 500;
    color: var(--text-primary);
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: color 0.3s ease;
}

.tree-node-badge {
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
    flex-shrink: 0;
}

.tree-node-warning {
    color: #ffc107;
    font-size: 14px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.tree-children {
    margin-top: 0;
    padding-left: 40px;
    position: relative;
}

/* Línea vertical que conecta todos los hijos */
.tree-children::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    width: 2px;
    height: calc(100% - 24px);
    background: var(--border-color);
}

.tree-legend {
    color: var(--text-secondary);
}

.tree-legend span {
    color: var(--text-primary);
    font-size: 14px;
}

/* Sin dependencias */
.no-dependencies-msg {
    text-align: center;
    padding: 30px;
    color: var(--text-muted);
}

.no-dependencies-msg i {
    font-size: 48px;
    margin-bottom: 15px;
    display: block;
    opacity: 0.5;
}

/* Contador de sucesores */
.tree-node-children-count {
    font-size: 11px;
    color: var(--text-muted);
    background: var(--bg-secondary);
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: auto;
}

/* Responsive */
@media (max-width: 768px) {
    .tree-children {
        padding-left: 25px;
    }
    
    .tree-children::before {
        left: 5px;
    }
    
    .tree-node.has-parent::before {
        left: -20px;
        width: 15px;
    }
    
    a.tree-node-content {
        padding: 10px 15px;
        gap: 8px;
    }
    
    .tree-node-name {
        font-size: 14px;
        max-width: 150px;
    }
    
    .tree-node-badge {
        font-size: 10px;
        padding: 3px 8px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalArbolDependencias');
    
    // Mover el modal al final del body para evitar problemas de z-index
    document.body.appendChild(modal);
    
    // Crear backdrop personalizado
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop-custom';
    backdrop.id = 'customBackdrop';
    backdrop.style.display = 'none';
    document.body.appendChild(backdrop);
    
    // Cuando se abre el modal
    modal.addEventListener('show.bs.modal', function() {
        backdrop.style.display = 'block';
        // Ocultar backdrop de Bootstrap
        setTimeout(() => {
            const bsBackdrop = document.querySelector('.modal-backdrop:not(.modal-backdrop-custom)');
            if (bsBackdrop) {
                bsBackdrop.style.display = 'none';
            }
        }, 10);
    });
    
    // Cuando se cierra el modal
    modal.addEventListener('hidden.bs.modal', function() {
        backdrop.style.display = 'none';
    });
    
    // Cerrar al hacer clic en el backdrop personalizado
    backdrop.addEventListener('click', function() {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    });
});
</script>

