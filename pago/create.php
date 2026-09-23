<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$repPreseleccionada = isset($_GET['rep']) ? (int)$_GET['rep'] : 0;

$filtroEquipo = '';
if (!esAdmin()) {
    $filtroEquipo = "WHERE r.Id_tecnico = " . tecnicoIdActual();
}

$reparaciones = $conn->query(
    "SELECT r.Id_reparacion, r.costo_total,
            CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
            e.tipo_equipo, e.marca, e.modelo
     FROM reparacion r
     INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
     INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
     $filtroEquipo
     ORDER BY r.Id_reparacion DESC"
);

renderHeader('Nuevo Pago');
?>
<div class="page-header">
    <h1>Nuevo Pago</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<?php renderStepper('pago'); ?>

<?php if (isset($_GET['msj'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msj']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="card">
    <form action="../includes/save.php" method="POST">
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="modulo" value="pago">
        <div class="form-row">
            <div class="form-group">
                <label>Reparación</label>
                <select name="Id_reparacion" id="Id_reparacion" required>
                    <option value="">-- Seleccionar reparación --</option>
                    <?php while ($r = $reparaciones->fetch_assoc()): ?>
                        <?php $sel = ($r['Id_reparacion'] == $repPreseleccionada) ? 'selected' : ''; ?>
                        <option value="<?php echo $r['Id_reparacion']; ?>" <?php echo $sel; ?> data-costo="<?php echo $r['costo_total']; ?>">
                            #<?php echo $r['Id_reparacion']; ?> - <?php echo htmlspecialchars($r['cliente_nombre'] . ' - ' . $r['tipo_equipo'] . ' ' . $r['marca'] . ' ' . $r['modelo']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <?php if ($reparaciones->num_rows == 0): ?>
                    <small style="color:#c5221f;">No hay reparaciones. <a href="../reparacion/create.php">Crear reparación</a></small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Tipo de Pago</label>
                <select name="tipo_pago" required>
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>
            <div class="form-group">
                <label>Monto</label>
                <input type="number" step="0.01" min="0" name="monto" id="monto" value="0.00" required>
            </div>
            <div class="form-group">
                <label>Anticipo</label>
                <input type="number" step="0.01" min="0" name="anticipo" id="anticipo" value="0.00" required>
            </div>
            <div class="form-group">
                <label>Saldo Pendiente</label>
                <input type="number" step="0.01" min="0" name="saldo_pendiente" id="saldo_pendiente" value="0.00" readonly>
            </div>
            <div class="form-group">
                <label>Descuento de estudiante</label>
                <label style="display:flex;align-items:center;gap:8px;font-weight:normal;margin-top:6px;border:1px solid #1a73e8;border-radius:6px;padding:8px 10px;cursor:pointer;">
                    <input type="checkbox" name="descuento_estudiante" id="descuento_estudiante">
                    Aplicar 25% de descuento
                </label>
            </div>
            <div class="form-group">
                <label>Referencia</label>
                <input type="text" name="referencia" maxlength="30">
            </div>
            <div class="form-group">
                <label>Fecha de Pago</label>
                <input type="datetime-local" name="fecha_pago" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<script src="<?php echo BASE_URL; ?>assets/js/funciones.js"></script>
<?php
renderFooter();
$conn->close();
?>