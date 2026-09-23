<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$esAdmin = esAdmin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$res = $conn->query("SELECT * FROM pago WHERE Id_pago = $id");
if ($res->num_rows == 0) {
    header('Location: index.php?err=' . urlencode('Pago no encontrado.'));
    exit;
}
$d = $res->fetch_assoc();

// Solo admin o el técnico agendado de la reparación del pago
if (!$esAdmin) {
    $chkR = $conn->query("SELECT r.Id_reparacion FROM pago p INNER JOIN reparacion r ON r.Id_reparacion = p.Id_reparacion WHERE p.Id_pago = $id AND r.Id_tecnico = " . tecnicoIdActual());
    if (!$chkR || $chkR->num_rows == 0) {
        header('Location: index.php?err=' . urlencode('Solo el técnico agendado puede editar este pago.'));
        exit;
    }
}

$filtro = $esAdmin ? '' : 'WHERE r.Id_tecnico = ' . tecnicoIdActual();
$reparaciones = $conn->query(
    "SELECT r.Id_reparacion, r.costo_total,
            CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
            e.tipo_equipo, e.marca, e.modelo
     FROM reparacion r
     INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
     INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
     $filtro
     ORDER BY r.Id_reparacion DESC"
);
$fp = date('Y-m-d\TH:i', strtotime($d['fecha_pago']));

renderHeader('Editar Pago');
?>
<div class="page-header">
    <h1>Editar Pago #<?php echo $d['Id_pago']; ?></h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<div class="card">
    <form action="../includes/save.php" method="POST">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="modulo" value="pago">
        <input type="hidden" name="id" value="<?php echo $d['Id_pago']; ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Reparación</label>
                <select name="Id_reparacion" id="Id_reparacion" required>
                    <option value="">-- Seleccionar reparación --</option>
                    <?php while ($r = $reparaciones->fetch_assoc()): ?>
                        <option value="<?php echo $r['Id_reparacion']; ?>" <?php echo $r['Id_reparacion'] == $d['Id_reparacion'] ? 'selected' : ''; ?> data-costo="<?php echo $r['costo_total']; ?>">
                            #<?php echo $r['Id_reparacion']; ?> - <?php echo htmlspecialchars($r['cliente_nombre'] . ' - ' . $r['tipo_equipo'] . ' ' . $r['marca'] . ' ' . $r['modelo']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de Pago</label>
                <select name="tipo_pago" required>
                    <option value="efectivo" <?php echo $d['tipo_pago'] == 'efectivo' || $d['tipo_pago'] == 'Efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                    <option value="transferencia" <?php echo $d['tipo_pago'] == 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                    <?php if (!in_array($d['tipo_pago'], ['efectivo', 'transferencia', 'Efectivo'])): ?>
                        <option value="<?php echo htmlspecialchars($d['tipo_pago']); ?>" selected><?php echo htmlspecialchars($d['tipo_pago']); ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Monto</label>
                <input type="number" step="0.01" min="0" name="monto" id="monto" value="<?php echo $d['monto']; ?>" required>
            </div>
            <div class="form-group">
                <label>Anticipo</label>
                <input type="number" step="0.01" min="0" name="anticipo" id="anticipo" value="<?php echo $d['anticipo']; ?>" required>
            </div>
            <div class="form-group">
                <label>Saldo Pendiente</label>
                <input type="number" step="0.01" min="0" name="saldo_pendiente" id="saldo_pendiente" value="<?php echo $d['saldo_pendiente']; ?>" readonly>
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
                <input type="text" name="referencia" maxlength="30" value="<?php echo htmlspecialchars($d['referencia']); ?>">
            </div>
            <div class="form-group">
                <label>Fecha de Pago</label>
                <input type="datetime-local" name="fecha_pago" value="<?php echo $fp; ?>" required>
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