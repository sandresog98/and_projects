<?php
/**
 * AND PROJECTS APP - Dashboard para Clientes (CX)
 */

require_once __DIR__ . '/../../ui/modules/proyectos/models/ProyectoModel.php';
require_once __DIR__ . '/../../ui/modules/reuniones/models/ReunionModel.php';
require_once __DIR__ . '/../../ui/modules/empresas/models/EmpresaModel.php';
require_once __DIR__ . '/../../ui/models/TiempoModel.php';
require_once __DIR__ . '/../../ui/modules/tareas/models/TareaModel.php';

$proyectoModel = new ProyectoModel();
$reunionModel = new ReunionModel();
$empresaModel = new EmpresaModel();
$tiempoModel = new TiempoModel();
$tareaModel = new TareaModel();

$empresaId = getCurrentClientEmpresaId();
$empresa = $empresaId ? $empresaModel->getById($empresaId) : null;

// Obtener horas de la empresa
$horasEmpresa = $empresaId ? $tiempoModel->getHorasEmpresa($empresaId) : ['horas_reales' => 0, 'horas_estimadas' => 0];
$horasPorProyecto = $empresaId ? $tiempoModel->getResumenPorProyecto($empresaId) : [];

// Obtener proyectos del cliente
$proyectos = $proyectoModel->getAll([
    'empresa_id' => $empresaId,
    'exclude_cancelled' => true
]);

// Obtener total de subtareas por proyecto
require_once __DIR__ . '/../../config/database.php';
$subtareasPorProyecto = [];
if ($empresaId && !empty($proyectos)) {
    $db = Database::getInstance();
    $proyectoIds = array_column($proyectos, 'id');
    if (!empty($proyectoIds)) {
        $placeholders = implode(',', array_fill(0, count($proyectoIds), '?'));
        $stmt = $db->prepare("
            SELECT 
                t.proyecto_id,
                COUNT(s.id) as total_subtareas
            FROM proyectos_tareas t
            LEFT JOIN proyectos_subtareas s ON t.id = s.tarea_id AND s.estado != 5
            WHERE t.proyecto_id IN ($placeholders)
            GROUP BY t.proyecto_id
        ");
        $stmt->execute($proyectoIds);
        $result = $stmt->fetchAll();
        foreach ($result as $row) {
            $subtareasPorProyecto[$row['proyecto_id']] = (int)$row['total_subtareas'];
        }
    }
}

// Combinar datos de proyectos con datos de horas, subtareas y calcular avance real
$proyectosConHoras = [];
$avancesCalculados = [];

foreach ($proyectos as $proyecto) {
    $horasProy = array_filter($horasPorProyecto, fn($h) => $h['id'] == $proyecto['id']);
    $horasProy = !empty($horasProy) ? reset($horasProy) : ['horas_reales' => 0, 'horas_estimadas' => 0, 'total_tareas' => 0];
    
    // Calcular avance real del proyecto (igual que en ver.php)
    $tareas = $tareaModel->getByProyecto($proyecto['id']);
    if ($proyecto['estado'] == 3) {
        // Proyecto completado = 100%
        $avanceCalculado = 100;
    } elseif (count($tareas) > 0) {
        // Calcular basado en tareas completadas
        $tareasCompletadas = count(array_filter($tareas, fn($t) => $t['estado'] == 3));
        $avanceCalculado = round(($tareasCompletadas / count($tareas)) * 100);
    } elseif ($proyecto['estado'] == 2) {
        // En progreso sin tareas
        $avanceCalculado = 50;
    } else {
        // Usar valor de BD o 0
        $avanceCalculado = (float)($proyecto['avance'] ?? 0);
    }
    
    $avancesCalculados[] = $avanceCalculado;
    
    $proyectosConHoras[] = array_merge($proyecto, [
        'avance' => $avanceCalculado, // Usar el avance calculado
        'horas_reales' => $horasProy['horas_reales'] ?? 0,
        'horas_estimadas' => $horasProy['horas_estimadas'] ?? 0,
        'total_tareas' => $horasProy['total_tareas'] ?? ($proyecto['total_tareas'] ?? 0),
        'total_subtareas' => $subtareasPorProyecto[$proyecto['id']] ?? 0
    ]);
}

// Obtener reuniones próximas
$reunionesProximas = $reunionModel->getProximas(14);
if ($empresaId) {
    $reunionesProximas = array_filter($reunionesProximas, fn($r) => $r['empresa_id'] == $empresaId || !empty($r['proyecto_id']));
}

// Estadísticas
$totalProyectos = count($proyectos);
$proyectosActivos = count(array_filter($proyectos, fn($p) => $p['estado'] == 2));
$proyectosCompletados = count(array_filter($proyectos, fn($p) => $p['estado'] == 3));

// Calcular avance promedio usando los avances calculados
$avancePromedio = count($avancesCalculados) > 0 ? round(array_sum($avancesCalculados) / count($avancesCalculados), 1) : 0;

// Funciones helper para estados
if (!function_exists('getStatusText')) {
    function getStatusText($estado): string {
        return match((int)$estado) {
            1 => 'Pendiente',
            2 => 'En Progreso',
            3 => 'Completado',
            4 => 'Bloqueado',
            5 => 'Cancelado',
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

<!-- Banner de bienvenida -->
<div class="welcome-banner fade-in-up">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2>¡Hola, <?= htmlspecialchars(explode(' ', $currentClient['nombre'])[0]) ?>!</h2>
            <p class="text-muted mb-0">
                Bienvenido al portal de proyectos
                <?php if ($empresa): ?>
                de <strong><?= htmlspecialchars($empresa['nombre']) ?></strong>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($empresa && $empresa['logo']): ?>
        <div class="col-md-4 text-end d-none d-md-block">
            <img src="<?= UPLOADS_URL . '/' . $empresa['logo'] ?>" alt="<?= htmlspecialchars($empresa['nombre']) ?>" class="company-logo">
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Estadísticas -->
<div class="row g-4 mb-4">
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.1s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="display-5 fw-bold mb-2" style="color: var(--accent-info);"><?= $totalProyectos ?></div>
                <div class="text-muted small">Proyectos Totales</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.2s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="display-5 fw-bold mb-2" style="color: var(--accent-info);"><?= $proyectosActivos ?></div>
                <div class="text-muted small">En Progreso</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.3s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="display-5 fw-bold mb-2" style="color: var(--accent-success);"><?= $proyectosCompletados ?></div>
                <div class="text-muted small">Completados</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-in-up" style="animation-delay: 0.4s">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="display-5 fw-bold mb-2" style="color: var(--text-primary);"><?= $avancePromedio ?>%</div>
                <div class="text-muted small">Avance Promedio</div>
            </div>
        </div>
    </div>
</div>

<!-- Mis Proyectos y Resumen de Horas (Vista Unificada) -->
<div class="row g-4 mb-4">
    <div class="col-12 fade-in-up" style="animation-delay: 0.45s">
        <div class="card">
            <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                <div>
                    <h6 class="mb-0"><i class="bi bi-kanban me-2"></i>Mis Proyectos</h6>
                    <small class="text-muted">Progreso y detalles de tus proyectos</small>
                </div>
                <a href="<?= cxModuleUrl('proyectos') ?>" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($proyectosConHoras)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-folder-x text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">No hay proyectos disponibles</p>
                </div>
                <?php else: ?>
                <!-- Vista móvil: Lista de cards -->
                <div class="d-md-none p-3">
                    <?php foreach (array_slice($proyectosConHoras, 0, 5) as $proy): ?>
                    <?php 
                    $porcentajeHoras = TiempoModel::calcularPorcentaje($proy['horas_reales'], $proy['horas_estimadas']);
                    ?>
                    <div class="proyecto-card-mobile mb-3" onclick="window.location='<?= cxModuleUrl('proyectos', 'ver', ['id' => $proy['id']]) ?>'">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width: 10px; height: 10px; border-radius: 50%; background: <?= $proy['color'] ?? '#55A5C8' ?>;"></div>
                                <strong style="color: var(--text-primary); font-size: 14px;"><?= htmlspecialchars($proy['nombre']) ?></strong>
                            </div>
                            <span class="badge badge-status-<?= $proy['estado'] ?>">
                                <?= getStatusText($proy['estado']) ?>
                            </span>
                        </div>
                        
                        <!-- Avance del proyecto -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Avance del Proyecto</small>
                                <strong style="font-size: 12px;"><?= number_format($proy['avance'], 1) ?>%</strong>
                            </div>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar" style="width: <?= min(100, max(0, $proy['avance'])) ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- Tareas y Subtareas (Principal) -->
                        <div class="d-flex gap-3 mb-2">
                            <div class="d-flex align-items-center gap-1">
                                <i class="bi bi-list-task" style="color: var(--accent-info); font-size: 14px;"></i>
                                <strong style="font-size: 13px; color: var(--text-primary);"><?= $proy['total_tareas'] ?></strong>
                                <span class="text-muted" style="font-size: 12px;">tareas</span>
                            </div>
                            <?php if ($proy['total_subtareas'] > 0): ?>
                            <div class="d-flex align-items-center gap-1">
                                <i class="bi bi-list-check" style="color: var(--accent-success); font-size: 14px;"></i>
                                <strong style="font-size: 13px; color: var(--text-primary);"><?= $proy['total_subtareas'] ?></strong>
                                <span class="text-muted" style="font-size: 12px;">subtareas</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Horas (Secundario - más pequeño) -->
                        <?php if ($proy['horas_estimadas'] > 0 || $proy['horas_reales'] > 0): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted" style="font-size: 11px;">
                                <i class="bi bi-clock me-1"></i>
                                <?= TiempoModel::formatHoras($proy['horas_reales']) ?> / <?= TiempoModel::formatHoras($proy['horas_estimadas']) ?>
                            </span>
                            <?php if ($proy['horas_estimadas'] > 0): ?>
                            <span class="<?= $porcentajeHoras > 100 ? 'text-danger' : 'text-muted' ?>" style="font-size: 11px;"><?= $porcentajeHoras ?>%</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Vista desktop: Tabla unificada -->
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Proyecto</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Avance</th>
                                    <th class="text-center" style="min-width: 100px;">
                                        <i class="bi bi-list-task me-1"></i>Tareas
                                    </th>
                                    <th class="text-center" style="min-width: 120px;">
                                        <i class="bi bi-list-check me-1"></i>Subtareas
                                    </th>
                                    <th class="text-center" style="min-width: 140px; font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">
                                        <i class="bi bi-clock me-1"></i>Horas
                                    </th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($proyectosConHoras, 0, 5) as $proy): ?>
                                <?php 
                                $porcentajeHoras = TiempoModel::calcularPorcentaje($proy['horas_reales'], $proy['horas_estimadas']);
                                ?>
                                <tr class="cursor-pointer" onclick="window.location='<?= cxModuleUrl('proyectos', 'ver', ['id' => $proy['id']]) ?>'">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width: 10px; height: 10px; border-radius: 50%; background: <?= $proy['color'] ?? '#55A5C8' ?>;"></div>
                                            <div>
                                                <strong style="color: var(--text-primary);"><?= htmlspecialchars($proy['nombre']) ?></strong>
                                                <?php if ($proy['fecha_fin_estimada']): ?>
                                                <br><small class="text-muted">Entrega: <?= formatDate($proy['fecha_fin_estimada']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-status-<?= $proy['estado'] ?>">
                                            <?= getStatusText($proy['estado']) ?>
                                        </span>
                                    </td>
                                    <td style="width: 150px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar" style="width: <?= min(100, max(0, $proy['avance'])) ?>%"></div>
                                            </div>
                                            <small class="text-muted" style="min-width: 40px;"><?= number_format($proy['avance'], 1) ?>%</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center">
                                            <strong style="font-size: 1.1rem; color: var(--accent-info);"><?= $proy['total_tareas'] ?></strong>
                                            <small class="text-muted" style="font-size: 0.75rem;">tareas</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center">
                                            <strong style="font-size: 1.1rem; color: var(--accent-success);"><?= $proy['total_subtareas'] ?></strong>
                                            <small class="text-muted" style="font-size: 0.75rem;">subtareas</small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($proy['horas_estimadas'] > 0 || $proy['horas_reales'] > 0): ?>
                                        <div class="d-flex flex-column align-items-center" style="font-size: 0.85rem;">
                                            <div>
                                                <span style="color: var(--accent-info);"><?= TiempoModel::formatHoras($proy['horas_reales']) ?></span>
                                                <span class="text-muted"> / </span>
                                                <span class="text-muted"><?= TiempoModel::formatHoras($proy['horas_estimadas']) ?></span>
                                            </div>
                                            <?php if ($proy['horas_estimadas'] > 0): ?>
                                            <small class="<?= $porcentajeHoras > 100 ? 'text-danger' : 'text-muted' ?>" style="font-size: 0.7rem;">
                                                <?= $porcentajeHoras ?>%
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= cxModuleUrl('proyectos', 'ver', ['id' => $proy['id']]) ?>" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.proyecto-card-mobile {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    padding: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.proyecto-card-mobile:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.15);
}
</style>

<!-- Próximas reuniones -->
<div class="row g-4">
    <div class="col-lg-4 fade-in-up" style="animation-delay: 0.6s">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Próximas Reuniones</h6>
                <a href="<?= cxModuleUrl('calendario') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-calendar3"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($reunionesProximas)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 36px;"></i>
                    <p class="text-muted mt-2 small">Sin reuniones próximas</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach (array_slice($reunionesProximas, 0, 4) as $reunion): ?>
                    <div class="list-group-item bg-transparent border-0 border-bottom" style="border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="d-block"><?= htmlspecialchars($reunion['titulo']) ?></strong>
                                <small class="text-muted">
                                    <?= formatDate($reunion['fecha'], 'd M') ?> • <?= date('H:i', strtotime($reunion['hora_inicio'])) ?>
                                </small>
                            </div>
                            <?php
                            $badgeClass = match($reunion['tipo']) {
                                'virtual' => 'info',
                                'hibrida' => 'warning',
                                default => 'primary'
                            };
                            ?>
                            <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($reunion['tipo']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

