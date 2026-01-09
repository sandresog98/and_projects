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

// Crear un mapa de horas por proyecto para facilitar la combinación
$horasMap = [];
foreach ($horasPorProyecto as $horasProy) {
    $horasMap[$horasProy['id']] = $horasProy;
}

// Calcular avance real de cada proyecto y combinar con información de horas
foreach ($proyectos as &$proyecto) {
    if ($proyecto['estado'] == 3) {
        // Proyecto completado = 100%
        $proyecto['avance'] = 100;
    } else {
        // Obtener tareas del proyecto
        $tareas = $tareaModel->getByProyecto($proyecto['id']);
        if (count($tareas) > 0) {
            // Calcular basado en tareas completadas
            $tareasCompletadas = count(array_filter($tareas, fn($t) => $t['estado'] == 3));
            $proyecto['avance'] = round(($tareasCompletadas / count($tareas)) * 100);
        } elseif ($proyecto['estado'] == 2) {
            // En progreso sin tareas
            $proyecto['avance'] = 50;
        } else {
            // Usar valor de BD o 0
            $proyecto['avance'] = $proyecto['avance'] ?? 0;
        }
    }
    
    // Combinar con información de horas si existe
    if (isset($horasMap[$proyecto['id']])) {
        $proyecto['horas_reales'] = $horasMap[$proyecto['id']]['horas_reales'];
        $proyecto['horas_estimadas'] = $horasMap[$proyecto['id']]['horas_estimadas'];
        $proyecto['porcentaje_horas'] = TiempoModel::calcularPorcentaje($proyecto['horas_reales'], $proyecto['horas_estimadas']);
    } else {
        $proyecto['horas_reales'] = 0;
        $proyecto['horas_estimadas'] = 0;
        $proyecto['porcentaje_horas'] = 0;
    }
}
unset($proyecto); // Liberar referencia

// Obtener reuniones próximas
$reunionesProximas = $reunionModel->getProximas(14);
if ($empresaId) {
    $reunionesProximas = array_filter($reunionesProximas, fn($r) => $r['empresa_id'] == $empresaId || !empty($r['proyecto_id']));
}

// Estadísticas
$totalProyectos = count($proyectos);
$proyectosActivos = count(array_filter($proyectos, fn($p) => $p['estado'] == 2));
$proyectosCompletados = count(array_filter($proyectos, fn($p) => $p['estado'] == 3));
$avancePromedio = $totalProyectos > 0 ? round(array_sum(array_column($proyectos, 'avance')) / $totalProyectos, 1) : 0;

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

<!-- Mis Proyectos - Vista Unificada -->
<?php if ($empresaId): ?>
<div class="row g-4 mb-4">
    <div class="col-12 fade-in-up" style="animation-delay: 0.45s">
        <div class="card">
            <div class="card-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                <div>
                    <h6 class="mb-0"><i class="bi bi-kanban me-2"></i>Mis Proyectos</h6>
                    <small class="text-muted">Progreso y horas de tus proyectos</small>
                </div>
                <div class="d-flex gap-4">
                    <div class="text-center">
                        <div class="h5 h4-md mb-0" style="color: var(--accent-info);"><?= TiempoModel::formatHoras($horasEmpresa['horas_reales']) ?></div>
                        <small class="text-muted">Horas Registradas</small>
                    </div>
                    <div class="text-center">
                        <div class="h5 h4-md mb-0" style="color: var(--accent-warning);"><?= TiempoModel::formatHoras($horasEmpresa['horas_estimadas']) ?></div>
                        <small class="text-muted">Horas Estimadas</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($proyectos)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-folder-x text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">No hay proyectos disponibles</p>
                </div>
                <?php else: ?>
                <!-- Vista móvil: Lista de cards -->
                <div class="d-md-none p-3">
                    <?php foreach (array_slice($proyectos, 0, 5) as $proyecto): ?>
                    <div class="proyecto-card-mobile mb-3" onclick="window.location='<?= cxModuleUrl('proyectos', 'ver', ['id' => $proyecto['id']]) ?>'">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="status-dot <?= getStatusClass($proyecto['estado']) ?>"></div>
                            <div style="width: 10px; height: 10px; border-radius: 50%; background: <?= $proyecto['color'] ?? '#55A5C8' ?>;"></div>
                            <strong style="color: var(--text-primary); font-size: 14px;"><?= htmlspecialchars($proyecto['nombre']) ?></strong>
                            <span class="badge badge-status-<?= $proyecto['estado'] ?> ms-auto"><?= getStatusText($proyecto['estado']) ?></span>
                        </div>
                        
                        <!-- Avance del proyecto -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">Avance</small>
                                <strong style="font-size: 12px;"><?= $proyecto['avance'] ?>%</strong>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" style="width: <?= $proyecto['avance'] ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- Información de horas -->
                        <?php if ($proyecto['horas_estimadas'] > 0): ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1" style="color: var(--accent-info);"></i>
                                    <?= TiempoModel::formatHoras($proyecto['horas_reales']) ?> / <?= TiempoModel::formatHoras($proyecto['horas_estimadas']) ?>
                                </small>
                                <span class="<?= $proyecto['porcentaje_horas'] > 100 ? 'text-danger' : 'text-muted' ?>" style="font-size: 12px; font-weight: 600;" title="Porcentaje de horas consumidas"><?= $proyecto['porcentaje_horas'] ?>%</span>
                            </div>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar <?= $proyecto['porcentaje_horas'] > 100 ? 'bg-danger' : '' ?>" style="width: <?= min($proyecto['porcentaje_horas'], 100) ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Info adicional -->
                        <div class="d-flex gap-3 text-muted" style="font-size: 11px;">
                            <span><i class="bi bi-list-task me-1"></i><?= $proyecto['total_tareas'] ?? 0 ?> tareas</span>
                            <?php if ($proyecto['fecha_fin_estimada']): ?>
                            <span><i class="bi bi-flag me-1"></i><?= formatDate($proyecto['fecha_fin_estimada']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Vista desktop: Tabla unificada -->
                <div class="d-none d-md-block">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Proyecto</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Avance</th>
                                <th class="text-center">Tareas</th>
                                <th class="text-center">Horas</th>
                                <th class="text-center" style="width: 150px;">Horas Consumidas</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($proyectos, 0, 5) as $proyecto): ?>
                            <tr class="cursor-pointer" onclick="window.location='<?= cxModuleUrl('proyectos', 'ver', ['id' => $proyecto['id']]) ?>'">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="status-dot <?= getStatusClass($proyecto['estado']) ?>"></div>
                                        <div style="width: 10px; height: 10px; border-radius: 50%; background: <?= $proyecto['color'] ?? '#55A5C8' ?>;"></div>
                                        <div>
                                            <strong style="color: var(--text-primary);"><?= htmlspecialchars($proyecto['nombre']) ?></strong>
                                            <?php if ($proyecto['fecha_fin_estimada']): ?>
                                            <br><small class="text-muted">Entrega: <?= formatDate($proyecto['fecha_fin_estimada']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-status-<?= $proyecto['estado'] ?>">
                                        <?= getStatusText($proyecto['estado']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                        <div class="progress" style="width: 80px; height: 6px;">
                                            <div class="progress-bar" style="width: <?= $proyecto['avance'] ?>%"></div>
                                        </div>
                                        <small class="text-muted" style="min-width: 35px;"><?= $proyecto['avance'] ?>%</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="text-muted"><?= $proyecto['total_tareas'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($proyecto['horas_estimadas'] > 0): ?>
                                    <div>
                                        <span style="color: var(--accent-info);"><?= TiempoModel::formatHoras($proyecto['horas_reales']) ?></span>
                                        <span class="text-muted"> / </span>
                                        <span class="text-muted"><?= TiempoModel::formatHoras($proyecto['horas_estimadas']) ?></span>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($proyecto['horas_estimadas'] > 0): ?>
                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                        <div class="progress" style="width: 80px; height: 6px;">
                                            <div class="progress-bar <?= $proyecto['porcentaje_horas'] > 100 ? 'bg-danger' : '' ?>" style="width: <?= min($proyecto['porcentaje_horas'], 100) ?>%"></div>
                                        </div>
                                        <small class="text-muted" style="min-width: 35px;" title="Porcentaje de horas consumidas vs estimadas"><?= $proyecto['porcentaje_horas'] ?>%</small>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= cxModuleUrl('proyectos', 'ver', ['id' => $proyecto['id']]) ?>" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($proyectos) && count($proyectos) > 5): ?>
            <div class="card-footer bg-transparent border-top" style="border-color: var(--border-color) !important;">
                <div class="text-center">
                    <a href="<?= cxModuleUrl('proyectos') ?>" class="btn btn-sm btn-outline-primary">
                        Ver todos los proyectos (<?= count($proyectos) ?>)
                    </a>
                </div>
            </div>
            <?php endif; ?>
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
<?php endif; ?>

<div class="row g-4">
    <!-- Próximas reuniones -->
    <div class="col-12 col-lg-4 fade-in-up" style="animation-delay: 0.6s">
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

