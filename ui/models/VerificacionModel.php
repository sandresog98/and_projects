<?php
/**
 * AND PROJECTS APP - Modelo de Verificación de Códigos
 */

require_once __DIR__ . '/../../config/database.php';

class VerificacionModel {
    private PDO $db;
    private string $table = 'proyectos_verificacion_codigos';
    private int $expiracionMinutos = 15;
    private int $intentosMaximos = 5;
    private int $cooldownSegundos = 60;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crear código de verificación
     */
    public function crearCodigo(string $email, string $tipo, ?string $datosTemporales = null): string {
        // Invalidar códigos anteriores del mismo tipo
        $this->invalidarCodigosAnteriores($email, $tipo);
        
        // Generar código de 6 dígitos
        $codigo = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Calcular fecha de expiración
        $expiracion = date('Y-m-d H:i:s', strtotime("+{$this->expiracionMinutos} minutes"));
        
        $sql = "INSERT INTO {$this->table} (email, codigo, tipo, datos_temporales, fecha_expiracion)
                VALUES (:email, :codigo, :tipo, :datos_temporales, :fecha_expiracion)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'codigo' => $codigo,
            'tipo' => $tipo,
            'datos_temporales' => $datosTemporales,
            'fecha_expiracion' => $expiracion
        ]);
        
        return $codigo;
    }
    
    /**
     * Verificar código
     */
    public function verificarCodigo(string $email, string $codigo, string $tipo): array {
        $sql = "SELECT * FROM {$this->table} 
                WHERE email = :email AND tipo = :tipo AND usado = 0
                ORDER BY fecha_creacion DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email, 'tipo' => $tipo]);
        $registro = $stmt->fetch();
        
        if (!$registro) {
            return ['valido' => false, 'mensaje' => 'No hay código de verificación pendiente'];
        }
        
        // Verificar expiración
        if (strtotime($registro['fecha_expiracion']) < time()) {
            return ['valido' => false, 'mensaje' => 'El código ha expirado'];
        }
        
        // Verificar intentos
        if ($registro['intentos'] >= $this->intentosMaximos) {
            return ['valido' => false, 'mensaje' => 'Demasiados intentos fallidos. Solicita un nuevo código'];
        }
        
        // Verificar código
        if ($registro['codigo'] !== $codigo) {
            $this->incrementarIntentos($registro['id']);
            $intentosRestantes = $this->intentosMaximos - $registro['intentos'] - 1;
            return ['valido' => false, 'mensaje' => "Código incorrecto. Te quedan {$intentosRestantes} intentos"];
        }
        
        // Marcar como usado
        $this->marcarUsado($registro['id']);
        
        return [
            'valido' => true,
            'mensaje' => 'Código verificado correctamente',
            'datos' => $registro['datos_temporales'] ? json_decode($registro['datos_temporales'], true) : null
        ];
    }
    
    /**
     * Verificar si puede reenviar código
     */
    public function puedeReenviar(string $email, string $tipo): array {
        $sql = "SELECT fecha_creacion FROM {$this->table} 
                WHERE email = :email AND tipo = :tipo
                ORDER BY fecha_creacion DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email, 'tipo' => $tipo]);
        $registro = $stmt->fetch();
        
        if (!$registro) {
            return ['puede' => true, 'segundos_restantes' => 0];
        }
        
        $tiempoTranscurrido = time() - strtotime($registro['fecha_creacion']);
        $segundosRestantes = $this->cooldownSegundos - $tiempoTranscurrido;
        
        if ($segundosRestantes > 0) {
            return ['puede' => false, 'segundos_restantes' => $segundosRestantes];
        }
        
        return ['puede' => true, 'segundos_restantes' => 0];
    }
    
    /**
     * Obtener último código válido
     */
    public function getLatestValidCode(string $email, string $tipo): ?array {
        $sql = "SELECT * FROM {$this->table} 
                WHERE email = :email AND tipo = :tipo AND usado = 0
                AND fecha_expiracion > NOW()
                ORDER BY fecha_creacion DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email, 'tipo' => $tipo]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Invalidar códigos anteriores
     */
    private function invalidarCodigosAnteriores(string $email, string $tipo): void {
        $sql = "UPDATE {$this->table} SET usado = 1 
                WHERE email = :email AND tipo = :tipo AND usado = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email, 'tipo' => $tipo]);
    }
    
    /**
     * Incrementar intentos fallidos
     */
    private function incrementarIntentos(int $id): void {
        $sql = "UPDATE {$this->table} SET intentos = intentos + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
    }
    
    /**
     * Marcar código como usado
     */
    private function marcarUsado(int $id): void {
        $sql = "UPDATE {$this->table} SET usado = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
    }
    
    /**
     * Limpiar códigos expirados (para cron)
     */
    public function limpiarExpirados(): int {
        $sql = "DELETE FROM {$this->table} WHERE fecha_expiracion < NOW()";
        $stmt = $this->db->query($sql);
        return $stmt->rowCount();
    }
}

