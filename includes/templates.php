<?php
// Cabecera común del sistema
require_once __DIR__ . '/auth.php';

function renderHeader($titulo) {
    require_once __DIR__ . '/auth.php';
    requireLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PANATICS ERP - <?php echo $titulo; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <a href="<?php echo BASE_URL; ?>index.php" class="brand">PAN<span>ATICS</span> ERP</a>
        <nav>
            <a href="<?php echo BASE_URL; ?>cliente/index.php">Clientes</a>
            <?php if (esAdmin()): ?>
                <a href="<?php echo BASE_URL; ?>tecnico/index.php">Técnicos</a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>equipo/index.php">Equipos</a>
            <a href="<?php echo BASE_URL; ?>reparacion/index.php">Reparaciones</a>
            <a href="<?php echo BASE_URL; ?>precios/index.php">Precios</a>
            <a href="<?php echo BASE_URL; ?>pago/index.php">Pagos</a>
            <a href="<?php echo BASE_URL; ?>fotos/index.php">Fotos</a>
            <a href="<?php echo BASE_URL; ?>garantias/index.php">Garantías</a>
            <?php if (esAdmin()): ?>
                <a href="<?php echo BASE_URL; ?>bitacora/index.php">Bitácora</a>
            <?php endif; ?>
        </nav>
        <div class="user-nav">
            <span class="user-name">
                <?php echo htmlspecialchars(isset($_SESSION['nombre']) && $_SESSION['nombre'] !== '' ? $_SESSION['nombre'] : 'Técnico'); ?>
                <?php if (esAdmin()): ?><span style="background:#188038;color:#fff;border-radius:10px;padding:1px 8px;font-size:11px;margin-left:4px;">Admin</span><?php endif; ?>
            </span>
            <a href="<?php echo BASE_URL; ?>logout.php" class="logout">Cerrar sesión</a>
        </div>
    </div>
    <div class="container">
<?php
}

function renderFooter() {
?>
    </div>
</body>
</html>
<?php
}

// Barra de pasos del flujo: Cliente -> Equipo -> Reparacion -> Fotos -> Pago
function renderStepper($paso) {
    $pasos = ['1. Cliente', '2. Equipo', '3. Reparacion', '4. Fotos', '5. Pago'];
    $indices = ['cliente' => 0, 'equipo' => 1, 'reparacion' => 2, 'fotos' => 3, 'pago' => 4];
    $n = $indices[$paso] ?? 0;
    echo '<div style="background:#f1f3f4;border-radius:8px;padding:10px 14px;margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">';
    foreach ($pasos as $i => $p) {
        if ($i < $n) { $style = 'background:#188038;color:#fff;'; }
        elseif ($i === $n) { $style = 'background:#1a73e8;color:#fff;font-weight:bold;'; }
        else { $style = 'background:#e0e0e0;color:#666;'; }
        echo '<span style="padding:4px 12px;border-radius:14px;font-size:12px;' . $style . '">' . $p . '</span>';
    }
    echo '</div>';
}
?>
