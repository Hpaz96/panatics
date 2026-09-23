<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['tecnico_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $pass = $_POST['pass'] ?? '';

    if ($usuario === '' || $pass === '') {
        $error = 'Ingresa usuario y contraseña.';
    } else {
        $conn = conectarBD();
        $stmt = $conn->prepare("SELECT Id_tecnico, nombre, apellido, `User`, `role` FROM tecnico WHERE `User` = ? AND `pass` = ? LIMIT 1");
        $stmt->bind_param('ss', $usuario, $pass);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $t = $res->fetch_assoc();
            $_SESSION['tecnico_id'] = (int)$t['Id_tecnico'];
            $_SESSION['nombre'] = $t['nombre'] . ' ' . $t['apellido'];
            $_SESSION['usuario'] = $t['User'];
            $_SESSION['role'] = $t['role'];
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
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
    <title>PANATICS ERP - Iniciar sesión</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
    <div class="card login-card">
        <div style="text-align:center;margin-bottom:10px;">
            <img src="assets/img/logo.jpg" alt="PanaTics" style="max-height:120px;max-width:100%;height:auto;width:auto;">
        </div>
        <h1>PANATICS ERP</h1>
        <p class="sub">Inicia sesión para continuar</p>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" value="<?php echo htmlspecialchars($usuario); ?>" autofocus required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="pass" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>
    </div>
</body>
</html>