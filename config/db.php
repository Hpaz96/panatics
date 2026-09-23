<?php
// Configuración de conexión a la base de datos
date_default_timezone_set('America/Mexico_City'); // GMT-6 Ciudad de México

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'L@lochivas9');
define('DB_NAME', 'pana');

function conectarBD() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    } catch (mysqli_sql_exception $e) {
        die("Error de conexión: " . $e->getMessage());
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Ruta base del proyecto
define('BASE_URL', 'http://localhost/panatics/');
define('UPLOAD_DIR', dirname(__DIR__) . '/assets/uploads/');
?>