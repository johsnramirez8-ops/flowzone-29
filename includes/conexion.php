<?php
// Configuración de conexión a base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'flowzone');
define('DB_USER', 'root');      // ← cambiar en producción
define('DB_PASS', '');          // ← cambiar en producción
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('[FlowZone][conexion] ' . $e->getMessage());
    die("Error de conexión. Intenta más tarde.");
}
?>
