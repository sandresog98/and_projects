<?php
/**
 * AND PROJECTS APP - Calendario para Clientes (CX)
 */

$pageTitle = 'Calendario';
$pageSubtitle = 'Reuniones y fechas de entrega';

require_once __DIR__ . '/../../../../ui/modules/reuniones/models/ReunionModel.php';
require_once __DIR__ . '/../../../../ui/modules/proyectos/models/ProyectoModel.php';
require_once __DIR__ . '/../../../../ui/modules/tareas/models/TareaModel.php';

$reunionModel = new ReunionModel();
$proyectoModel = new ProyectoModel();
$tareaModel = new TareaModel();

$empresaId = getCurrentClientEmpresaId();

// Obtener reuniones (próximos 60 días)
$fechaDesde = date('Y-m-d');
$fechaHasta = date('Y-m-d', strtotime('+60 days'));

$reuniones = $reunionModel->getAll([
    'empresa_id' => $empresaId,
    'fecha_desde' => $fechaDesde,
    'fecha_hasta' => $fechaHasta
]);

// Obtener proyectos con fecha de entrega
$proyectos = $proyectoModel->getAll([
    'empresa_id' => $empresaId,
    'exclude_cancelled' => true
]);

// Obtener tareas con fecha de entrega
$tareas = [];
foreach ($proyectos as $proyecto) {
    $tareasProyecto = $tareaModel->getByProyecto($proyecto['id']);
    foreach ($tareasProyecto as $tarea) {
        if ($tarea['fecha_fin_estimada']) {
            $tarea['proyecto_nombre'] = $proyecto['nombre'];
            $tareas[] = $tarea;
        }
    }
}

// Construir eventos para el calendario
$eventos = [];

// Reuniones
foreach ($reuniones as $reunion) {
    $eventos[] = [
        'id' => 'reunion_' . $reunion['id'],
        'title' => '📅 ' . $reunion['titulo'],
        'start' => $reunion['fecha'] . 'T' . $reunion['hora_inicio'],
        'end' => $reunion['fecha'] . 'T' . date('H:i:s', strtotime($reunion['hora_inicio']) + ($reunion['duracion_minutos'] * 60)),
        'color' => match($reunion['tipo']) {
            'virtual' => '#17a2b8',
            'hibrida' => '#ffc107',
            default => '#55A5C8'
        },
        'extendedProps' => [
            'tipo' => 'reunion',
            'subtipo' => $reunion['tipo'],
            'ubicacion' => $reunion['ubicacion']
        ]
    ];
}

// Fechas de entrega de proyectos
foreach ($proyectos as $proyecto) {
    if ($proyecto['fecha_fin_estimada']) {
        $eventos[] = [
            'id' => 'proyecto_' . $proyecto['id'],
            'title' => '🎯 Entrega: ' . $proyecto['nombre'],
            'start' => $proyecto['fecha_fin_estimada'],
            'allDay' => true,
            'color' => $proyecto['estado'] == 3 ? '#9AD082' : '#6A0DAD',
            'extendedProps' => [
                'tipo' => 'proyecto',
                'estado' => $proyecto['estado']
            ]
        ];
    }
}

// Fechas de entrega de tareas
foreach ($tareas as $tarea) {
    $eventos[] = [
        'id' => 'tarea_' . $tarea['id'],
        'title' => '📋 ' . $tarea['nombre'],
        'start' => $tarea['fecha_fin_estimada'],
        'allDay' => true,
        'color' => $tarea['estado'] == 3 ? '#9AD082' : '#35719E',
        'extendedProps' => [
            'tipo' => 'tarea',
            'proyecto' => $tarea['proyecto_nombre'],
            'estado' => $tarea['estado']
        ]
    ];
}
?>

<div class="mb-4 fade-in-up">
    <h4 class="mb-2">Calendario</h4>
    <p class="text-muted mb-0">Reuniones programadas y fechas de entrega</p>
</div>

<div class="row g-4">
    <div class="col-lg-8 fade-in-up">
        <div class="card">
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 fade-in-up">
        <!-- Leyenda -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-palette me-2"></i>Leyenda</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width: 16px; height: 16px; background: #55A5C8; border-radius: 4px;"></div>
                    <small style="color: var(--text-primary);">Reunión presencial</small>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width: 16px; height: 16px; background: #17a2b8; border-radius: 4px;"></div>
                    <small style="color: var(--text-primary);">Reunión virtual</small>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width: 16px; height: 16px; background: #ffc107; border-radius: 4px;"></div>
                    <small style="color: var(--text-primary);">Reunión híbrida</small>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width: 16px; height: 16px; background: #6A0DAD; border-radius: 4px;"></div>
                    <small style="color: var(--text-primary);">Entrega de proyecto</small>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width: 16px; height: 16px; background: #35719E; border-radius: 4px;"></div>
                    <small style="color: var(--text-primary);">Entrega de tarea</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 16px; height: 16px; background: #9AD082; border-radius: 4px;"></div>
                    <small style="color: var(--text-primary);">Completado</small>
                </div>
            </div>
        </div>
        
        <!-- Próximos eventos -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-clock me-2"></i>Próximos 7 días</h6>
            </div>
            <div class="card-body p-0">
                <?php
                // Filtrar eventos de los próximos 7 días
                $proximosEventos = array_filter($eventos, function($e) {
                    $fechaEvento = strtotime(explode('T', $e['start'])[0]);
                    $hoy = strtotime(date('Y-m-d'));
                    $en7dias = strtotime('+7 days');
                    return $fechaEvento >= $hoy && $fechaEvento <= $en7dias;
                });
                
                usort($proximosEventos, function($a, $b) {
                    return strcmp($a['start'], $b['start']);
                });
                ?>
                
                <?php if (empty($proximosEventos)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-calendar-check text-muted" style="font-size: 32px;"></i>
                    <p class="text-muted mt-2 mb-0 small">Sin eventos próximos</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach (array_slice($proximosEventos, 0, 6) as $evento): ?>
                    <div class="list-group-item bg-transparent" style="border-color: var(--border-color);">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 10px; height: 10px; background: <?= $evento['color'] ?>; border-radius: 50%;"></div>
                            <div class="flex-grow-1">
                                <strong class="d-block small"><?= htmlspecialchars(str_replace(['📅 ', '🎯 ', '📋 '], '', $evento['title'])) ?></strong>
                                <small class="text-muted">
                                    <?= date('d M', strtotime(explode('T', $evento['start'])[0])) ?>
                                    <?php if (strpos($evento['start'], 'T') !== false): ?>
                                    • <?= date('H:i', strtotime($evento['start'])) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
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
            right: 'dayGridMonth,listWeek'
        },
        events: <?= json_encode($eventos) ?>,
        eventClick: function(info) {
            var props = info.event.extendedProps;
            var mensaje = '';
            
            if (props.tipo === 'reunion') {
                mensaje = '<strong>Tipo:</strong> ' + props.subtipo;
                if (props.ubicacion) {
                    mensaje += '<br><strong>Ubicación:</strong> ' + props.ubicacion;
                }
            } else if (props.tipo === 'proyecto' || props.tipo === 'tarea') {
                var estado = props.estado == 3 ? 'Completado' : 'Pendiente';
                mensaje = '<strong>Estado:</strong> ' + estado;
                if (props.proyecto) {
                    mensaje += '<br><strong>Proyecto:</strong> ' + props.proyecto;
                }
            }
            
            Swal.fire({
                title: info.event.title.replace(/^[📅🎯📋]\s*/, ''),
                html: mensaje,
                icon: 'info',
                background: '#161B22',
                color: '#F0F6FC',
                confirmButtonColor: '#55A5C8'
            });
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
    }
    
    .fc-theme-standard td, .fc-theme-standard th {
        border-color: var(--border-color);
    }
    
    .fc-daygrid-day-number, .fc-col-header-cell-cushion {
        color: var(--text-primary);
    }
    
    .fc-list-event-title {
        color: var(--text-primary) !important;
    }
</style>

