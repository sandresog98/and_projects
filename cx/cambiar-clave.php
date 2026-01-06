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
    <meta name="color-scheme" content="dark">
    <title>Cambiar Contraseña - <?= APP_NAME ?></title>
    
    <link rel="icon" type="image/x-icon" href="<?= assetUrl('favicons/favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6A0DAD;
            --secondary-color: #8A2BE2;
            --accent-color: #DA70D6;
            --text-light: #E0E0E0;
            --text-dark: #FFFFFF;
            --bg-dark: #1A1A2E;
            --bg-card-dark: #16213E;
            --border-dark: #0F3460;
            --input-bg-dark: #0F3460;
        }
        
        * { font-family: 'Poppins', sans-serif; }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0F0F1A 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            padding: 20px;
        }
        
        .change-password-container {
            width: 100%;
            max-width: 450px;
        }
        
        .card {
            background: var(--bg-card-dark);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .logo { text-align: center; margin-bottom: 30px; }
        .logo img { max-width: 180px; }
        
        .icon-lock {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .icon-lock i { font-size: 36px; color: white; }
        
        .form-label { font-weight: 600; color: var(--text-light); font-size: 14px; }
        
        .form-control {
            background: var(--input-bg-dark);
            border: 2px solid var(--border-dark);
            border-radius: 12px;
            padding: 14px 16px;
            color: var(--text-light);
        }
        
        .form-control:focus {
            background: var(--input-bg-dark);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(218, 112, 214, 0.15);
            color: var(--text-light);
        }
        
        .input-group-text {
            background: var(--input-bg-dark);
            border: 2px solid var(--border-dark);
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: var(--text-light);
        }
        
        .input-group .form-control {
            border-radius: 0 12px 12px 0;
            border-left: none;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(106, 13, 173, 0.4);
            color: white;
        }
        
        .alert { border-radius: 12px; border: none; font-size: 14px; }
        .alert-danger { background: rgba(220, 53, 69, 0.2); color: #ff7b7b; }
        .alert-info { background: rgba(85, 165, 200, 0.2); color: #55A5C8; }
        
        .password-toggle {
            position: absolute; right: 15px; top: 50%;
            transform: translateY(-50%); cursor: pointer;
            color: var(--text-light); z-index: 10;
        }
        
        /* Fix textos oscuros */
        h4 {
            color: var(--text-dark);
        }
        
        .text-muted {
            color: var(--text-light) !important;
            opacity: 0.8;
        }
        
        p {
            color: var(--text-light);
        }
        
        a.text-muted:hover {
            color: var(--accent-color) !important;
        }
        
        /* Placeholders */
        .form-control::placeholder {
            color: rgba(224, 224, 224, 0.5);
        }
        
        .form-control::-webkit-input-placeholder {
            color: rgba(224, 224, 224, 0.5);
        }
        
        .form-control::-moz-placeholder {
            color: rgba(224, 224, 224, 0.5);
        }
    </style>
</head>
<body>
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
                <p class="text-muted mb-0">Es tu primer acceso. Por seguridad, debes establecer una nueva contraseña.</p>
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
                    <label class="form-label">Contraseña Actual (Temporal)</label>
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
                    <label class="form-label">Confirmar Nueva Contraseña</label>
                    <div class="input-group position-relative">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave" placeholder="Repite la nueva contraseña" required>
                        <span class="password-toggle" onclick="togglePassword('confirmar_clave')">
                            <i class="bi bi-eye" id="toggleIcon-confirmar_clave"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-submit">
                    <i class="bi bi-check-lg me-2"></i>Guardar Nueva Contraseña
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
    </script>
</body>
</html>

