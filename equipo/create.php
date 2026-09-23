<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$clientePre = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
$clientes = $conn->query("SELECT Id_cliente, nombre, apellido FROM cliente ORDER BY nombre");

renderHeader('Nuevo Equipo');
?>
<div class="page-header">
    <h1>Nuevo Equipo</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<?php renderStepper('equipo'); ?>

<?php if (isset($_GET['msj'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msj']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="card">
    <form action="../includes/save.php" method="POST" id="form_equipo">
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="modulo" value="equipo">
        <div class="form-row">
            <div class="form-group">
                <label>Cliente</label>
                <select name="Id_cliente" required>
                    <option value="">-- Seleccionar cliente --</option>
                    <?php if ($clientes->num_rows > 0): ?>
                        <?php while ($c = $clientes->fetch_assoc()): ?>
                            <option value="<?php echo $c['Id_cliente']; ?>" <?php echo $c['Id_cliente'] == $clientePre ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nombre'] . ' ' . $c['apellido'] . ' (#' . $c['Id_cliente'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <?php if ($clientes->num_rows == 0): ?>
                    <small style="color:#c5221f;">No hay clientes. <a href="../cliente/create.php">Crear cliente</a></small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Tipo de Equipo</label>
                <select name="tipo_equipo" id="tipo_equipo" required>
                    <option value="">-- Seleccionar tipo --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Marca</label>
                <select name="marca" id="marca" required>
                    <option value="">-- Seleccionar marca --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Modelo</label>
                <input type="text" name="modelo" maxlength="30" required>
            </div>
            <div class="form-group">
                <label>Serie</label>
                <input type="text" name="serie" maxlength="30">
            </div>
            <div class="form-group">
                <label>Color</label>
                <input type="text" name="color" maxlength="30">
            </div>
            <div class="form-group">
                <label>Accesorios</label>
                <input type="text" name="accesorios" maxlength="30">
            </div>
            <div class="form-group">
                <label>Estado Físico</label>
                <select name="estado_fisico" required>
                    <option value="Sin daños">Sin daños</option>
                    <option value="daños visibles">Daños visibles</option>
                    <option value="daños internos">Daños internos</option>
                </select>
            </div>
            <div class="form-group">
                <label>Observaciones</label>
                <input type="text" name="observaciones" maxlength="255">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" name="proceso" value="1" id="btnSiguiente" class="btn btn-success">Siguiente &rarr; Reparaci&oacute;n</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<script src="<?php echo BASE_URL; ?>assets/js/funciones.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initSiguiente('form_equipo');
    });
</script>
<?php
renderFooter();
$conn->close();
?>