<?php
// Instalador de la base de datos PANATICS
$host = 'localhost';
$user = 'root';
$pass = '';

$msj = '';
$guardar = isset($_POST['guardar']) ? true : false;

if ($guardar) {
    $host = trim($_POST['host']);
    $user = trim($_POST['user']);
    $pass = $_POST['pass'];

    // Conexión sin seleccionar BD
    try {
        $conn = new mysqli($host, $user, $pass);
        $conexionOk = true;
    } catch (mysqli_sql_exception $e) {
        $conexionOk = false;
        $msj = '<div class="error">Error de conexión: ' . $e->getMessage() . '</div>';
    }

    if ($conexionOk) {
        $sql = file_get_contents(__DIR__ . '/db/install.sql');
        if ($conn->multi_query($sql)) {
            // consumir resultados múltiples
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());
            $msj = '<div class="ok">Base de datos instalada correctamente en <strong>localhost/' . $host . '</strong>.</div>';
            // Guardar configuración en db.php
            $config = "<?php\n// Configuración de conexión a la base de datos\ndefine('DB_HOST', '" . $host . "');\ndefine('DB_USER', '" . $user . "');\ndefine('DB_PASS', '" . addslashes($pass) . "');\ndefine('DB_NAME', 'pana');\n\nfunction conectarBD() {\n    try {\n        \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);\n    } catch (mysqli_sql_exception \$e) {\n        die(\"Error de conexión: \" . \$e->getMessage());\n    }\n    \$conn->set_charset(\"utf8mb4\");\n    return \$conn;\n}\n\n// Ruta base del proyecto\ndefine('BASE_URL', 'http://localhost/panatics/');\ndefine('UPLOAD_DIR', dirname(__DIR__) . '/assets/uploads/');\n?>";
            file_put_contents(__DIR__ . '/config/db.php', $config);
        } else {
            $msj = '<div class="error">Error: ' . $conn->error . '</div>';
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PANATICS - Instalación</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="card login-card">
        <h1>PANATICS ERP</h1>
        <p class="sub">Instalación de la base de datos</p>
        <?php echo $msj; ?>
        <form method="POST" action="install.php">
            <label>Servidor (Host)</label>
            <input type="text" name="host" value="<?php echo htmlspecialchars($host); ?>" required>
            <label>Usuario</label>
            <input type="text" name="user" value="<?php echo htmlspecialchars($user); ?>" required>
            <label>Contraseña</label>
            <input type="password" name="pass" value="<?php echo htmlspecialchars($pass); ?>">
            <button type="submit" name="guardar" value="1" class="btn btn-primary btn-block">Instalar Base de Datos</button>
        </form>
        <p class="hint">Después de instalar, ve a <a href="index.php">Inicio</a></p>
    </div>
</body>
</html>
