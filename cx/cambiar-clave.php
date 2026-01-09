<?php
/**
 * AND PROJECTS APP - Cambio de clave obligatorio para clientes (CX)
 */

require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Verificar que hay sesión
if (!isClientAuthenticated()) {
    header('Location: login.php');
    exit;
}

$client = getCurrentClient();

// Si no requiere cambio de clave, redirigir al dashboard
if (!$client['requiere_cambio_clave']) {
    header('Location: index.php');
    exit;
}

$auth = new AuthController();
$error = '';
$success = '';

// Procesar cambio de clave
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claveActual = $_POST['clave_actual'] ?? '';
    $nuevaClave = $_POST['nueva_clave'] ?? '';
    $confirmarClave = $_POST['confirmar_clave'] ?? '';
    
    $result = $auth->cambiarClaveInicial($claveActual, $nuevaClave, $confirmarClave);
    
    if ($result['success']) {
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
    <title>Cambiar Contraseña - <?= APP_NAME ?></title>
    
    <link rel="icon" type="image/x-icon" href="<?= assetUrl('favicons/favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .change-password-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            padding: 20px;
        }
        
        /* Card */
        .card {
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
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo img {
            width: 180px;
            height: auto;
            filter: brightness(0) invert(1);
        }
        
        /* Icon lock */
        .icon-lock {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        
        .icon-lock i {
            font-size: 30px;
            color: #fff;
        }
        
        /* Textos */
        h4 {
            color: #fff;
            font-size: 24px;
            font-weight: 700;
        }
        
        .text-muted {
            color: #666 !important;
        }
        
        p {
            color: #888;
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
        
        /* Botón submit */
        .btn-submit {
            background: #fff;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-weight: 600;
            font-size: 14px;
            color: #000;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0,0,0,0.1), transparent);
            transition: left 0.5s;
        }
        
        .btn-submit:hover {
            background: #e5e5e5;
            color: #000;
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 255, 255, 0.2);
        }
        
        .btn-submit:hover::before {
            left: 100%;
        }
        
        /* Password toggle */
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
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 14px;
        }
        
        .alert-danger {
            background: rgba(248, 113, 113, 0.15);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.3);
        }
        
        .alert-info {
            background: rgba(96, 165, 250, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(96, 165, 250, 0.3);
        }
        
        /* Link cerrar sesión */
        a.text-muted {
            color: #444 !important;
            transition: color 0.3s;
        }
        
        a.text-muted:hover {
            color: #fff !important;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .card {
                padding: 40px 25px;
            }
            
            .logo img {
                width: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- Partículas -->
    <div id="tsparticles"></div>
    
    <!-- Gradiente overlay -->
    <div class="gradient-overlay"></div>
    
    <!-- Container -->
    <div class="change-password-container">
        <div class="card">
            <div class="logo">
                <img src="<?= assetUrl('img/logo-horizontal-white.png') ?>" alt="<?= APP_NAME ?>">
            </div>
            
            <div class="icon-lock">
                <i class="bi bi-shield-lock"></i>
            </div>
            
            <div class="text-center mb-4">
                <h4 class="mb-2">Cambiar Contraseña</h4>
                <p class="mb-0">Es tu primer acceso. Por seguridad, debes establecer una nueva contraseña.</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle me-2"></i>
                La contraseña actual es la contraseña temporal que te proporcionó el administrador.
            </div>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Contraseña Actual</label>
                    <div class="input-group position-relative">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control" id="clave_actual" name="clave_actual" placeholder="Contraseña proporcionada" required>
                        <span class="password-toggle" onclick="togglePassword('clave_actual')">
                            <i class="bi bi-eye" id="toggleIcon-clave_actual"></i>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nueva Contraseña</label>
                    <div class="input-group position-relative">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="nueva_clave" name="nueva_clave" placeholder="Mínimo 6 caracteres" minlength="6" required>
                        <span class="password-toggle" onclick="togglePassword('nueva_clave')">
                            <i class="bi bi-eye" id="toggleIcon-nueva_clave"></i>
                        </span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Confirmar Contraseña</label>
                    <div class="input-group position-relative">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave" placeholder="Repite la nueva contraseña" required>
                        <span class="password-toggle" onclick="togglePassword('confirmar_clave')">
                            <i class="bi bi-eye" id="toggleIcon-confirmar_clave"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-submit">
                    Guardar Contraseña
                </button>
            </form>
            
            <div class="text-center mt-4">
                <a href="logout.php" class="text-muted text-decoration-none">
                    <i class="bi bi-box-arrow-left me-1"></i>Cerrar sesión
                </a>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById('toggleIcon-' + inputId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
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
