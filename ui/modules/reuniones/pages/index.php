<?php
/**
 * AND PROJECTS APP - Listado de Reuniones con Calendario
 */

$pageTitle = 'Reuniones';
$pageSubtitle = 'Gestión de reuniones y calendario';

require_once __DIR__ . '/../models/ReunionModel.php';
require_once __DIR__ . '/../../empresas/models/EmpresaModel.php';
require_once __DIR__ . '/../../proyectos/models/ProyectoModel.php';

$model = new ReunionModel();
$empresaModel = new EmpresaModel();
$proyectoModel = new ProyectoModel();

// Modo de vista
$vista = $_GET['vista'] ?? 'calendario';

// Filtros
$search = $_GET['search'] ?? '';
$empresa_id = $_GET['empresa_id'] ?? '';
$proyecto_id = $_GET['proyecto_id'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d', strtotime('+30 days'));

$filters = [];
if ($empresa_id) $filters['empresa_id'] = (int)$empresa_id;
if ($proyecto_id) $filters['proyecto_id'] = (int)$proyecto_id;
$filters['fecha_desde'] = $fecha_desde;
$filters['fecha_hasta'] = $fecha_hasta;

$reuniones = $model->getAll($filters);
$empresas = $empresaModel->getActiveForSelect();
$proyectos = $proyectoModel->getAll(['exclude_cancelled' => true]);

// Reuniones de hoy
$reunionesHoy = $model->getHoy();
?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
    <div>
        <h5 class="mb-0">Reuniones</h5>
        <small class="text-muted"><?= count($reuniones) ?> reuniones en el período</small>
    </div>
    <div class="d-flex gap-2">
        <!-- Toggle de vista -->
        <div class="btn-group">
            <a href="<?= uiModuleUrl('reuniones', 'index', ['vista' => 'calendario']) ?>" 
               class="btn btn-outline-secondary <?= $vista === 'calendario' ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i>
            </a>
            <a href="<?= uiModuleUrl('reuniones', 'index', ['vista' => 'lista']) ?>" 
               class="btn btn-outline-secondary <?= $vista === 'lista' ? 'active' : '' ?>">
                <i class="bi bi-list-ul"></i>
            </a>
        </div>
        
        <?php if (hasPermission('reuniones', 'crear')): ?>
        <a href="<?= uiModuleUrl('reuniones', 'crear') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Nueva Reunión
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Reuniones de hoy -->
    <div class="col-lg-4">
        <div class="card fade-in-up">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Hoy</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($reunionesHoy)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 32px;"></i>
                    <p class="text-muted mt-2 mb-0 small">Sin reuniones para hoy</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($reunionesHoy as $reunion): ?>
                    <a href="<?= uiModuleUrl('reuniones', 'ver', ['id' => $reunion['id']]) ?>" class="list-group-item list-group-item-action bg-transparent">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-start gap-2">
                                <?php if (!empty($reunion['empresa_logo'])): ?>
                                <img src="<?= UPLOADS_URL . '/' . $reunion['empresa_logo'] ?>" 
                                     alt="<?= htmlspecialchars($reunion['empresa_nombre']) ?>" 
                                     class="rounded mt-1" style="width: 24px; height: 24px; object-fit: contain; background: #fff;">
                                <?php elseif ($reunion['empresa_nombre']): ?>
                                <div class="rounded d-flex align-items-center justify-content-center mt-1" 
                                     style="width: 24px; height: 24px; background: var(--bg-tertiary);">
                                    <i class="bi bi-building text-muted" style="font-size: 12px;"></i>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <strong class="d-block"><?= htmlspecialchars($reunion['titulo']) ?></strong>
                                    <small class="text-muted">
                                        <?= $reunion['empresa_nombre'] ?? $reunion['proyecto_nombre'] ?? 'General' ?>
                                    </small>
                                </div>
                            </div>
                            <span class="badge bg-primary"><?= date('H:i', strtotime($reunion['hora_inicio'])) ?></span>
                        </div>
                        <div class="mt-2 small text-muted">
                            <i class="bi bi-hourglass-split me-1"></i><?= $reunion['duracion_minutos'] ?> min
                            <?php if ($reunion['ubicacion']): ?>
                            <span class="ms-2"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars(substr($reunion['ubicacion'], 0, 30)) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Próximas reuniones -->
        <div class="card fade-in-up mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-clock me-2"></i>Próximas 7 días</h6>
            </div>
            <div class="card-body p-0">
                <?php 
                $proximas = $model->getProximas(7);
                $proximas = array_filter($proximas, fn($r) => $r['fecha'] > date('Y-m-d'));
                ?>
                <?php if (empty($proximas)): ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-0 small">Sin reuniones próximas</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach (array_slice($proximas, 0, 5) as $reunion): ?>
                    <a href="<?= uiModuleUrl('reuniones', 'ver', ['id' => $reunion['id']]) ?>" class="list-group-item list-group-item-action bg-transparent">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($reunion['empresa_logo'])): ?>
                                <img src="<?= UPLOADS_URL . '/' . $reunion['empresa_logo'] ?>" 
                                     alt="<?= htmlspecialchars($reunion['empresa_nombre']) ?>" 
                                     class="rounded" style="width: 20px; height: 20px; object-fit: contain; background: #fff;">
                                <?php elseif ($reunion['empresa_nombre']): ?>
                                <div class="rounded d-flex align-items-center justify-content-center" 
                                     style="width: 20px; height: 20px; background: var(--bg-tertiary);">
                                    <i class="bi bi-building text-muted" style="font-size: 10px;"></i>
                                </div>
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($reunion['titulo']) ?></strong>
                            </div>
                            <small class="text-muted"><?= formatDate($reunion['fecha'], 'd M') ?></small>
                        </div>
                        <small class="text-muted ms-4"><?= date('H:i', strtotime($reunion['hora_inicio'])) ?></small>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Calendario o Lista -->
    <div class="col-lg-8">
        <?php if ($vista === 'calendario'): ?>
        <!-- Vista Calendario -->
        <div class="card fade-in-up">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
        
        <!-- FullCalendar -->
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/es.global.min.js"></script>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                events: <?= json_encode($model->getParaCalendario($fecha_desde, $fecha_hasta)) ?>,
                eventClick: function(info) {
                    window.location.href = '<?= uiModuleUrl('reuniones', 'ver') ?>&id=' + info.event.id;
                },
                eventDidMount: function(info) {
                    // Tooltip con información
                    info.el.setAttribute('title', info.event.extendedProps.ubicacion || '');
                }
            });
            calendar.render();
        });
        </script>
        
        <style>
            #calendar {
                --fc-border-color: var(--border-color);
                --fc-button-bg-color: var(--primary-blue);
                --fc-button-border-color: var(--primary-blue);
                --fc-button-hover-bg-color: var(--dark-blue);
                --fc-button-hover-border-color: var(--dark-blue);
                --fc-button-active-bg-color: var(--dark-blue);
                --fc-button-active-border-color: var(--dark-blue);
                --fc-today-bg-color: rgba(85, 165, 200, 0.1);
                --fc-event-bg-color: var(--primary-blue);
                --fc-event-border-color: var(--primary-blue);
            }
            
            .fc-theme-standard td, .fc-theme-standard th {
                border-color: var(--border-color);
            }
            
            .fc-daygrid-day-number, .fc-col-header-cell-cushion {
                color: var(--text-primary);
            }
        </style>
        
        <?php else: ?>
        <!-- Vista Lista -->
        <div class="card fade-in-up">
            <div class="card-header">
                <h6 class="mb-0">Listado de Reuniones</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($reuniones)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 48px;"></i>
                    <p class="text-muted mt-3">No hay reuniones en este período</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Reunión</th>
                                <th>Fecha y Hora</th>
                                <th>Duración</th>
                                <th>Tipo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reuniones as $reunion): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-start gap-2">
                                        <?php if (!empty($reunion['empresa_logo'])): ?>
                                        <img src="<?= UPLOADS_URL . '/' . $reunion['empresa_logo'] ?>" 
                                             alt="<?= htmlspecialchars($reunion['empresa_nombre']) ?>" 
                                             class="rounded mt-1" style="width: 28px; height: 28px; object-fit: contain; background: #fff;">
                                        <?php elseif ($reunion['empresa_nombre']): ?>
                                        <div class="rounded d-flex align-items-center justify-content-center mt-1" 
                                             style="width: 28px; height: 28px; background: var(--bg-tertiary);">
                                            <i class="bi bi-building text-muted" style="font-size: 14px;"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= htmlspecialchars($reunion['titulo']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?= $reunion['empresa_nombre'] ?? $reunion['proyecto_nombre'] ?? 'General' ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= formatDate($reunion['fecha']) ?>
                                    <br>
                                    <small class="text-muted"><?= date('H:i', strtotime($reunion['hora_inicio'])) ?></small>
                                </td>
                                <td><?= $reunion['duracion_minutos'] ?> min</td>
                                <td>
                                    <?php
                                    $tipoClass = match($reunion['tipo']) {
                                        'virtual' => 'info',
                                        'hibrida' => 'warning',
                                        default => 'primary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $tipoClass ?>"><?= ucfirst($reunion['tipo']) ?></span>
                                </td>
                                <td>
                                    <a href="<?= uiModuleUrl('reuniones', 'ver', ['id' => $reunion['id']]) ?>" class="btn btn-sm btn-outline-primary">
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
        </div>
        <?php endif; ?>
    </div>
</div>

