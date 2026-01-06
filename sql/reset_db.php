<?php
/**
 * AND PROJECTS APP - Reset Database Script
 * Script para reiniciar la base de datos
 * 
 * USO: php reset_db.php
 * ADVERTENCIA: Este script eliminará todos los datos existentes
 */

// Verificar que se ejecuta desde CLI
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde la línea de comandos');
}

echo "========================================\n";
echo "AND PROJECTS APP - Reset Database\n";
echo "========================================\n\n";

echo "ADVERTENCIA: Este proceso eliminará TODOS los datos.\n";
echo "¿Desea continuar? (escriba 'SI' para confirmar): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim($line) !== 'SI') {
    echo "Operación cancelada.\n";
    exit(0);
}

echo "\nIniciando reset de base de datos...\n\n";

// Cargar configuración
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    
    // Leer archivo DDL
    $ddlFile = __DIR__ . '/ddl.sql';
    if (!file_exists($ddlFile)) {
        throw new Exception("No se encontró el archivo DDL: $ddlFile");
    }
    
    $sql = file_get_contents($ddlFile);
    
    // Ejecutar cada sentencia
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $total = count($statements);
    $current = 0;
    
    foreach ($statements as $statement) {
        $current++;
        try {
            $db->exec($statement);
            echo "[" . str_pad($current, 3, '0', STR_PAD_LEFT) . "/$total] OK\n";
        } catch (PDOException $e) {
            // Ignorar errores de DROP en tablas que no existen
            if (strpos($e->getMessage(), 'Unknown table') === false) {
                echo "[" . str_pad($current, 3, '0', STR_PAD_LEFT) . "/$total] Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n========================================\n";
    echo "Base de datos reiniciada exitosamente!\n";
    echo "========================================\n\n";
    
    echo "Credenciales por defecto:\n";
    echo "  Email: admin@andprojects.com\n";
    echo "  Password: Admin123!\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

