<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/templates.php';

// Verificar que la BD existe
try {
    $conectar = new mysqli(DB_HOST, DB_USER, DB_PASS);
    $bdExiste = $conectar->select_db(DB_NAME);
    $conectar->close();
} catch (mysqli_sql_exception $e) {
    $bdExiste = false;
}

if (!$bdExiste) {
    header('Location: ' . BASE_URL . 'install.php');
    exit;
}

$conn = conectarBD();
$stats = [];
$stats['clientes'] = $conn->query("SELECT COUNT(*) c FROM cliente")->fetch_assoc()['c'];
$stats['tecnicos'] = $conn->query("SELECT COUNT(*) c FROM tecnico")->fetch_assoc()['c'];
$stats['equipos'] = $conn->query("SELECT COUNT(*) c FROM equipo")->fetch_assoc()['c'];
$stats['reparaciones'] = $conn->query("SELECT COUNT(*) c FROM reparacion")->fetch_assoc()['c'];
$stats['pagos'] = $conn->query("SELECT COUNT(*) c FROM pago")->fetch_assoc()['c'];
$stats['fotos'] = $conn->query("SELECT COUNT(*) c FROM fotos")->fetch_assoc()['c'];
$stats['garantias'] = $conn->query("SELECT COUNT(*) c FROM garantias")->fetch_assoc()['c'];
$stats['precios'] = $conn->query("SELECT COUNT(*) c FROM precios")->fetch_assoc()['c'];
$esAdmin = esAdmin();

renderHeader('Panel Principal');
?>
<div class="page-header">
    <h1>Panel Principal</h1>
</div>

<div class="stats">
    <div class="stat-card blue"><h3>Clientes</h3><div class="value"><?php echo $stats['clientes']; ?></div></div>
    <div class="stat-card green"><h3>Técnicos</h3><div class="value"><?php echo $stats['tecnicos']; ?></div></div>
    <div class="stat-card orange"><h3>Equipos</h3><div class="value"><?php echo $stats['equipos']; ?></div></div>
    <div class="stat-card blue"><h3>Reparaciones</h3><div class="value"><?php echo $stats['reparaciones']; ?></div></div>
    <div class="stat-card green"><h3>Pagos</h3><div class="value"><?php echo $stats['pagos']; ?></div></div>
    <div class="stat-card orange"><h3>Fotos</h3><div class="value"><?php echo $stats['fotos']; ?></div></div>
</div>

<?php if ($esAdmin || true): ?>
<div class="stats" style="margin-top:14px;">
    <?php if ($esAdmin): ?>
        <div class="stat-card green"><h3>Garantías</h3><div class="value"><?php echo $stats['garantias']; ?></div></div>
    <?php endif; ?>
    <div class="stat-card blue"><h3>Servicios (Precios)</h3><div class="value"><?php echo $stats['precios']; ?></div></div>
</div>
<?php endif; ?>

<div class="card">
    <h2 style="margin-bottom:15px;">Módulos del sistema</h2>
    <table class="data">
        <thead>
            <tr><th>Módulo</th><th>Descripción</th><th>Acción</th></tr>
        </thead>
        <tbody>
            <tr><td>Clientes</td><td>Gestión de clientes, datos y sucursal</td><td><a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>cliente/index.php">Entrar</a></td></tr>
            <?php if ($esAdmin): ?>
                <tr><td>Técnicos</td><td>Registro de técnicos y zonas</td><td><a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>tecnico/index.php">Entrar</a></td></tr>
            <?php endif; ?>
            <tr><td>Equipos</td><td>Equipos de cómputo y videojuegos por cliente</td><td><a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>equipo/index.php">Entrar</a></td></tr>
            <tr><td>Reparaciones</td><td>Seguimiento de reparaciones y estatus</td><td><a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>reparacion/index.php">Entrar</a></td></tr>
            <tr><td>Precios</td><td>Lista de precios por servicio (Hardware/Software)</td><td><a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>precios/index.php">Entrar</a></td></tr>
            <?php if ($esAdmin): ?>
                <tr><td>Pagos</td><td>Pagos y saldos pendientes de reparaciones</td><td><a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>pago/index.php">Entrar</a></td></tr>
                <tr><td>Fotos</td><td>Imágenes por reparación</td><td><a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>fotos/index.php">Entrar</a></td></tr>
                <tr><td>Garantías</td><td>Garantías de 60 días por reparación reparada</td><td><a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>garantias/index.php">Entrar</a></td></tr>
                <tr><td>Bitácora</td><td>Registro de modificaciones del sistema</td><td><a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>bitacora/index.php">Entrar</a></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
renderFooter();
$conn->close();
?>
