<?php
/**
 * AND PROJECTS APP - Listado de Proyectos para Clientes (CX)
 */

$pageTitle = 'Mis Proyectos';
$pageSubtitle = 'Visualiza el progreso de tus proyectos';

require_once __DIR__ . '/../../../../ui/modules/proyectos/models/ProyectoModel.php';
require_once __DIR__ . '/../../../../ui/modules/tareas/models/TareaModel.php';

$model = new ProyectoModel();
$tareaModel = new TareaModel();
$empresaId = getCurrentClientEmpresaId();

// Filtros
$estado = $_GET['estado'] ?? '';

$filters = [
    'empresa_id' => $empresaId,
    'exclude_cancelled' => true
];
if ($estado !== '') $filters['estado'] = (int)$estado;

$proyectos = $model->getAll($filters);

// Calcular avance real de cada proyecto basado en tareas completadas
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
}
unset($proyecto); // Liberar referencia

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

<div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
    <div>
        <h4 class="mb-1">Mis Proyectos</h4>
        <p class="text-muted mb-0"><?= count($proyectos) ?> proyectos encontrados</p>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4 fade-in-up">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="module" value="proyectos">
            
            <div class="col-md-4">
                <label class="form-label small">Filtrar por estado</label>
                <select name="estado" class="form-select" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="2" <?= $estado === '2' ? 'selected' : '' ?>>En Progreso</option>
                    <option value="3" <?= $estado === '3' ? 'selected' : '' ?>>Completado</option>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Grid de proyectos -->
<?php if (empty($proyectos)): ?>
<div class="card fade-in-up">
    <div class="card-body text-center py-5">
        <i class="bi bi-folder-x text-muted" style="font-size: 64px;"></i>
        <h5 class="mt-3 text-muted">No hay proyectos disponibles</h5>
        <p class="text-muted">Cuando se te asignen proyectos, aparecerán aquí.</p>
    </div>
</div>
<?php else: ?>
<div class="row g-4">
    <?php foreach ($proyectos as $index => $proyecto): ?>
    <div class="col-md-6 col-lg-4 fade-in-up" style="animation-delay: <?= ($index * 0.1) ?>s">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge badge-status-<?= $proyecto['estado'] ?>">
                        <?= getStatusText($proyecto['estado']) ?>
                    </span>
                    <div style="width: 40px; height: 40px; background: <?= $proyecto['color'] ?? 'var(--primary-blue)' ?>; border-radius: 10px; opacity: 0.8;"></div>
                </div>
                
                <h5 class="mb-2"><?= htmlspecialchars($proyecto['nombre']) ?></h5>
                
                <?php if ($proyecto['descripcion']): ?>
                <p class="text-muted small mb-3"><?= htmlspecialchars(substr($proyecto['descripcion'], 0, 100)) ?>...</p>
                <?php endif; ?>
                
                <!-- Avance -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="text-muted">Avance</small>
                        <strong><?= $proyecto['avance'] ?>%</strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" style="width: <?= $proyecto['avance'] ?>%"></div>
                    </div>
                </div>
                
                <!-- Info -->
                <div class="d-flex flex-wrap gap-2 text-muted small mb-3">
                    <?php if ($proyecto['fecha_inicio']): ?>
                    <span><i class="bi bi-calendar me-1"></i>Inicio: <?= formatDate($proyecto['fecha_inicio']) ?></span>
                    <?php endif; ?>
                    <?php if ($proyecto['fecha_fin_estimada']): ?>
                    <span><i class="bi bi-flag me-1"></i>Entrega: <?= formatDate($proyecto['fecha_fin_estimada']) ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Stats -->
                <div class="d-flex gap-3 text-muted small mb-3">
                    <span><i class="bi bi-list-task me-1"></i><?= $proyecto['total_tareas'] ?? 0 ?> tareas</span>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top" style="border-color: var(--border-color) !important;">
                <a href="<?= cxModuleUrl('proyectos', 'ver', ['id' => $proyecto['id']]) ?>" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-eye me-2"></i>Ver Detalles
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

