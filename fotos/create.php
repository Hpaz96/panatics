<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$esAdmin = esAdmin();
$repPreseleccionada = isset($_GET['rep']) ? (int)$_GET['rep'] : 0;

$filtro = $esAdmin ? '' : 'WHERE r.Id_tecnico = ' . tecnicoIdActual();

// El técnico solo puede acceder a fotos de una reparación agendada a él
if (!$esAdmin && $repPreseleccionada > 0) {
    $chkR = $conn->query("SELECT Id_reparacion FROM reparacion WHERE Id_reparacion = $repPreseleccionada AND Id_tecnico = " . tecnicoIdActual());
    if (!$chkR || $chkR->num_rows == 0) {
        header('Location: index.php?err=' . urlencode('Solo el técnico agendado puede ver las fotos de esta reparación.'));
        exit;
    }
}

$reparaciones = $conn->query(
    "SELECT r.Id_reparacion, CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
            e.tipo_equipo, e.marca, e.modelo
     FROM reparacion r
     INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
     INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
     $filtro
     ORDER BY r.Id_reparacion DESC"
);

$vistas = [1 => 'Vista superior', 2 => 'Vista inferior', 3 => 'Vista lateral derecha', 4 => 'Vista lateral izquierda', 5 => 'Vista frontal'];

renderHeader('Nueva Foto');
?>
<div class="page-header">
    <h1>Nueva Foto</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<?php renderStepper('fotos'); ?>

<div class="card">
    <form action="../includes/save.php" method="POST" enctype="multipart/form-data" id="form_fotos">
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="modulo" value="fotos">
        <div class="form-row">
            <div class="form-group">
                <label>Reparación</label>
                <select name="Id_reparacion" required>
                    <option value="">-- Seleccionar reparación --</option>
                    <?php while ($r = $reparaciones->fetch_assoc()): ?>
                        <option value="<?php echo $r['Id_reparacion']; ?>" <?php echo $r['Id_reparacion'] == $repPreseleccionada ? 'selected' : ''; ?>>
                            #<?php echo $r['Id_reparacion']; ?> - <?php echo htmlspecialchars($r['cliente_nombre'] . ' - ' . $r['marca'] . ' ' . $r['modelo'] . ' (' . $r['tipo_equipo'] . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <?php if ($reparaciones->num_rows == 0): ?>
                    <small style="color:#c5221f;">No hay reparaciones agendadas. <a href="../reparacion/create.php">Crear reparación</a></small>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-row">
            <?php foreach ($vistas as $num => $vista): ?>
                <div class="form-group">
                    <label><?php echo htmlspecialchars($vista); ?></label>
                    <input type="file" name="vista_<?php echo $num; ?>" accept="image/*">
                    <small style="color:#666;">JPG, PNG, GIF, WEBP, BMP</small>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="form-actions">
            <button type="submit" name="proceso" value="1" id="btnSiguiente" class="btn btn-success">Siguiente &rarr; Pago</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<script src="<?php echo BASE_URL; ?>assets/js/funciones.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () { initSiguiente('form_fotos'); });
</script>
<?php
renderFooter();
$conn->close();
?>