<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$esAdmin = esAdmin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$res = $conn->query("SELECT * FROM fotos WHERE id_foto = $id");
if ($res->num_rows == 0) {
    header('Location: index.php?err=' . urlencode('Foto no encontrada.'));
    exit;
}
$d = $res->fetch_assoc();

// Solo admin o el técnico agendado de la reparación
if (!$esAdmin) {
    $chkR = $conn->query("SELECT Id_reparacion FROM reparacion WHERE Id_reparacion = " . (int)$d['Id_reparacion'] . " AND Id_tecnico = " . tecnicoIdActual());
    if (!$chkR || $chkR->num_rows == 0) {
        header('Location: index.php?err=' . urlencode('Solo el técnico agendado puede editar estas fotos.'));
        exit;
    }
}

$filtro = $esAdmin ? '' : 'WHERE r.Id_tecnico = ' . tecnicoIdActual();
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
$vistoActual = isset($vistas[(int)$d['tipo_foto']]) ? $vistas[(int)$d['tipo_foto']] : ('Tipo ' . $d['tipo_foto']);

renderHeader('Editar Foto');
?>
<div class="page-header">
    <h1>Editar Foto #<?php echo $d['id_foto']; ?></h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<div class="card">
    <p style="margin-bottom:15px;">
        Imagen actual (<?php echo htmlspecialchars($vistoActual); ?>):<br>
        <a href="<?php echo BASE_URL . htmlspecialchars($d['ruta_imagen']); ?>" target="_blank">
            <img src="<?php echo BASE_URL . htmlspecialchars($d['ruta_imagen']); ?>" class="thumbnail" style="width:120px;height:120px;" alt="Foto">
        </a>
    </p>
    <form action="../includes/save.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="modulo" value="fotos">
        <input type="hidden" name="id" value="<?php echo $d['id_foto']; ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Reparación</label>
                <select name="Id_reparacion" required>
                    <option value="">-- Seleccionar reparación --</option>
                    <?php while ($r = $reparaciones->fetch_assoc()): ?>
                        <option value="<?php echo $r['Id_reparacion']; ?>" <?php echo $r['Id_reparacion'] == $d['Id_reparacion'] ? 'selected' : ''; ?>>
                            #<?php echo $r['Id_reparacion']; ?> - <?php echo htmlspecialchars($r['cliente_nombre'] . ' - ' . $r['marca'] . ' ' . $r['modelo']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Vista</label>
                <select name="tipo_foto" required>
                    <?php foreach ($vistas as $num => $vista): ?>
                        <option value="<?php echo $num; ?>" <?php echo (int)$d['tipo_foto'] == $num ? 'selected' : ''; ?>><?php echo htmlspecialchars($vista); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Reemplazar imagen (opcional)</label>
                <input type="file" name="ruta_imagen" accept="image/*">
                <small style="color:#666;">Solo se sube si eliges un archivo nuevo.</small>
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