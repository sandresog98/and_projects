<?php
/**
 * AND PROJECTS APP - Helper para envío de emails
 */

require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';
require_once __DIR__ . '/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    
    /**
     * Obtener instancia configurada de PHPMailer
     */
    private static function getMailer(): PHPMailer {
        $mail = new PHPMailer(true);
        
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = defined('MAIL_HOST') ? MAIL_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
        $mail->Password = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = defined('MAIL_PORT') ? MAIL_PORT : 587;
        
        // Remitente
        $mail->setFrom(
            defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'noreply@andprojects.com',
            defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : APP_NAME
        );
        
        // Configuración general
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        
        return $mail;
    }
    
    /**
     * Enviar código de verificación
     */
    public static function sendVerificationCode(string $email, string $codigo, string $nombre): array {
        try {
            $mail = self::getMailer();
            $mail->addAddress($email, $nombre);
            $mail->Subject = 'Código de verificación - ' . APP_NAME;
            
            $mail->Body = self::getTemplate('verification', [
                'nombre' => $nombre,
                'codigo' => $codigo,
                'app_name' => APP_NAME
            ]);
            
            $mail->AltBody = "Hola {$nombre}, tu código de verificación es: {$codigo}. Válido por 15 minutos.";
            
            $mail->send();
            return ['success' => true, 'message' => 'Código enviado'];
            
        } catch (Exception $e) {
            error_log('Error enviando email: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al enviar el email'];
        }
    }
    
    /**
     * Enviar código de recuperación de contraseña
     */
    public static function sendPasswordResetCode(string $email, string $codigo, string $nombre): array {
        try {
            $mail = self::getMailer();
            $mail->addAddress($email, $nombre);
            $mail->Subject = 'Recuperar contraseña - ' . APP_NAME;
            
            $mail->Body = self::getTemplate('password_reset', [
                'nombre' => $nombre,
                'codigo' => $codigo,
                'app_name' => APP_NAME
            ]);
            
            $mail->AltBody = "Hola {$nombre}, tu código para recuperar tu contraseña es: {$codigo}. Válido por 15 minutos.";
            
            $mail->send();
            return ['success' => true, 'message' => 'Código enviado'];
            
        } catch (Exception $e) {
            error_log('Error enviando email: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al enviar el email'];
        }
    }
    
    /**
     * Enviar email de bienvenida
     */
    public static function sendWelcome(string $email, string $nombre): array {
        try {
            $mail = self::getMailer();
            $mail->addAddress($email, $nombre);
            $mail->Subject = '¡Bienvenido a ' . APP_NAME . '!';
            
            $mail->Body = self::getTemplate('welcome', [
                'nombre' => $nombre,
                'app_name' => APP_NAME,
                'app_url' => APP_URL ?? ''
            ]);
            
            $mail->AltBody = "¡Hola {$nombre}! Te damos la bienvenida a " . APP_NAME . ".";
            
            $mail->send();
            return ['success' => true, 'message' => 'Email de bienvenida enviado'];
            
        } catch (Exception $e) {
            error_log('Error enviando email: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al enviar el email'];
        }
    }
    
    /**
     * Obtener plantilla HTML de email
     */
    private static function getTemplate(string $template, array $data): string {
        $templates = [
            'verification' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body { font-family: Arial, sans-serif; background: #1A1A2E; margin: 0; padding: 20px; }
                        .container { max-width: 500px; margin: 0 auto; background: #16213E; border-radius: 16px; padding: 40px; }
                        .logo { text-align: center; margin-bottom: 30px; }
                        .logo h1 { color: #55A5C8; margin: 0; }
                        .content { color: #E0E0E0; }
                        .code { background: #0F3460; padding: 20px; border-radius: 12px; text-align: center; margin: 25px 0; }
                        .code span { font-size: 32px; font-weight: bold; color: #9AD082; letter-spacing: 8px; }
                        .footer { text-align: center; color: #8B949E; font-size: 12px; margin-top: 30px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="logo">
                            <h1>{{app_name}}</h1>
                        </div>
                        <div class="content">
                            <p>Hola <strong>{{nombre}}</strong>,</p>
                            <p>Tu código de verificación es:</p>
                            <div class="code">
                                <span>{{codigo}}</span>
                            </div>
                            <p>Este código es válido por 15 minutos. Si no solicitaste este código, ignora este mensaje.</p>
                        </div>
                        <div class="footer">
                            <p>© ' . date('Y') . ' {{app_name}}. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            
            'password_reset' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body { font-family: Arial, sans-serif; background: #1A1A2E; margin: 0; padding: 20px; }
                        .container { max-width: 500px; margin: 0 auto; background: #16213E; border-radius: 16px; padding: 40px; }
                        .logo { text-align: center; margin-bottom: 30px; }
                        .logo h1 { color: #55A5C8; margin: 0; }
                        .content { color: #E0E0E0; }
                        .code { background: #0F3460; padding: 20px; border-radius: 12px; text-align: center; margin: 25px 0; }
                        .code span { font-size: 32px; font-weight: bold; color: #DA70D6; letter-spacing: 8px; }
                        .footer { text-align: center; color: #8B949E; font-size: 12px; margin-top: 30px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="logo">
                            <h1>{{app_name}}</h1>
                        </div>
                        <div class="content">
                            <p>Hola <strong>{{nombre}}</strong>,</p>
                            <p>Recibimos una solicitud para restablecer tu contraseña. Usa el siguiente código:</p>
                            <div class="code">
                                <span>{{codigo}}</span>
                            </div>
                            <p>Este código es válido por 15 minutos. Si no solicitaste este cambio, ignora este mensaje.</p>
                        </div>
                        <div class="footer">
                            <p>© ' . date('Y') . ' {{app_name}}. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
            ',
            
            'welcome' => '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body { font-family: Arial, sans-serif; background: #1A1A2E; margin: 0; padding: 20px; }
                        .container { max-width: 500px; margin: 0 auto; background: #16213E; border-radius: 16px; padding: 40px; }
                        .logo { text-align: center; margin-bottom: 30px; }
                        .logo h1 { color: #55A5C8; margin: 0; }
                        .content { color: #E0E0E0; text-align: center; }
                        .welcome { font-size: 48px; margin: 20px 0; }
                        .footer { text-align: center; color: #8B949E; font-size: 12px; margin-top: 30px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="logo">
                            <h1>{{app_name}}</h1>
                        </div>
                        <div class="content">
                            <div class="welcome">🎉</div>
                            <h2>¡Bienvenido, {{nombre}}!</h2>
                            <p>Tu cuenta ha sido creada exitosamente. Ahora puedes acceder a todas las funcionalidades de la plataforma.</p>
                            <p>¡Estamos emocionados de tenerte con nosotros!</p>
                        </div>
                        <div class="footer">
                            <p>© ' . date('Y') . ' {{app_name}}. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
            '
        ];
        
        $html = $templates[$template] ?? '';
        
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', htmlspecialchars($value), $html);
        }
        
        return $html;
    }
}

