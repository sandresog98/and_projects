<?php
/**
 * AND PROJECTS APP - Header para CX (Clientes)
 * Diseño minimalista para visualización de proyectos
 */

require_once __DIR__ . '/../../utils/session.php';
requireClientAuth();

$currentClient = getCurrentClient();
$flashMessage = getFlashMessage();

// Obtener info de la empresa del cliente
require_once __DIR__ . '/../../../ui/modules/empresas/models/EmpresaModel.php';
$empresaModel = new EmpresaModel();
$empresa = $currentClient['empresa_id'] ? $empresaModel->getById($currentClient['empresa_id']) : null;
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <title><?= $pageTitle ?? 'Portal' ?> - <?= APP_NAME ?> | Portal Clientes</title>
    
    <link rel="icon" type="image/x-icon" href="<?= assetUrl('favicons/favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #55A5C8;
            --secondary-green: #9AD082;
            --tertiary-gray: #B1BCBF;
            --dark-blue: #35719E;
            --purple-accent: #6A0DAD;
            
            --bg-primary: #0D1117;
            --bg-secondary: #161B22;
            --bg-tertiary: #21262D;
            --text-primary: #F0F6FC;
            --text-secondary: #8B949E;
            --border-color: #30363D;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        /* Navbar superior */
        .cx-navbar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .cx-logo {
            height: 36px;
            width: auto;
        }
        
        .cx-empresa-logo {
            height: 32px;
            max-width: 120px;
            width: auto;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
        }
        
        .logo-divider {
            width: 1px;
            height: 28px;
            background: var(--border-color);
        }
        
        .cx-nav-links {
            display: flex;
            gap: 8px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .cx-nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .cx-nav-links a:hover,
        .cx-nav-links a.active {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .cx-nav-links a.active {
            color: var(--primary-blue);
        }
        
        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 8px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
        }
        
        .user-dropdown .dropdown-toggle::after {
            display: none;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple-accent), var(--primary-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            color: white;
        }
        
        .dropdown-menu {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
        }
        
        .dropdown-item {
            color: var(--text-secondary);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
        }
        
        .dropdown-item:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .dropdown-item.text-danger:hover {
            background: rgba(220, 53, 69, 0.15);
        }
        
        .dropdown-divider {
            border-color: var(--border-color);
            margin: 8px 0;
        }
        
        /* Contenido principal */
        .cx-main {
            padding: 30px 0;
            min-height: calc(100vh - 70px);
        }
        
        /* Cards */
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 16px 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Badges de estado */
        .badge-status-1 { background: var(--text-secondary); color: white; }
        .badge-status-2 { background: var(--primary-blue); color: white; }
        .badge-status-3 { background: var(--secondary-green); color: #1a1a1a; }
        .badge-status-4 { background: #dc3545; color: white; }
        
        /* Progress bars */
        .progress {
            background: var(--bg-tertiary);
            border-radius: 10px;
            height: 8px;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--primary-blue), var(--secondary-green));
            border-radius: 10px;
        }
        
        /* Tables */
        .table {
            color: var(--text-primary);
        }
        
        .table > :not(caption) > * > * {
            background: transparent;
            border-color: var(--border-color);
            padding: 14px 16px;
        }
        
        .table thead th {
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
        }
        
        /* Animaciones */
        .fade-in-up {
            animation: fadeInUp 0.4s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 12px;
        }
        
        .alert-success {
            background: rgba(154, 208, 130, 0.15);
            color: var(--secondary-green);
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #ff7b7b;
        }
        
        .alert-info {
            background: rgba(85, 165, 200, 0.15);
            color: var(--primary-blue);
        }
        
        /* Welcome banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .welcome-banner h2 {
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .welcome-banner .company-logo {
            max-height: 60px;
            width: auto;
        }
        
        /* Estado labels */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        
        .status-dot.pending { background: var(--text-secondary); }
        .status-dot.in-progress { background: var(--primary-blue); }
        .status-dot.completed { background: var(--secondary-green); }
        .status-dot.blocked { background: #dc3545; }
        
        /* === FIXES PARA TEMA OSCURO === */
        
        /* Textos generales */
        .text-muted {
            color: var(--text-secondary) !important;
        }
        
        .text-dark {
            color: var(--text-primary) !important;
        }
        
        h1, h2, h3, h4, h5, h6,
        .h1, .h2, .h3, .h4, .h5, .h6 {
            color: var(--text-primary);
        }
        
        p, span, div, label, small {
            color: inherit;
        }
        
        strong, b {
            color: var(--text-primary);
        }
        
        a {
            color: var(--primary-blue);
        }
        
        a:hover {
            color: var(--secondary-green);
        }
        
        /* Botones */
        .btn-primary {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--dark-blue);
            border-color: var(--dark-blue);
            color: white;
        }
        
        .btn-outline-primary {
            color: var(--primary-blue);
            border-color: var(--primary-blue);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-blue);
            color: white;
        }
        
        .btn-outline-secondary {
            color: var(--text-secondary);
            border-color: var(--border-color);
        }
        
        .btn-outline-secondary:hover {
            background: var(--bg-tertiary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        .btn-success {
            background: var(--secondary-green);
            border-color: var(--secondary-green);
            color: #1a1a1a;
        }
        
        /* Form controls */
        .form-control,
        .form-select {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        .form-control:focus,
        .form-select:focus {
            background: var(--bg-tertiary);
            border-color: var(--primary-blue);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(85, 165, 200, 0.25);
        }
        
        .form-control::placeholder {
            color: var(--text-secondary);
        }
        
        .form-label {
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-text {
            color: var(--text-secondary);
        }
        
        .form-check-label {
            color: var(--text-primary);
        }
        
        /* List groups */
        .list-group-item {
            background: transparent;
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        .list-group-item-action:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        /* Badges */
        .badge {
            font-weight: 500;
        }
        
        .badge.bg-secondary {
            background: var(--text-secondary) !important;
        }
        
        .badge.bg-primary {
            background: var(--primary-blue) !important;
        }
        
        .badge.bg-success {
            background: var(--secondary-green) !important;
            color: #1a1a1a !important;
        }
        
        .badge.bg-info {
            background: var(--primary-blue) !important;
        }
        
        .badge.bg-warning {
            background: #f0b429 !important;
            color: #1a1a1a !important;
        }
        
        /* Modals */
        .modal-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        .modal-header {
            border-bottom-color: var(--border-color);
        }
        
        .modal-footer {
            border-top-color: var(--border-color);
        }
        
        .btn-close {
            filter: invert(1);
        }
        
        /* Breadcrumbs */
        .breadcrumb {
            background: transparent;
        }
        
        .breadcrumb-item a {
            color: var(--primary-blue);
        }
        
        .breadcrumb-item.active {
            color: var(--text-secondary);
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            color: var(--text-secondary);
        }
        
        /* Navs y Tabs */
        .nav-tabs {
            border-bottom-color: var(--border-color);
        }
        
        .nav-tabs .nav-link {
            color: var(--text-secondary);
            border: none;
        }
        
        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: var(--text-primary);
        }
        
        .nav-tabs .nav-link.active {
            background: transparent;
            border-color: transparent transparent var(--primary-blue);
            color: var(--primary-blue);
        }
        
        /* Pagination */
        .pagination .page-link {
            background: var(--bg-tertiary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        .pagination .page-link:hover {
            background: var(--bg-secondary);
            color: var(--primary-blue);
        }
        
        .pagination .page-item.active .page-link {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
        }
        
        /* Tooltips */
        .tooltip-inner {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        /* Input groups */
        .input-group-text {
            background: var(--bg-tertiary);
            border-color: var(--border-color);
            color: var(--text-secondary);
        }
        
        /* Spinners */
        .spinner-border {
            color: var(--primary-blue);
        }
        
        /* Timeline / Comentarios */
        .timeline strong,
        .timeline p {
            color: var(--text-primary);
        }
        
        /* Card body texts */
        .card-body p {
            color: var(--text-primary);
        }
        
        /* Accordion */
        .accordion-item {
            background: transparent;
            border-color: var(--border-color);
        }
        
        .accordion-button {
            background: transparent;
            color: var(--text-primary);
            box-shadow: none;
        }
        
        .accordion-button:not(.collapsed) {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            box-shadow: none;
        }
        
        .accordion-button::after {
            filter: invert(1);
        }
        
        .accordion-button:focus {
            box-shadow: none;
            border-color: var(--border-color);
        }
        
        .accordion-body {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        .accordion-body strong,
        .accordion-body span {
            color: var(--text-primary);
        }
        
        /* Badge pequeño */
        .badge-sm {
            font-size: 10px;
            padding: 3px 6px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="cx-navbar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-4">
                    <div class="d-flex align-items-center gap-3">
                        <a href="<?= cxUrl('index.php') ?>">
                            <img src="<?= assetUrl('img/logo-horizontal-white.png') ?>" alt="<?= APP_NAME ?>" class="cx-logo">
                        </a>
                        <?php if ($empresa && $empresa['logo']): ?>
                        <span class="logo-divider"></span>
                        <img src="<?= UPLOADS_URL . '/' . $empresa['logo'] ?>" alt="<?= htmlspecialchars($empresa['nombre']) ?>" class="cx-empresa-logo">
                        <?php endif; ?>
                    </div>
                    
                    <ul class="cx-nav-links d-none d-md-flex">
                        <li>
                            <a href="<?= cxUrl('index.php') ?>" class="<?= empty($currentModule) ? 'active' : '' ?>">
                                <i class="bi bi-house"></i> Inicio
                            </a>
                        </li>
                        <li>
                            <a href="<?= cxModuleUrl('proyectos') ?>" class="<?= $currentModule === 'proyectos' ? 'active' : '' ?>">
                                <i class="bi bi-kanban"></i> Proyectos
                            </a>
                        </li>
                        <li>
                            <a href="<?= cxModuleUrl('calendario') ?>" class="<?= $currentModule === 'calendario' ? 'active' : '' ?>">
                                <i class="bi bi-calendar3"></i> Calendario
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="user-dropdown dropdown">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?= strtoupper(substr($currentClient['nombre'], 0, 1)) ?>
                        </div>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($currentClient['nombre']) ?></span>
                        <i class="bi bi-chevron-down ms-1"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2">
                            <small class="text-muted">Conectado como</small>
                            <div class="fw-bold text-truncate" style="max-width: 200px;">
                                <?= htmlspecialchars($currentClient['email']) ?>
                            </div>
                            <?php if ($empresa): ?>
                            <small class="text-primary"><?= htmlspecialchars($empresa['nombre']) ?></small>
                            <?php endif; ?>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= cxModuleUrl('perfil') ?>">
                                <i class="bi bi-person me-2"></i>Mi Perfil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= cxUrl('logout.php') ?>">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="cx-main">
        <div class="container">
            <?php if ($flashMessage): ?>
            <div class="alert alert-<?= $flashMessage['type'] === 'success' ? 'success' : ($flashMessage['type'] === 'error' ? 'danger' : 'info') ?> alert-dismissible fade show fade-in-up" role="alert">
                <i class="bi bi-<?= $flashMessage['type'] === 'success' ? 'check-circle' : ($flashMessage['type'] === 'error' ? 'exclamation-circle' : 'info-circle') ?> me-2"></i>
                <?= $flashMessage['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

