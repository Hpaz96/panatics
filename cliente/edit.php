<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$res = $conn->query("SELECT * FROM cliente WHERE Id_cliente = $id");
if ($res->num_rows == 0) {
    header('Location: index.php?err=' . urlencode('Cliente no encontrado.'));
    exit;
}
$d = $res->fetch_assoc();

// Solo el administrador o el técnico designado (con reparación del cliente) editan
$puedeEditar = esAdmin();
if (!$puedeEditar) {
    $ag = $conn->query(
        "SELECT r.Id_reparacion FROM reparacion r
         INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
         WHERE e.Id_cliente = $id AND r.Id_tecnico = " . tecnicoIdActual() . " LIMIT 1"
    );
    $puedeEditar = ($ag && $ag->num_rows > 0);
}
if (!$puedeEditar) {
    header('Location: index.php?err=' . urlencode('Solo el técnico designado puede editar este cliente.'));
    exit;
}

$fecha = date('Y-m-d\TH:i', strtotime($d['fecha']));

renderHeader('Editar Cliente');
?>
<div class="page-header">
    <h1>Editar Cliente #<?php echo $d['Id_cliente']; ?></h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<div class="card">
    <form action="../includes/save.php" method="POST">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="modulo" value="cliente">
        <input type="hidden" name="id" value="<?php echo $d['Id_cliente']; ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" maxlength="40" value="<?php echo htmlspecialchars($d['nombre']); ?>" required>
            </div>
            <div class="form-group">
                <label>Apellido</label>
                <input type="text" name="apellido" maxlength="50" value="<?php echo htmlspecialchars($d['apellido']); ?>" required>
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" maxlength="15" value="<?php echo htmlspecialchars($d['telefono']); ?>" required>
            </div>
            <div class="form-group">
                <label>Correo</label>
                <input type="email" name="correo" maxlength="30" value="<?php echo htmlspecialchars($d['correo']); ?>" required>
            </div>
            <div class="form-group">
                <label>Fecha</label>
                <input type="datetime-local" name="fecha" value="<?php echo $fecha; ?>" required>
            </div>
            <div class="form-group">
                <label>Sucursal</label>
                <select name="sucursal" required>
                    <option value="san andres" <?php echo $d['sucursal'] == 'san andres' ? 'selected' : ''; ?>>San Andrés</option>
                    <option value="tlazala" <?php echo $d['sucursal'] == 'tlazala' ? 'selected' : ''; ?>>Tlazala</option>
                </select>
            </div>
            <div class="form-group">
                <label>RFC</label>
                <input type="text" name="rfc" maxlength="30" value="<?php echo htmlspecialchars($d['rfc']); ?>">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<?php
renderFooter();
$conn->close();
?>
