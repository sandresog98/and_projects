<?php
/**
 * AND PROJECTS APP - Header para CX (Clientes)
 * Diseño minimalista blanco/negro con sparkles
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
    
    <!-- tsParticles -->
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2.12.0/tsparticles.bundle.min.js"></script>
    
    <style>
        :root {
            /* Paleta monocromática */
            --pure-white: #FFFFFF;
            --white-90: #E5E5E5;
            --white-70: #B3B3B3;
            --white-50: #808080;
            --white-30: #4D4D4D;
            --white-20: #333333;
            --white-10: #1A1A1A;
            --pure-black: #000000;
            
            /* Colores de acento - visibles */
            --accent-success: #4ADE80;
            --accent-warning: #FBBF24;
            --accent-danger: #F87171;
            --accent-info: #60A5FA;
            
            /* Compatibilidad con variables antiguas */
            --primary-blue: #60A5FA;
            --secondary-green: #4ADE80;
            
            /* Tema principal */
            --bg-primary: #000000;
            --bg-secondary: #0A0A0A;
            --bg-tertiary: #141414;
            --bg-hover: #1F1F1F;
            --bg-card: rgba(20, 20, 20, 0.8);
            
            --text-primary: #FFFFFF;
            --text-secondary: #C0C0C0;
            --text-muted: #8A8A8A;
            
            --border-color: #262626;
            --border-hover: #404040;
            --border-light: rgba(255, 255, 255, 0.1);
            
            /* Glowing effect colors - Blanco, Gris, Azul oscuro, Rojo oscuro */
            --glow-color-1: #FFFFFF;
            --glow-color-2: #6B7280;
            --glow-color-3: #1E3A5F;
            --glow-color-4: #7F1D1D;
        }
        
        /* Glow Animation */
        @property --glow-angle {
            syntax: '<angle>';
            initial-value: 0deg;
            inherits: false;
        }
        
        @keyframes glow-rotate {
            0% { --glow-angle: 0deg; }
            100% { --glow-angle: 360deg; }
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
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
        
        /* Navbar superior */
        .cx-navbar {
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            position: relative;
        }
        
        .cx-navbar::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 0;
            padding: 1px;
            background: conic-gradient(
                from var(--glow-angle, 0deg),
                var(--glow-color-1),
                var(--glow-color-2),
                var(--glow-color-3),
                var(--glow-color-4),
                var(--glow-color-1)
            );
            -webkit-mask: 
                linear-gradient(#fff 0 0) content-box, 
                linear-gradient(#fff 0 0);
            mask: 
                linear-gradient(#fff 0 0) content-box, 
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.3s ease;
            animation: glow-rotate 6s linear infinite;
            pointer-events: none;
        }
        
        .cx-navbar:hover::before {
            opacity: 0.6;
        }
        
        .cx-logo {
            height: 32px;
            width: auto;
            filter: brightness(0) invert(1);
            transition: transform 0.3s ease;
        }
        
        .cx-logo:hover {
            transform: scale(1.02);
        }
        
        .cx-empresa-logo {
            height: 28px;
            max-width: 100px;
            width: auto;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.05);
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logo-divider {
            width: 1px;
            height: 24px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .cx-nav-links {
            display: flex;
            gap: 4px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .cx-nav-links a {
            color: #9CA3AF;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            position: relative;
        }
        
        .cx-nav-links a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: #fff;
            transition: width 0.3s ease;
        }
        
        .cx-nav-links a:hover {
            color: #D1D5DB;
        }
        
        .cx-nav-links a.active {
            color: #fff;
            position: relative;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }
        
        .cx-nav-links a.active::before {
            width: 30%;
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
        
        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .user-dropdown .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .user-dropdown .dropdown-toggle::after {
            display: none;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #333, #555);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .dropdown-menu {
            background: rgba(20, 20, 20, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1), 0 0 30px rgba(30, 58, 95, 0.2);
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        
        .dropdown-item {
            color: #B0B0B0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #E5E5E5;
            color: #fff;
        }
        
        .dropdown-item.text-danger:hover {
            background: rgba(248, 113, 113, 0.15);
            color: #f87171;
        }
        
        .dropdown-divider {
            border-color: rgba(255, 255, 255, 0.1);
            margin: 8px 0;
        }
        
        /* ============================================
           GLOWING EFFECTS PARA CX
           ============================================ */
        
        /* Glow en user dropdown */
        .user-dropdown .dropdown-toggle {
            transition: box-shadow 0.3s ease;
        }
        
        .user-dropdown .dropdown-toggle:hover {
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.3);
        }
        
        /* Glow en company logo */
        .company-logo {
            position: relative;
            border-radius: 12px;
        }
        
        .company-logo::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: inherit;
            background: conic-gradient(
                from var(--glow-angle, 0deg),
                rgba(255,255,255,0.5),
                rgba(107,114,128,0.3),
                rgba(30,58,95,0.4),
                rgba(127,29,29,0.3),
                rgba(255,255,255,0.5)
            );
            filter: blur(8px);
            opacity: 0.6;
            animation: glow-rotate 5s linear infinite;
            z-index: -1;
            pointer-events: none;
        }
        
        /* Glow en status dots */
        .status-dot {
            position: relative;
        }
        
        .status-dot::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: inherit;
            filter: blur(6px);
            opacity: 0.6;
            animation: pulse-glow 2s ease-in-out infinite;
            z-index: -1;
        }
        
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.2); }
        }
        
        /* Glow en SVG progress circles */
        .card svg circle:last-child {
            filter: drop-shadow(0 0 8px currentColor);
        }
        
        /* Glow en botones outline */
        .btn-outline-primary,
        .btn-outline-secondary {
            transition: box-shadow 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.4);
        }
        
        .btn-outline-secondary:hover {
            box-shadow: 0 0 15px rgba(107, 114, 128, 0.4);
        }
        
        /* Glow en textarea de comentarios */
        textarea.form-control:focus {
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2), 
                        0 0 0 4px rgba(30, 58, 95, 0.3),
                        0 0 20px rgba(127, 29, 29, 0.15);
        }
        
        /* Glow en badges de tipo reunión */
        .badge.bg-info {
            box-shadow: 0 0 10px rgba(96, 165, 250, 0.4);
        }
        
        .badge.bg-primary {
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }
        
        .badge.bg-warning {
            box-shadow: 0 0 10px rgba(251, 191, 36, 0.4);
        }
        
        /* Glow en iconos de subtareas */
        .bi-check-circle-fill.text-success,
        .bi-play-circle.text-primary {
            filter: drop-shadow(0 0 6px currentColor);
        }
        
        /* Glow en display values (porcentajes grandes) */
        .display-5,
        .display-6 {
            text-shadow: 0 0 20px currentColor;
        }
        
        /* Glow en el formulario de comentarios card */
        .card:has(textarea) {
            position: relative;
        }
        
        .card:has(textarea)::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: inherit;
            background: conic-gradient(
                from var(--glow-angle, 0deg),
                rgba(255,255,255,0.2),
                rgba(107,114,128,0.15),
                rgba(30,58,95,0.2),
                rgba(127,29,29,0.15),
                rgba(255,255,255,0.2)
            );
            filter: blur(10px);
            opacity: 0;
            transition: opacity 0.4s ease;
            animation: glow-rotate 6s linear infinite;
            z-index: -1;
            pointer-events: none;
        }
        
        .card:has(textarea:focus)::after {
            opacity: 0.8;
        }
        
        /* Glow en user avatar comments */
        .timeline .user-avatar,
        .comment-item .user-avatar {
            position: relative;
        }
        
        .timeline .user-avatar::before,
        .comment-item .user-avatar::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 50%;
            background: conic-gradient(
                from var(--glow-angle, 0deg),
                rgba(255,255,255,0.4),
                rgba(107,114,128,0.3),
                rgba(30,58,95,0.4),
                rgba(127,29,29,0.3),
                rgba(255,255,255,0.4)
            );
            opacity: 0.5;
            animation: glow-rotate 4s linear infinite;
            z-index: -1;
            pointer-events: none;
        }
        
        /* Glow en accordion cuando está abierto */
        .accordion-collapse.show {
            position: relative;
        }
        
        .accordion-item:has(.accordion-collapse.show) {
            position: relative;
        }
        
        .accordion-item:has(.accordion-collapse.show)::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: inherit;
            background: conic-gradient(
                from var(--glow-angle, 0deg),
                rgba(255,255,255,0.3),
                rgba(107,114,128,0.2),
                rgba(30,58,95,0.3),
                rgba(127,29,29,0.2),
                rgba(255,255,255,0.3)
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
        
        /* Contenido principal */
        .cx-main {
            padding: 30px 0;
            min-height: calc(100vh - 70px);
            position: relative;
            z-index: 1;
        }
        
        /* Cards */
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: conic-gradient(
                from var(--glow-angle, 0deg),
                var(--glow-color-1),
                var(--glow-color-2),
                var(--glow-color-3),
                var(--glow-color-4),
                var(--glow-color-1)
            );
            -webkit-mask: 
                linear-gradient(#fff 0 0) content-box, 
                linear-gradient(#fff 0 0);
            mask: 
                linear-gradient(#fff 0 0) content-box, 
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.4s ease;
            animation: glow-rotate 4s linear infinite;
            pointer-events: none;
            padding: 2px;
            margin: -2px;
        }
        
        .card:hover::before {
            opacity: 1;
        }
        
        .card:hover {
            border-color: var(--border-hover);
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.05);
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 16px 20px;
            color: var(--text-primary);
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Badges de estado */
        .badge {
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
        }
        
        .badge-status-1 { background: var(--white-30); color: var(--pure-white); }
        .badge-status-2 { background: var(--accent-info); color: var(--pure-black); }
        .badge-status-3 { background: var(--accent-success); color: var(--pure-black); }
        .badge-status-4 { background: var(--accent-danger); color: var(--pure-white); }
        
        /* Progress bars */
        .progress {
            background: var(--bg-tertiary);
            border-radius: 10px;
            height: 6px;
            position: relative;
            transition: box-shadow 0.3s ease;
        }
        
        .progress:hover {
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }
        
        .progress-bar {
            background: linear-gradient(90deg, var(--white-50), var(--pure-white));
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
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
        }
        
        .table tbody td {
            color: var(--text-secondary);
        }
        
        .table-hover > tbody > tr:hover > * {
            background: var(--bg-hover);
            color: var(--text-primary);
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
            backdrop-filter: blur(10px);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
        }
        
        .alert-success {
            background: rgba(74, 222, 128, 0.15);
            color: var(--accent-success);
            border: 1px solid rgba(74, 222, 128, 0.3);
        }
        
        .alert-danger {
            background: rgba(248, 113, 113, 0.15);
            color: var(--accent-danger);
            border: 1px solid rgba(248, 113, 113, 0.3);
        }
        
        .alert-info {
            background: rgba(96, 165, 250, 0.15);
            color: var(--accent-info);
            border: 1px solid rgba(96, 165, 250, 0.3);
        }
        
        /* Welcome banner */
        .welcome-banner {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .welcome-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: conic-gradient(
                from var(--glow-angle, 0deg),
                var(--glow-color-1),
                var(--glow-color-2),
                var(--glow-color-3),
                var(--glow-color-4),
                var(--glow-color-1)
            );
            -webkit-mask: 
                linear-gradient(#fff 0 0) content-box, 
                linear-gradient(#fff 0 0);
            mask: 
                linear-gradient(#fff 0 0) content-box, 
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.4s ease;
            animation: glow-rotate 4s linear infinite;
            pointer-events: none;
            padding: 2px;
            margin: -2px;
        }
        
        .welcome-banner:hover::before {
            opacity: 1;
        }
        
        .welcome-banner:hover {
            border-color: var(--border-hover);
        }
        
        .welcome-banner h2 {
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        
        .welcome-banner .company-logo {
            max-height: 60px;
            width: auto;
        }
        
        /* Textos generales */
        .text-muted {
            color: var(--text-muted) !important;
        }
        
        .text-secondary {
            color: var(--text-secondary) !important;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary);
        }
        
        p {
            color: var(--text-secondary);
        }
        
        strong, b {
            color: var(--text-primary);
        }
        
        small {
            color: var(--text-muted);
        }
        
        a {
            color: var(--text-secondary);
            transition: color 0.2s;
        }
        
        a:hover {
            color: var(--text-primary);
        }
        
        /* Botones */
        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--pure-white);
            border: none;
            color: var(--pure-black);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: var(--white-90);
            color: var(--pure-black);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 255, 255, 0.3), 0 0 30px rgba(30, 58, 95, 0.2);
        }
        
        .btn-outline-primary {
            color: var(--pure-white);
            border: 2px solid var(--pure-white);
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background: var(--pure-white);
            color: var(--pure-black);
        }
        
        .btn-outline-secondary {
            color: var(--text-secondary);
            border: 2px solid var(--border-hover);
            background: transparent;
        }
        
        .btn-outline-secondary:hover {
            background: var(--bg-hover);
            border-color: var(--white-50);
            color: var(--text-primary);
        }
        
        .btn-success {
            background: var(--accent-success);
            border: none;
            color: var(--pure-black);
        }
        
        /* Form controls */
        .form-control,
        .form-select {
            background: var(--bg-tertiary);
            border: 2px solid var(--border-color);
            position: relative;
            color: var(--text-primary);
            border-radius: 10px;
            padding: 12px 16px;
        }
        
        .form-control:focus,
        .form-select:focus {
            background: var(--bg-tertiary);
            border-color: var(--pure-white);
            color: var(--text-primary);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
        }
        
        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-text {
            color: var(--text-muted);
        }
        
        /* List groups */
        .list-group-item {
            background: transparent;
            border-color: var(--border-color);
            color: var(--text-primary);
            transition: box-shadow 0.3s ease;
        }
        
        .list-group-item:hover {
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.15);
        }
        
        .list-group-item-action:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }
        
        /* Modals */
        .modal-content {
            background: var(--bg-secondary);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.15), 0 0 60px rgba(30, 58, 95, 0.2);
            color: var(--text-primary);
            border-radius: 16px;
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
        
        /* Accordion */
        .accordion-item {
            background: transparent;
            border-color: var(--border-color);
            transition: box-shadow 0.3s ease;
        }
        
        .accordion-item:hover {
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
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
        .accordion-body span,
        .accordion-body p {
            color: var(--text-primary);
        }
        
        /* Timeline / Comentarios */
        .timeline strong,
        .timeline p {
            color: var(--text-primary);
        }
        
        /* Card body texts */
        .card-body p {
            color: var(--text-secondary);
        }
        
        /* Scrollbar personalizado */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--white-30);
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--white-50);
        }
    </style>
</head>
<body>
    <!-- Partículas de fondo -->
    <div id="tsparticles-bg"></div>
    
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
                        <span class="d-none d-md-inline" style="font-size: 14px;"><?= htmlspecialchars($currentClient['nombre']) ?></span>
                        <i class="bi bi-chevron-down ms-1" style="font-size: 12px;"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2">
                            <small class="text-muted">Conectado como</small>
                            <div class="fw-bold text-truncate text-white" style="max-width: 200px; font-size: 13px;">
                                <?= htmlspecialchars($currentClient['email']) ?>
                            </div>
                            <?php if ($empresa): ?>
                            <small class="text-muted"><?= htmlspecialchars($empresa['nombre']) ?></small>
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
