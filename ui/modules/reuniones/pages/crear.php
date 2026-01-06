<?php
/**
 * AND PROJECTS APP - Crear Reunión
 */

if (!hasPermission('reuniones', 'crear')) {
    setFlashMessage('error', 'No tiene permisos para crear reuniones');
    header('Location: ' . uiModuleUrl('reuniones'));
    exit;
}

$pageTitle = 'Nueva Reunión';
$pageSubtitle = 'Programar una nueva reunión';

require_once __DIR__ . '/../models/ReunionModel.php';
require_once __DIR__ . '/../../empresas/models/EmpresaModel.php';
require_once __DIR__ . '/../../proyectos/models/ProyectoModel.php';

$model = new ReunionModel();
$empresaModel = new EmpresaModel();
$proyectoModel = new ProyectoModel();

$empresas = $empresaModel->getActiveForSelect();

// Obtener TODOS los proyectos para filtrado dinámico en JS
$todosProyectos = $proyectoModel->getAll(['exclude_cancelled' => true]);

$errors = [];
$formData = [
    'titulo' => '',
    'descripcion' => '',
    'fecha' => date('Y-m-d'),
    'hora_inicio' => '09:00',
    'duracion_minutos' => 60,
    'tipo' => 'presencial',
    'ubicacion' => '',
    'enlace_virtual' => '',
    'empresa_id' => $_GET['empresa_id'] ?? '',
    'proyecto_id' => $_GET['proyecto_id'] ?? '',
    'finalidad' => ''
];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_merge($formData, $_POST);
    
    // Validaciones
    if (empty($formData['titulo'])) {
        $errors[] = 'El título es obligatorio';
    }
    
    if (empty($formData['fecha'])) {
        $errors[] = 'La fecha es obligatoria';
    }
    
    if (empty($formData['hora_inicio'])) {
        $errors[] = 'La hora de inicio es obligatoria';
    }
    
    // Si no hay errores, guardar
    if (empty($errors)) {
        try {
            // Combinar ubicación y enlace virtual en un solo campo
            $ubicacionFinal = $formData['ubicacion'] ?: '';
            if ($formData['enlace_virtual']) {
                $ubicacionFinal = $ubicacionFinal ? $ubicacionFinal . ' | ' . $formData['enlace_virtual'] : $formData['enlace_virtual'];
            }
            
            $id = $model->create([
                'titulo' => $formData['titulo'],
                'descripcion' => $formData['descripcion'] ?: null,
                'fecha' => $formData['fecha'],
                'hora_inicio' => $formData['hora_inicio'],
                'duracion_minutos' => (int)$formData['duracion_minutos'],
                'tipo' => $formData['tipo'],
                'ubicacion' => $ubicacionFinal ?: null,
                'empresa_id' => $formData['empresa_id'] ?: null,
                'proyecto_id' => $formData['proyecto_id'] ?: null,
                'finalidad' => $formData['finalidad'] ?: null,
                'creada_por' => getCurrentUserId()
            ]);
            
            setFlashMessage('success', 'Reunión programada correctamente');
            header('Location: ' . uiModuleUrl('reuniones', 'ver', ['id' => $id]));
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'Error al crear la reunión: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-calendar-plus me-2"></i>Nueva Reunión</h6>
                <a href="<?= uiModuleUrl('reuniones') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Volver
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row g-3">
                        <!-- Información básica -->
                        <div class="col-12">
                            <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-2"></i>Información de la Reunión</h6>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Título *</label>
                            <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($formData['titulo']) ?>" required placeholder="Ej: Reunión de kick-off del proyecto">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción breve de la reunión..."><?= htmlspecialchars($formData['descripcion']) ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Finalidad / Objetivos</label>
                            <textarea name="finalidad" class="form-control" rows="2" placeholder="¿Cuál es el objetivo principal de esta reunión?"><?= htmlspecialchars($formData['finalidad']) ?></textarea>
                        </div>
                        
                        <!-- Fecha y hora -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-clock me-2"></i>Fecha y Hora</h6>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Fecha *</label>
                            <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($formData['fecha']) ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Hora de Inicio *</label>
                            <input type="time" name="hora_inicio" class="form-control" value="<?= htmlspecialchars($formData['hora_inicio']) ?>" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Duración</label>
                            <select name="duracion_minutos" class="form-select">
                                <option value="15" <?= $formData['duracion_minutos'] == 15 ? 'selected' : '' ?>>15 minutos</option>
                                <option value="30" <?= $formData['duracion_minutos'] == 30 ? 'selected' : '' ?>>30 minutos</option>
                                <option value="45" <?= $formData['duracion_minutos'] == 45 ? 'selected' : '' ?>>45 minutos</option>
                                <option value="60" <?= $formData['duracion_minutos'] == 60 ? 'selected' : '' ?>>1 hora</option>
                                <option value="90" <?= $formData['duracion_minutos'] == 90 ? 'selected' : '' ?>>1 hora 30 min</option>
                                <option value="120" <?= $formData['duracion_minutos'] == 120 ? 'selected' : '' ?>>2 horas</option>
                                <option value="180" <?= $formData['duracion_minutos'] == 180 ? 'selected' : '' ?>>3 horas</option>
                            </select>
                        </div>
                        
                        <!-- Tipo y ubicación -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-geo-alt me-2"></i>Tipo y Ubicación</h6>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Reunión</label>
                            <select name="tipo" class="form-select" onchange="toggleUbicacion(this.value)">
                                <option value="presencial" <?= $formData['tipo'] == 'presencial' ? 'selected' : '' ?>>🏢 Presencial</option>
                                <option value="virtual" <?= $formData['tipo'] == 'virtual' ? 'selected' : '' ?>>💻 Virtual</option>
                                <option value="hibrida" <?= $formData['tipo'] == 'hibrida' ? 'selected' : '' ?>>🔄 Híbrida</option>
                            </select>
                        </div>
                        
                        <div class="col-md-8" id="ubicacionContainer">
                            <label class="form-label">Ubicación</label>
                            <input type="text" name="ubicacion" class="form-control" value="<?= htmlspecialchars($formData['ubicacion']) ?>" placeholder="Dirección o sala de reuniones">
                        </div>
                        
                        <div class="col-md-8" id="enlaceContainer" style="display: none;">
                            <label class="form-label">Enlace de la reunión virtual</label>
                            <input type="url" name="enlace_virtual" class="form-control" value="<?= htmlspecialchars($formData['enlace_virtual']) ?>" placeholder="https://meet.google.com/... o https://zoom.us/...">
                        </div>
                        
                        <!-- Contexto -->
                        <div class="col-12 mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-folder me-2"></i>Contexto</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Empresa (opcional)</label>
                            <select name="empresa_id" id="selectEmpresa" class="form-select" onchange="filtrarProyectosPorEmpresa()">
                                <option value="">Sin empresa específica</option>
                                <?php foreach ($empresas as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $formData['empresa_id'] == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Proyecto (opcional)</label>
                            <select name="proyecto_id" id="selectProyecto" class="form-select">
                                <option value="">Sin proyecto específico</option>
                                <?php foreach ($todosProyectos as $proy): ?>
                                <option value="<?= $proy['id'] ?>" data-empresa="<?= $proy['empresa_id'] ?>" <?= $formData['proyecto_id'] == $proy['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($proy['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small id="sinProyectosMsg" class="text-muted" style="display: none;">No hay proyectos para esta empresa</small>
                        </div>
                        
                        <!-- Botones -->
                        <div class="col-12 mt-4">
                            <hr class="my-3">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= uiModuleUrl('reuniones') ?>" class="btn btn-outline-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-calendar-check me-2"></i>Programar Reunión
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleUbicacion(tipo) {
    const ubicacionContainer = document.getElementById('ubicacionContainer');
    const enlaceContainer = document.getElementById('enlaceContainer');
    
    if (tipo === 'virtual') {
        ubicacionContainer.style.display = 'none';
        enlaceContainer.style.display = 'block';
    } else if (tipo === 'hibrida') {
        ubicacionContainer.style.display = 'block';
        enlaceContainer.style.display = 'block';
    } else {
        ubicacionContainer.style.display = 'block';
        enlaceContainer.style.display = 'none';
    }
}

// Filtrar proyectos cuando cambie la empresa (sin recargar página)
function filtrarProyectosPorEmpresa() {
    const empresaId = document.getElementById('selectEmpresa').value;
    const selectProyecto = document.getElementById('selectProyecto');
    const sinProyectosMsg = document.getElementById('sinProyectosMsg');
    const opciones = selectProyecto.querySelectorAll('option');
    
    // Limpiar selección actual
    selectProyecto.value = '';
    
    let hayProyectosVisibles = false;
    
    opciones.forEach(option => {
        if (option.value === '') {
            // Siempre mostrar la opción vacía
            option.style.display = '';
            return;
        }
        
        const empresaProyecto = option.getAttribute('data-empresa');
        
        if (!empresaId || empresaProyecto === empresaId) {
            option.style.display = '';
            hayProyectosVisibles = true;
        } else {
            option.style.display = 'none';
        }
    });
    
    // Mostrar mensaje si no hay proyectos
    if (empresaId && !hayProyectosVisibles) {
        sinProyectosMsg.style.display = 'block';
    } else {
        sinProyectosMsg.style.display = 'none';
    }
}

// Inicializar según el valor actual
document.addEventListener('DOMContentLoaded', function() {
    toggleUbicacion(document.querySelector('[name="tipo"]').value);
    // Aplicar filtro inicial si hay empresa seleccionada
    filtrarProyectosPorEmpresa();
});
</script>

