<?php
/**
 * AND PROJECTS APP - Listado de Empresas
 */

$pageTitle = 'Empresas';
$pageSubtitle = 'Gestión de empresas y clientes';

require_once __DIR__ . '/../models/EmpresaModel.php';
$model = new EmpresaModel();

// Filtros
$search = $_GET['search'] ?? '';
$estado = $_GET['estado'] ?? '';

$filters = [];
if ($search) $filters['search'] = $search;
if ($estado !== '') $filters['estado'] = (int)$estado;

$empresas = $model->getAll($filters);
?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
    <div>
        <h5 class="mb-0">Listado de Empresas</h5>
        <small class="text-muted"><?= count($empresas) ?> empresas encontradas</small>
    </div>
    <?php if (hasPermission('empresas', 'crear')): ?>
    <a href="<?= uiModuleUrl('empresas', 'crear') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i>Nueva Empresa
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card mb-4 fade-in-up">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="module" value="empresas">
            
            <div class="col-md-6">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-control" placeholder="Nombre, razón social o NIT..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Activos</option>
                    <option value="0" <?= $estado === '0' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Listado -->
<div class="card fade-in-up">
    <div class="card-body p-0">
        <?php if (empty($empresas)): ?>
        <div class="text-center py-5">
            <i class="bi bi-building text-muted" style="font-size: 48px;"></i>
            <p class="text-muted mt-3">No se encontraron empresas</p>
            <?php if (hasPermission('empresas', 'crear')): ?>
            <a href="<?= uiModuleUrl('empresas', 'crear') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Crear Primera Empresa
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Contacto</th>
                        <th>Proyectos</th>
                        <th>Usuarios</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($empresas as $empresa): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <?php if ($empresa['logo']): ?>
                                <img src="<?= UPLOADS_URL . '/' . htmlspecialchars($empresa['logo']) ?>" 
                                     alt="Logo" 
                                     style="width: 40px; height: 40px; object-fit: contain; border-radius: 8px; background: var(--bg-input);">
                                <?php else: ?>
                                <div style="width: 40px; height: 40px; border-radius: 8px; background: <?= $empresa['color_primario'] ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">
                                    <?= strtoupper(substr($empresa['nombre'], 0, 1)) ?>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <strong><?= htmlspecialchars($empresa['nombre']) ?></strong>
                                    <?php if ($empresa['nit']): ?>
                                    <br><small class="text-muted">NIT: <?= htmlspecialchars($empresa['nit']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($empresa['email']): ?>
                            <small><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($empresa['email']) ?></small><br>
                            <?php endif; ?>
                            <?php if ($empresa['telefono']): ?>
                            <small><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($empresa['telefono']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-primary"><?= $empresa['total_proyectos'] ?></span>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= $empresa['total_usuarios'] ?></span>
                        </td>
                        <td>
                            <?php if ($empresa['estado'] == 1): ?>
                            <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="<?= uiModuleUrl('empresas', 'ver', ['id' => $empresa['id']]) ?>" 
                                   class="btn-icon" title="Ver detalles" data-bs-toggle="tooltip">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (hasPermission('empresas', 'editar')): ?>
                                <a href="<?= uiModuleUrl('empresas', 'editar', ['id' => $empresa['id']]) ?>" 
                                   class="btn-icon btn-icon-primary" title="Editar" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <a href="<?= uiModuleUrl('proyectos', 'index', ['empresa_id' => $empresa['id']]) ?>" 
                                   class="btn-icon btn-icon-success" title="Ver proyectos" data-bs-toggle="tooltip">
                                    <i class="bi bi-folder"></i>
                                </a>
                                <?php if (hasPermission('empresas', 'eliminar')): ?>
                                <button type="button" class="btn-icon btn-icon-danger" title="Eliminar" data-bs-toggle="tooltip"
                                        onclick="confirmDelete('<?= uiModuleUrl('empresas', 'eliminar', ['id' => $empresa['id']]) ?>', 'la empresa <?= htmlspecialchars($empresa['nombre']) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

