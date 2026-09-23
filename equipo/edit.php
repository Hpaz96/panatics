<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$res = $conn->query("SELECT * FROM equipo WHERE Id_equipo = $id");
if ($res->num_rows == 0) {
    header('Location: index.php?err=' . urlencode('Equipo no encontrado.'));
    exit;
}
$d = $res->fetch_assoc();

// Solo el administrador o el técnico agendado (con reparación de este equipo) pueden editar
$puedeEditar = esAdmin();
if (!$puedeEditar) {
    $ag = $conn->query("SELECT r.Id_reparacion FROM reparacion r WHERE r.Id_equipo = $id AND r.Id_tecnico = " . tecnicoIdActual() . " LIMIT 1");
    $puedeEditar = ($ag && $ag->num_rows > 0);
}
if (!$puedeEditar) {
    header('Location: index.php?err=' . urlencode('Solo el técnico agendado puede editar este equipo.'));
    exit;
}

$clientes = $conn->query("SELECT Id_cliente, nombre, apellido FROM cliente ORDER BY nombre");

renderHeader('Editar Equipo');
?>
<div class="page-header">
    <h1>Editar Equipo #<?php echo $d['Id_equipo']; ?></h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<div class="card">
    <form action="../includes/save.php" method="POST">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="modulo" value="equipo">
        <input type="hidden" name="id" value="<?php echo $d['Id_equipo']; ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Cliente</label>
                <select name="Id_cliente" required>
                    <option value="">-- Seleccionar cliente --</option>
                    <?php while ($c = $clientes->fetch_assoc()): ?>
                        <option value="<?php echo $c['Id_cliente']; ?>" <?php echo $c['Id_cliente'] == $d['Id_cliente'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nombre'] . ' ' . $c['apellido'] . ' (#' . $c['Id_cliente'] . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de Equipo</label>
                <select name="tipo_equipo" id="tipo_equipo" data-valor="<?php echo htmlspecialchars($d['tipo_equipo']); ?>">
                </select>
            </div>
            <div class="form-group">
                <label>Marca</label>
                <select name="marca" id="marca" data-valor="<?php echo htmlspecialchars($d['marca']); ?>">
                </select>
            </div>
            <div class="form-group">
                <label>Modelo</label>
                <input type="text" name="modelo" maxlength="30" value="<?php echo htmlspecialchars($d['modelo']); ?>" required>
            </div>
            <div class="form-group">
                <label>Serie</label>
                <input type="text" name="serie" maxlength="30" value="<?php echo htmlspecialchars($d['serie']); ?>">
            </div>
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color" maxlength="30" value="<?php echo htmlspecialchars($d['color']); ?>">
            </div>
            <div class="form-group">
                <label>Accesorios</label>
                <input type="text" name="accesorios" maxlength="30" value="<?php echo htmlspecialchars($d['accesorios']); ?>">
            </div>
            <div class="form-group">
                <label>Estado Físico</label>
                <select name="estado_fisico">
                    <option value="Sin daños" <?php echo $d['estado_fisico'] == 'Sin daños' ? 'selected' : ''; ?>>Sin daños</option>
                    <option value="daños visibles" <?php echo $d['estado_fisico'] == 'daños visibles' ? 'selected' : ''; ?>>Daños visibles</option>
                    <option value="daños internos" <?php echo $d['estado_fisico'] == 'daños internos' ? 'selected' : ''; ?>>Daños internos</option>
                </select>
            </div>
            <div class="form-group">
                <label>Observaciones</label>
                <input type="text" name="observaciones" maxlength="255" value="<?php echo htmlspecialchars($d['observaciones']); ?>">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<script src="<?php echo BASE_URL; ?>assets/js/funciones.js"></script>
<?php
renderFooter();
$conn->close();
?>