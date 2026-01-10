<?php
/**
 * AND PROJECTS APP - Ver Proyecto para Clientes (CX)
 */

require_once __DIR__ . '/../../../../ui/modules/proyectos/models/ProyectoModel.php';
require_once __DIR__ . '/../../../../ui/modules/tareas/models/TareaModel.php';
require_once __DIR__ . '/../../../../ui/modules/subtareas/models/SubtareaModel.php';
require_once __DIR__ . '/../../../../ui/modules/comentarios/models/ComentarioModel.php';
require_once __DIR__ . '/../../../../ui/models/TiempoModel.php';

$proyectoModel = new ProyectoModel();
$tareaModel = new TareaModel();
$subtareaModel = new SubtareaModel();
$comentarioModel = new ComentarioModel();
$tiempoModel = new TiempoModel();

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

// Obtener horas del proyecto
$horasProyecto = $tiempoModel->getHorasProyecto($id);
$porcentajeHoras = TiempoModel::calcularPorcentaje($horasProyecto['horas_reales'], $horasProyecto['horas_estimadas']);

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
                <div class="d-flex gap-2 align-items-center">
                    <?php if (!empty($tareas)): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalArbolDependencias">
                        <i class="bi bi-diagram-3 me-1"></i>Ver Árbol
                    </button>
                    <?php endif; ?>
                    <span class="badge bg-secondary"><?= count($tareas) ?> tareas</span>
                </div>
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
                        // Obtener horas de la tarea
                        $horasTareaItem = $tiempoModel->getHorasTarea($tarea['id']);
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
                                        <div class="d-flex align-items-center gap-3 mt-1 flex-wrap">
                                            <small class="text-muted"><?= $tarea['total_subtareas'] ?> subtareas</small>
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i><?= TiempoModel::formatHoras($horasTareaItem['horas_reales']) ?>
                                                <?php if ($horasTareaItem['horas_estimadas'] > 0): ?>
                                                / <?= TiempoModel::formatHoras($horasTareaItem['horas_estimadas']) ?>
                                                <?php endif; ?>
                                            </small>
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
        <div class="card mb-4 fade-in-up">
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
        
        <!-- Resumen de Horas -->
        <div class="card fade-in-up">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Horas</h6>
            </div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col-6">
                        <div class="h4 mb-0" style="color: var(--accent-info);"><?= TiempoModel::formatHoras($horasProyecto['horas_reales']) ?></div>
                        <small class="text-muted">Registradas</small>
                    </div>
                    <div class="col-6">
                        <div class="h4 mb-0" style="color: var(--accent-warning);"><?= TiempoModel::formatHoras($horasProyecto['horas_estimadas']) ?></div>
                        <small class="text-muted">Estimadas</small>
                    </div>
                </div>
                
                <?php if ($horasProyecto['horas_estimadas'] > 0): ?>
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height: 8px;">
                        <div class="progress-bar <?= $porcentajeHoras > 100 ? 'bg-danger' : '' ?>" style="width: <?= min($porcentajeHoras, 100) ?>%"></div>
                    </div>
                    <small class="text-muted"><?= $porcentajeHoras ?>%</small>
                </div>
                <p class="text-muted small mt-2 mb-0 text-center">
                    <?php if ($porcentajeHoras > 100): ?>
                    <i class="bi bi-exclamation-triangle text-danger me-1"></i>Se han excedido las horas estimadas
                    <?php else: ?>
                    Horas consumidas vs estimadas
                    <?php endif; ?>
                </p>
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
                            function renderDependencyTreeVisualCX($tareas, $allTareas, $dependencias, $isChild = false, &$renderedIds = []) {
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
                                    
                                    // URL de la tarea (CX)
                                    $tareaUrl = cxModuleUrl('tareas', 'ver', ['id' => $tarea['id']]);
                                    
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
                                        $output .= renderDependencyTreeVisualCX($children, $allTareas, $dependencias, true, $renderedIds);
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
                                echo '<small class="text-muted">El equipo puede definir dependencias para mostrar el flujo de trabajo.</small>';
                                echo '</div>';
                            } else {
                                $renderedIds = [];
                                echo renderDependencyTreeVisualCX($tareasRaiz, $tareas, $dependencias, false, $renderedIds);
                                
                                // Mostrar tareas huérfanas (que no están en ninguna jerarquía)
                                $tareasHuerfanas = array_filter($tareas, fn($t) => !in_array($t['id'], $renderedIds));
                                if (!empty($tareasHuerfanas)) {
                                    echo '<div class="mt-4 pt-3 border-top">';
                                    echo '<h6 class="text-muted mb-3"><i class="bi bi-box me-2"></i>Tareas sin dependencias</h6>';
                                    echo renderDependencyTreeVisualCX($tareasHuerfanas, $tareas, $dependencias, false, $renderedIds);
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
/* Asegurar que el modal esté sobre todo */
body.modal-open .cx-navbar {
    z-index: 999 !important;
}
#modalArbolDependencias {
    z-index: 9999 !important;
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
    if (!modal) return;
    
    // Mover el modal al final del body para evitar problemas de z-index
    document.body.appendChild(modal);
    
    // Crear backdrop personalizado
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop-custom';
    backdrop.id = 'customBackdropCX';
    backdrop.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);z-index:9998;backdrop-filter:blur(5px);display:none;';
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

