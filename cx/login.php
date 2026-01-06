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
    <meta name="color-scheme" content="dark">
    <title>Acceso Clientes - <?= APP_NAME ?></title>
    
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
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #0F0F1A 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: var(--bg-card-dark);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo img {
            max-width: 180px;
            height: auto;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            font-weight: 700;
            font-size: 24px;
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: var(--text-light);
            opacity: 0.8;
            font-size: 14px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-light);
            font-size: 14px;
        }
        
        .form-control {
            background: var(--input-bg-dark);
            border: 2px solid var(--border-dark);
            border-radius: 12px;
            padding: 14px 16px;
            color: var(--text-light);
            font-size: 15px;
        }
        
        .form-control:focus {
            background: var(--input-bg-dark);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(218, 112, 214, 0.15);
            color: var(--text-light);
        }
        
        .form-control::placeholder {
            color: var(--text-light);
            opacity: 0.5;
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
        
        .input-group:focus-within .input-group-text {
            border-color: var(--accent-color);
        }
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(106, 13, 173, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            font-size: 14px;
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #ff7b7b;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            z-index: 10;
        }
        
        .client-badge {
            display: inline-block;
            background: rgba(154, 208, 130, 0.2);
            color: #9AD082;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .collaborator-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-dark);
        }
        
        .collaborator-link a {
            color: var(--text-light);
            font-size: 13px;
            text-decoration: none;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .collaborator-link a:hover {
            opacity: 1;
            color: var(--accent-color);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <img src="<?= assetUrl('img/logo-horizontal-white.png') ?>" alt="<?= APP_NAME ?>">
            </div>
            
            <div class="login-header">
                <span class="client-badge"><i class="bi bi-building me-1"></i> Portal de Clientes</span>
                <h2>Bienvenido</h2>
                <p>Accede para ver el progreso de tus proyectos</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" 
                               class="form-control" 
                               name="email" 
                               value="<?= htmlspecialchars($formData['email']) ?>"
                               placeholder="tu@empresa.com"
                               required 
                               autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group position-relative">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control" 
                               id="password"
                               name="password" 
                               placeholder="Tu contraseña"
                               required>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                </button>
            </form>
            
            <div class="collaborator-link">
                <a href="<?= APP_BASE_URL ?>/ui/login.php">
                    <i class="bi bi-person-workspace me-1"></i>¿Eres colaborador? Accede aquí
                </a>
            </div>
        </div>
    </div>
    
    <script>
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
    </script>
</body>
</html>

