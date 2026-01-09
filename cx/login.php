<?php
/**
 * AND PROJECTS APP - Login de Clientes (CX)
 */

ob_start();
require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Si ya está logueado, redirigir
if (isClientAuthenticated()) {
    $client = getCurrentClient();
    if ($client['requiere_cambio_clave']) {
        ob_end_clean();
        header('Location: cambiar-clave.php');
        exit;
    }
    ob_end_clean();
    header('Location: index.php');
    exit;
}

$auth = new AuthController();
$error = '';
$formData = ['email' => ''];

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $result = $auth->login($formData['email'], $password);
    
    if ($result['success']) {
        if ($result['requiere_cambio_clave']) {
            ob_end_clean();
            header('Location: cambiar-clave.php');
            exit;
        }
        ob_end_clean();
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Clientes - <?= APP_NAME ?></title>
    
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
    
    <!-- tsParticles -->
    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2.12.0/tsparticles.bundle.min.js"></script>
    
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Partículas */
        #tsparticles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        
        /* Gradiente overlay */
        .gradient-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, transparent 0%, rgba(0,0,0,0.7) 70%, #000 100%);
            z-index: 1;
            pointer-events: none;
        }
        
        /* Container principal */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            padding: 20px;
        }
        
        /* Card de login */
        .login-card {
            background: rgba(10, 10, 10, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 50px 40px;
            animation: cardEnter 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }
        
        @keyframes cardEnter {
            0% {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Logo */
        .login-logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-logo img {
            width: 200px;
            height: auto;
            filter: brightness(0) invert(1);
            animation: logoFloat 3s ease-in-out infinite;
        }
        
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        /* Badge de cliente */
        .client-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            color: #999;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Título */
        .login-title {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .login-title h2 {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }
        
        .login-title p {
            color: #555;
            font-size: 14px;
        }
        
        /* Separador */
        .login-divider {
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
            margin: 25px auto 35px;
        }
        
        /* Form */
        .form-label {
            font-weight: 600;
            color: #999;
            margin-bottom: 10px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 15px;
            color: #fff;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.05);
            color: #fff;
        }
        
        .form-control::placeholder {
            color: #444;
        }
        
        .input-group-text {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: #666;
            padding: 0 16px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: rgba(255, 255, 255, 0.3);
            color: #999;
        }
        
        /* Botón login */
        .btn-login {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 16px;
            font-weight: 600;
            font-size: 14px;
            color: #fff;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .btn-login:hover {
            background: #fff;
            border-color: #fff;
            color: #000;
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 255, 255, 0.2);
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        /* Password toggle */
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #444;
            z-index: 10;
            transition: color 0.3s;
        }
        
        .password-toggle:hover {
            color: #fff;
        }
        
        /* Alert */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 14px;
            background: rgba(248, 113, 113, 0.15);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.3);
        }
        
        /* Link colaboradores */
        .collaborator-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .collaborator-link a {
            color: #444;
            font-size: 12px;
            text-decoration: none;
            transition: color 0.3s;
            letter-spacing: 1px;
        }
        
        .collaborator-link a:hover {
            color: #fff;
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: #333;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 40px 25px;
            }
            
            .login-logo img {
                width: 160px;
            }
            
            .login-title h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Partículas -->
    <div id="tsparticles"></div>
    
    <!-- Gradiente overlay -->
    <div class="gradient-overlay"></div>
    
    <!-- Login Container -->
    <div class="login-container">
        <div class="login-card">
            <!-- Logo -->
            <div class="login-logo">
                <img src="<?= assetUrl('img/logo-horizontal-white.png') ?>" alt="<?= APP_NAME ?>">
            </div>
            
            <!-- Badge -->
            <div class="text-center">
                <span class="client-badge">
                    <i class="bi bi-building me-1"></i> Portal de Clientes
                </span>
            </div>
            
            <!-- Título -->
            <div class="login-title">
                <h2>Bienvenido</h2>
                <p>Accede para ver el progreso de tus proyectos</p>
            </div>
            
            <div class="login-divider"></div>
            
            <!-- Error -->
            <?php if ($error): ?>
            <div class="alert d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <!-- Form -->
            <form method="POST" autocomplete="off">
                <div class="mb-4">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?= htmlspecialchars($formData['email']) ?>"
                               placeholder="tu@empresa.com"
                               required 
                               autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="password-wrapper">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Tu contraseña"
                                   required>
                        </div>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    Ingresar
                </button>
            </form>
            
            <!-- Link colaboradores -->
            <div class="collaborator-link">
                <a href="<?= APP_BASE_URL ?>/ui/login.php">
                    <i class="bi bi-person-workspace me-1"></i>¿Eres colaborador? Accede aquí
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="login-footer">
            © 2025 <?= APP_NAME ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }
        
        // Inicializar tsParticles
        tsParticles.load("tsparticles", {
            fullScreen: { enable: false },
            background: { color: { value: "#000000" } },
            fpsLimit: 120,
            interactivity: {
                events: {
                    onHover: { enable: true, mode: "repulse" },
                    resize: true
                },
                modes: {
                    repulse: { distance: 100, duration: 0.4 }
                }
            },
            particles: {
                color: { value: "#ffffff" },
                move: {
                    direction: "none",
                    enable: true,
                    outModes: { default: "out" },
                    random: true,
                    speed: { min: 0.1, max: 0.5 },
                    straight: false
                },
                number: {
                    density: { enable: true, area: 800 },
                    value: 100
                },
                opacity: {
                    value: { min: 0.1, max: 1 },
                    animation: {
                        enable: true,
                        speed: 1,
                        sync: false,
                        startValue: "random"
                    }
                },
                shape: { type: "circle" },
                size: {
                    value: { min: 0.5, max: 2 },
                    animation: { enable: true, speed: 2, sync: false }
                },
                twinkle: {
                    particles: { enable: true, frequency: 0.05, opacity: 1 }
                }
            },
            detectRetina: true
        });
    </script>
</body>
</html>
