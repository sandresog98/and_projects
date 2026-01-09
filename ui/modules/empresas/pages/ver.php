<?php
/**
 * AND PROJECTS APP - Ver Empresa
 */

$id = $_GET['id'] ?? 0;
if (!$id) {
    setFlashMessage('error', 'Empresa no especificada');
    header('Location: ' . uiModuleUrl('empresas'));
    exit;
}

require_once __DIR__ . '/../models/EmpresaModel.php';
require_once __DIR__ . '/../../proyectos/models/ProyectoModel.php';
require_once __DIR__ . '/../../../models/UserModel.php';
require_once __DIR__ . '/../../../models/TiempoModel.php';

$model = new EmpresaModel();
$proyectoModel = new ProyectoModel();
$userModel = new UserModel();
$tiempoModel = new TiempoModel();

$empresa = $model->getById($id);
if (!$empresa) {
    setFlashMessage('error', 'Empresa no encontrada');
    header('Location: ' . uiModuleUrl('empresas'));
    exit;
}

// Obtener proyectos de la empresa
$proyectos = $proyectoModel->getAll(['empresa_id' => $id]);

// Obtener usuarios de la empresa
$usuarios = $userModel->getAll(['empresa_id' => $id]);

// Obtener horas de la empresa
$horasEmpresa = $tiempoModel->getHorasEmpresa($id);
$horasPorProyecto = $tiempoModel->getResumenPorProyecto($id);

$pageTitle = $empresa['nombre'];
$pageSubtitle = 'Detalles de la empresa';
?>

<div class="mb-4 fade-in-up">
    <a href="<?= uiModuleUrl('empresas') ?>" class="btn btn-outline-secondary btn-sm mb-3">
        <i class="bi bi-arrow-left me-2"></i>Volver a Empresas
    </a>
    
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <?php if ($empresa['logo']): ?>
            <img src="<?= UPLOADS_URL . '/' . $empresa['logo'] ?>" alt="<?= htmlspecialchars($empresa['nombre']) ?>" 
                 style="width: 60px; height: 60px; object-fit: contain; background: rgba(255,255,255,0.05); border-radius: 12px; padding: 8px;">
            <?php else: ?>
            <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-building" style="font-size: 28px; color: var(--text-muted);"></i>
            </div>
            <?php endif; ?>
            <div>
                <h4 class="mb-1"><?= htmlspecialchars($empresa['nombre']) ?></h4>
                <?php if ($empresa['razon_social']): ?>
                <small class="text-muted"><?= htmlspecialchars($empresa['razon_social']) ?></small>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Botones de acción con iconos -->
        <div class="d-flex gap-2">
            <?php if (hasPermission('empresas', 'editar')): ?>
            <a href="<?= uiModuleUrl('empresas', 'editar', ['id' => $empresa['id']]) ?>" 
               class="btn-icon btn-icon-primary" title="Editar empresa" data-bs-toggle="tooltip">
                <i class="bi bi-pencil"></i>
            </a>
            <?php endif; ?>
            
            <a href="<?= uiModuleUrl('proyectos', 'crear', ['empresa_id' => $empresa['id']]) ?>" 
               class="btn-icon btn-icon-success" title="Nuevo proyecto" data-bs-toggle="tooltip">
                <i class="bi bi-plus-lg"></i>
            </a>
            
            <a href="<?= uiModuleUrl('usuarios', 'crear', ['empresa_id' => $empresa['id']]) ?>" 
               class="btn-icon btn-icon-primary" title="Nuevo usuario" data-bs-toggle="tooltip">
                <i class="bi bi-person-plus"></i>
            </a>
            
            <?php if (hasPermission('empresas', 'eliminar')): ?>
            <button type="button" class="btn-icon btn-icon-danger" title="Desactivar empresa" data-bs-toggle="tooltip"
                    onclick="confirmDelete('<?= uiModuleUrl('empresas', 'eliminar', ['id' => $empresa['id']]) ?>', 'la empresa <?= htmlspecialchars($empresa['nombre']) ?>')">
                <i class="bi bi-trash"></i>
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Información de la empresa -->
    <div class="col-lg-4">
        <div class="card fade-in-up mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información</h6>
            </div>
            <div class="card-body">
                <?php if ($empresa['nit']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">NIT</small>
                    <strong><?= htmlspecialchars($empresa['nit']) ?></strong>
                </div>
                <?php endif; ?>
                
                <?php if ($empresa['email']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Email</small>
                    <a href="mailto:<?= htmlspecialchars($empresa['email']) ?>"><?= htmlspecialchars($empresa['email']) ?></a>
                </div>
                <?php endif; ?>
                
                <?php if ($empresa['telefono']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Teléfono</small>
                    <a href="tel:<?= htmlspecialchars($empresa['telefono']) ?>"><?= htmlspecialchars($empresa['telefono']) ?></a>
                </div>
                <?php endif; ?>
                
                <?php if ($empresa['direccion']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Dirección</small>
                    <span><?= htmlspecialchars($empresa['direccion']) ?></span>
                    <?php if ($empresa['ciudad'] || $empresa['pais']): ?>
                    <br><small class="text-muted"><?= htmlspecialchars(trim($empresa['ciudad'] . ', ' . $empresa['pais'], ', ')) ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($empresa['sitio_web']): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Sitio Web</small>
                    <a href="<?= htmlspecialchars($empresa['sitio_web']) ?>" target="_blank">
                        <?= htmlspecialchars($empresa['sitio_web']) ?>
                        <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($empresa['descripcion']): ?>
                <div class="mb-0">
                    <small class="text-muted d-block">Descripción</small>
                    <span><?= nl2br(htmlspecialchars($empresa['descripcion'])) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="card fade-in-up mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Estadísticas</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="display-6 fw-bold"><?= count($proyectos) ?></div>
                        <small class="text-muted">Proyectos</small>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="display-6 fw-bold"><?= count($usuarios) ?></div>
                        <small class="text-muted">Usuarios</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resumen de Horas -->
        <div class="card fade-in-up">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Horas</h6>
            </div>
            <div class="card-body">
                <?php $porcentajeHoras = TiempoModel::calcularPorcentaje($horasEmpresa['horas_reales'], $horasEmpresa['horas_estimadas']); ?>
                <div class="row text-center mb-3">
                    <div class="col-6">
                        <div class="h4 mb-0" style="color: var(--accent-info);"><?= TiempoModel::formatHoras($horasEmpresa['horas_reales']) ?></div>
                        <small class="text-muted">Registradas</small>
                    </div>
                    <div class="col-6">
                        <div class="h4 mb-0" style="color: var(--accent-warning);"><?= TiempoModel::formatHoras($horasEmpresa['horas_estimadas']) ?></div>
                        <small class="text-muted">Estimadas</small>
                    </div>
                </div>
                
                <?php if ($horasEmpresa['horas_estimadas'] > 0): ?>
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height: 8px;">
                        <div class="progress-bar <?= $porcentajeHoras > 100 ? 'bg-danger' : '' ?>" style="width: <?= min($porcentajeHoras, 100) ?>%"></div>
                    </div>
                    <small class="text-muted"><?= $porcentajeHoras ?>%</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Proyectos -->
    <div class="col-lg-8">
        <div class="card fade-in-up mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-kanban me-2"></i>Proyectos</h6>
                <a href="<?= uiModuleUrl('proyectos', 'crear', ['empresa_id' => $empresa['id']]) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Nuevo
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($proyectos)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-kanban text-muted" style="font-size: 32px;"></i>
                    <p class="text-muted mt-2 mb-0">Sin proyectos</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($proyectos as $proyecto): ?>
                    <a href="<?= uiModuleUrl('proyectos', 'ver', ['id' => $proyecto['id']]) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($proyecto['nombre']) ?></strong>
                            <br><small class="text-muted"><?= $proyecto['fecha_inicio'] ? formatDate($proyecto['fecha_inicio']) : 'Sin fecha' ?></small>
                        </div>
                        <span class="badge badge-status-<?= $proyecto['estado'] ?>"><?= getStatusText($proyecto['estado']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Usuarios -->
        <div class="card fade-in-up">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-people me-2"></i>Usuarios</h6>
                <a href="<?= uiModuleUrl('usuarios', 'crear', ['empresa_id' => $empresa['id']]) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>Nuevo
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($usuarios)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-people text-muted" style="font-size: 32px;"></i>
                    <p class="text-muted mt-2 mb-0">Sin usuarios</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($usuarios as $usuario): ?>
                    <a href="<?= uiModuleUrl('usuarios', 'ver', ['id' => $usuario['id']]) ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="user-avatar" style="width: 36px; height: 36px; font-size: 12px;">
                                <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($usuario['email']) ?></small>
                            </div>
                        </div>
                        <span class="badge bg-<?= $usuario['rol'] === 'admin' ? 'danger' : ($usuario['rol'] === 'cliente' ? 'info' : 'primary') ?>">
                            <?= ucfirst($usuario['rol']) ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

