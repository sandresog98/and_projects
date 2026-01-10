<?php
/**
 * AND PROJECTS APP - Header para UI (Colaboradores)
 */

require_once __DIR__ . '/../../utils/session.php';
requireUserAuth();

$currentUser = getCurrentUser();
$flashMessage = getFlashMessage();

// Cargar roles y permisos
$rolesFile = __DIR__ . '/../../../roles.json';
$roles = file_exists($rolesFile) ? json_decode(file_get_contents($rolesFile), true) : [];
$userRole = $currentUser['rol'] ?? 'colaborador';
$userPermissions = $roles[$userRole]['modulos'] ?? [];

// Módulo actual para el sidebar
$currentModule = $currentModule ?? '';
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= APP_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= assetUrl('favicons/favicon.ico') ?>">
    
    <!-- Google Fonts - Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- App CSS -->
    <link href="<?= assetUrl('css/app.css') ?>" rel="stylesheet">
    
    <!-- tsParticles -->
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2.12.0/tsparticles.bundle.min.js"></script>
    
    <style>
        /* Partículas de fondo */
        #tsparticles-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        /* Glow en sidebar links activos */
        .sidebar-link.active {
            position: relative;
            overflow: visible;
        }
        
        .sidebar-link.active::after {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            background: conic-gradient(
                from var(--glow-angle, 0deg),
                rgba(255,255,255,0.6),
                rgba(107,114,128,0.4),
                rgba(30,58,95,0.5),
                rgba(127,29,29,0.4),
                rgba(255,255,255,0.6)
            );
            -webkit-mask: 
                linear-gradient(#fff 0 0) content-box, 
                linear-gradient(#fff 0 0);
            mask: 
                linear-gradient(#fff 0 0) content-box, 
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0.8;
            animation: glow-rotate 4s linear infinite;
            pointer-events: none;
        }
        
        @keyframes glow-rotate {
            0% { --glow-angle: 0deg; }
            100% { --glow-angle: 360deg; }
        }
        
        @property --glow-angle {
            syntax: '<angle>';
            initial-value: 0deg;
            inherits: false;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .sidebar-logo {
            max-width: 160px;
            height: auto;
            filter: brightness(0) invert(1);
            transition: transform 0.3s ease;
        }
        
        .sidebar-logo:hover {
            transform: scale(1.02);
        }
        
        .sidebar-nav {
            padding: 15px 12px;
        }
        
        .sidebar-section {
            margin-bottom: 8px;
        }
        
        .sidebar-section-title {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #888;
            padding: 15px 14px 10px;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            color: #9CA3AF;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s ease;
            margin-bottom: 4px;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 3px;
            height: 100%;
            background: #fff;
            transform: scaleY(0);
            transition: transform 0.2s ease;
        }
        
        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #D1D5DB;
        }
        
        .sidebar-link.active {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            font-weight: 500;
        }
        
        .sidebar-link.active::before {
            transform: scaleY(1);
        }
        
        .sidebar-link i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .sidebar-user {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(10, 10, 10, 0.95);
        }
        
        .sidebar-user .user-avatar {
            width: 38px;
            height: 38px;
            font-size: 13px;
            background: linear-gradient(135deg, #333, #555);
        }
        
        /* Main content */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 30px;
            position: relative;
            z-index: 1;
        }
        
        /* Mobile toggle */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            padding: 10px 12px;
            background: rgba(20, 20, 20, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
        }
        
        .sidebar-toggle:hover {
            background: rgba(40, 40, 40, 0.9);
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block;
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                backdrop-filter: blur(5px);
                z-index: 999;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        /* Cursor pointer */
        .cursor-pointer {
            cursor: pointer;
        }
        
        /* Page header styles */
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h4 {
            font-weight: 700;
            color: #fff;
            margin-bottom: 5px;
        }
        
        .page-header p {
            color: #666;
            margin: 0;
        }
        
        /* Modal z-index fix para estar sobre el sidebar */
        .modal-backdrop {
            z-index: 1050 !important;
        }
        .modal {
            z-index: 1055 !important;
        }
    </style>
</head>
<body>
    <!-- Partículas de fondo -->
    <div id="tsparticles-bg"></div>
    
    <!-- Sidebar Toggle (Mobile) -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?= uiUrl('index.php') ?>">
                <img src="<?= assetUrl('img/logo-horizontal-white.png') ?>" alt="<?= APP_NAME ?>" class="sidebar-logo">
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <!-- Dashboard -->
            <div class="sidebar-section">
                <a href="<?= uiUrl('index.php') ?>" class="sidebar-link <?= empty($currentModule) ? 'active' : '' ?>">
                    <i class="bi bi-house"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <!-- Gestión -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">Gestión</div>
                
                <?php if (hasPermission('empresas', 'ver')): ?>
                <a href="<?= uiModuleUrl('empresas') ?>" class="sidebar-link <?= $currentModule === 'empresas' ? 'active' : '' ?>">
                    <i class="bi bi-building"></i>
                    <span>Empresas</span>
                </a>
                <?php endif; ?>
                
                <?php if (hasPermission('usuarios', 'ver')): ?>
                <a href="<?= uiModuleUrl('usuarios') ?>" class="sidebar-link <?= $currentModule === 'usuarios' ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>
                    <span>Usuarios</span>
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Proyectos -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">Proyectos</div>
                
                <?php if (hasPermission('proyectos', 'ver')): ?>
                <a href="<?= uiModuleUrl('proyectos') ?>" class="sidebar-link <?= $currentModule === 'proyectos' ? 'active' : '' ?>">
                    <i class="bi bi-kanban"></i>
                    <span>Proyectos</span>
                </a>
                <?php endif; ?>
                
                <?php if (hasPermission('tareas', 'ver')): ?>
                <a href="<?= uiModuleUrl('tareas') ?>" class="sidebar-link <?= $currentModule === 'tareas' ? 'active' : '' ?>">
                    <i class="bi bi-list-task"></i>
                    <span>Tareas</span>
                </a>
                <?php endif; ?>
                
                <?php if (hasPermission('subtareas', 'ver')): ?>
                <a href="<?= uiModuleUrl('subtareas') ?>" class="sidebar-link <?= $currentModule === 'subtareas' ? 'active' : '' ?>">
                    <i class="bi bi-list-check"></i>
                    <span>Subtareas</span>
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Colaboración -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">Colaboración</div>
                
                <?php if (hasPermission('reuniones', 'ver')): ?>
                <a href="<?= uiModuleUrl('reuniones') ?>" class="sidebar-link <?= $currentModule === 'reuniones' ? 'active' : '' ?>">
                    <i class="bi bi-calendar-event"></i>
                    <span>Reuniones</span>
                </a>
                <?php endif; ?>
            </div>
        </nav>
        
        <!-- User info -->
        <div class="sidebar-user">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center gap-3 text-decoration-none" data-bs-toggle="dropdown">
                    <div class="user-avatar">
                        <?php if (!empty($currentUser['avatar'])): ?>
                        <img src="<?= UPLOADS_URL . '/' . $currentUser['avatar'] ?>" alt="">
                        <?php else: ?>
                        <?= strtoupper(substr($currentUser['nombre'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <strong class="d-block text-truncate text-white" style="font-size: 14px;"><?= htmlspecialchars($currentUser['nombre']) ?></strong>
                        <small class="text-muted text-capitalize" style="font-size: 11px;"><?= htmlspecialchars($currentUser['rol']) ?></small>
                    </div>
                    <i class="bi bi-chevron-up text-muted"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end w-100">
                    <li>
                        <a class="dropdown-item" href="<?= uiModuleUrl('perfil') ?>">
                            <i class="bi bi-person me-2"></i>Mi Perfil
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= uiModuleUrl('perfil') ?>#cambiar-password">
                            <i class="bi bi-key me-2"></i>Cambiar Contraseña
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?= uiUrl('logout.php') ?>">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <?php if ($flashMessage): ?>
        <div class="alert alert-<?= $flashMessage['type'] === 'success' ? 'success' : ($flashMessage['type'] === 'error' ? 'danger' : 'info') ?> alert-dismissible fade show fade-in-up" role="alert">
            <i class="bi bi-<?= $flashMessage['type'] === 'success' ? 'check-circle' : ($flashMessage['type'] === 'error' ? 'exclamation-circle' : 'info-circle') ?> me-2"></i>
            <?= $flashMessage['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
