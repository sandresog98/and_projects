<?php
/**
 * AND PROJECTS APP - Listado de Usuarios
 */

$pageTitle = 'Usuarios';
$pageSubtitle = 'Gestión de usuarios y colaboradores';

require_once __DIR__ . '/../../../models/UserModel.php';
require_once __DIR__ . '/../../empresas/models/EmpresaModel.php';

$model = new UserModel();
$empresaModel = new EmpresaModel();

// Filtros
$search = $_GET['search'] ?? '';
$rol = $_GET['rol'] ?? '';
$empresa_id = $_GET['empresa_id'] ?? '';
$estado = $_GET['estado'] ?? '';

$filters = [];
if ($search) $filters['search'] = $search;
if ($rol) $filters['rol'] = $rol;
if ($empresa_id) $filters['empresa_id'] = (int)$empresa_id;
if ($estado !== '') $filters['estado'] = (int)$estado;

$usuarios = $model->getAll($filters);
$empresas = $empresaModel->getActiveForSelect();
?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
    <div>
        <h5 class="mb-0">Listado de Usuarios</h5>
        <small class="text-muted"><?= count($usuarios) ?> usuarios encontrados</small>
    </div>
    <?php if (hasPermission('usuarios', 'crear')): ?>
    <a href="<?= uiModuleUrl('usuarios', 'crear') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i>Nuevo Usuario
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card mb-4 fade-in-up">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="module" value="usuarios">
            
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-control" placeholder="Nombre o email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Rol</label>
                <select name="rol" class="form-select">
                    <option value="">Todos</option>
                    <option value="admin" <?= $rol === 'admin' ? 'selected' : '' ?>>Administrador</option>
                    <option value="colaborador" <?= $rol === 'colaborador' ? 'selected' : '' ?>>Colaborador</option>
                    <option value="cliente" <?= $rol === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Empresa</label>
                <select name="empresa_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($empresas as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= $empresa_id == $emp['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= $estado === '0' ? 'selected' : '' ?>>Inactivo</option>
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
        <?php if (empty($usuarios)): ?>
        <div class="text-center py-5">
            <i class="bi bi-people text-muted" style="font-size: 48px;"></i>
            <p class="text-muted mt-3">No se encontraron usuarios</p>
            <?php if (hasPermission('usuarios', 'crear')): ?>
            <a href="<?= uiModuleUrl('usuarios', 'crear') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Crear Primer Usuario
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Empresa</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último acceso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="user-avatar" style="width: 40px; height: 40px; font-size: 14px;">
                                    <?php if (!empty($usuario['avatar'])): ?>
                                    <img src="<?= UPLOADS_URL . '/' . htmlspecialchars($usuario['avatar']) ?>" alt="Avatar">
                                    <?php else: ?>
                                    <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($usuario['email']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?= $usuario['empresa_nombre'] ? htmlspecialchars($usuario['empresa_nombre']) : '<span class="text-muted">-</span>' ?>
                        </td>
                        <td>
                            <?php
                            $rolClass = match($usuario['rol']) {
                                'admin' => 'danger',
                                'colaborador' => 'primary',
                                'cliente' => 'info',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $rolClass ?>"><?= ucfirst($usuario['rol']) ?></span>
                        </td>
                        <td>
                            <?php if ($usuario['estado'] == 1): ?>
                            <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($usuario['ultimo_acceso']): ?>
                            <small><?= formatDateTime($usuario['ultimo_acceso']) ?></small>
                            <?php else: ?>
                            <small class="text-muted">Nunca</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="<?= uiModuleUrl('usuarios', 'ver', ['id' => $usuario['id']]) ?>" 
                                   class="btn-icon" title="Ver detalles" data-bs-toggle="tooltip">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (hasPermission('usuarios', 'editar')): ?>
                                <a href="<?= uiModuleUrl('usuarios', 'editar', ['id' => $usuario['id']]) ?>" 
                                   class="btn-icon btn-icon-primary" title="Editar" data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if (hasPermission('usuarios', 'eliminar') && $usuario['id'] != getCurrentUserId()): ?>
                                <button type="button" class="btn-icon btn-icon-danger" title="Desactivar" data-bs-toggle="tooltip"
                                        onclick="confirmDelete('<?= uiModuleUrl('usuarios', 'eliminar', ['id' => $usuario['id']]) ?>', 'el usuario <?= htmlspecialchars($usuario['nombre']) ?>')">
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

