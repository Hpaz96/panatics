<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$esAdmin = esAdmin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$res = $conn->query("SELECT * FROM garantias WHERE Id_reparacion = $id");
if ($res->num_rows == 0) {
    header('Location: index.php?err=' . urlencode('Garantía no encontrada.'));
    exit;
}
$d = $res->fetch_assoc();

// Un técnico solo puede editar garantías de reparaciones agendadas a él
if (!$esAdmin && $d['Id_tecnico'] != tecnicoIdActual()) {
    header('Location: index.php?err=' . urlencode('Solo puedes modificar garantías de tus propias reparaciones.'));
    exit;
}

$rep = $conn->query(
    "SELECT r.Id_equipo, r.costo_total,
            CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
            e.tipo_equipo, e.marca, e.modelo
     FROM reparacion r
     INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
     INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
     WHERE r.Id_reparacion = $id"
)->fetch_assoc();

$tecnicos = $conn->query("SELECT Id_tecnico, nombre, apellido FROM tecnico ORDER BY nombre");

function catZonaEq($tipoEquipo) {
    $t = strtolower($tipoEquipo);
    if (strpos($t, 'escritorio') !== false || strpos($t, 'laptop') !== false || strpos($t, 'todo en uno') !== false) return 'pc';
    if (strpos($t, 'control') !== false) return 'controles';
    if (strpos($t, 'videojuegos') !== false || strpos($t, 'consola') !== false) return 'videojuegos';
    return 'otro';
}

$zonaCat = catZonaEq($rep['tipo_equipo']);
$fe = date('Y-m-d\TH:i', strtotime($d['fecha_entrega']));
$ft = date('Y-m-d\TH:i', strtotime($d['fecha_termino_garantia']));

renderHeader('Editar Garantía');
?>
<div class="page-header">
    <h1>Editar Garantía #<?php echo $id; ?></h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="card">
    <form action="../includes/save.php" method="POST">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="modulo" value="garantia">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="Id_reparacion" value="<?php echo $id; ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Reparación</label>
                <input type="text" value="#<?php echo $id; ?> - <?php echo htmlspecialchars($rep['cliente_nombre'] . ' - ' . $rep['tipo_equipo'] . ' ' . $rep['marca'] . ' ' . $rep['modelo']); ?>" disabled>
            </div>
            <div class="form-group">
                <label>Técnico</label>
                <?php if ($esAdmin): ?>
                <select name="Id_tecnico" required>
                    <option value="">-- Seleccionar técnico --</option>
                    <?php while ($t = $tecnicos->fetch_assoc()): ?>
                        <option value="<?php echo $t['Id_tecnico']; ?>" <?php echo $t['Id_tecnico'] == $d['Id_tecnico'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['nombre'] . ' ' . $t['apellido']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <?php else: ?>
                    <input type="hidden" name="Id_tecnico" value="<?php echo $d['Id_tecnico']; ?>">
                    <input type="text" value="Técnico actual" disabled>
                    <small style="color:#666;">Se conserva el técnico responsable de esta garantía.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Inicio de Garantía (Fecha de Entrega)</label>
                <input type="datetime-local" name="fecha_entrega" id="fecha_entrega" value="<?php echo $fe; ?>" readonly style="background:#f0f0f0;cursor:not-allowed;" title="Se toma automáticamente de la fecha en que el equipo cambió a estatus 'entregado'.">
                <small style="color:#666;">Fecha bloqueada: se toma de la fecha del cambio a "entregado".</small>
            </div>
            <div class="form-group">
                <label>Vencimiento de Garantía (60 días naturales)</label>
                <input type="datetime-local" name="fecha_termino_garantia" id="fecha_termino_garantia" value="<?php echo $ft; ?>" readonly style="background:#f0f0f0;cursor:not-allowed;" title="Siempre se calcula como 60 días naturales después de la fecha de entrega.">
            </div>
            <div class="form-group">
                <label>Zona de falla (Hardware)</label>
                <select name="zona_falla_h" id="zona_falla_h" data-zona="<?php echo $zonaCat; ?>" data-valor="<?php echo htmlspecialchars($d['zona_falla_h'], ENT_QUOTES); ?>">
                    <option value="">-- Seleccionar --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Zona de falla (Software)</label>
                <select name="zona_falla_s" id="zona_falla_s" data-zona="<?php echo $zonaCat; ?>" data-valor="<?php echo htmlspecialchars($d['zona_falla_s'], ENT_QUOTES); ?>">
                    <option value="">-- Seleccionar --</option>
                </select>
            </div>
            <div class="form-group" style="flex:1 1 100%;">
                <label>Motivo de la Garantía</label>
                <textarea name="motivo_garantia" rows="3" required><?php echo htmlspecialchars($d['motivo_garantia']); ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<script src="<?php echo BASE_URL; ?>assets/js/zonas.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        poblarZonasDirecto('<?php echo $zonaCat; ?>');
        const fe = document.getElementById('fecha_entrega');
        const ft = document.getElementById('fecha_termino_garantia');
        function calcTermino() {
            if (!fe.value) return;
            const m = fe.value.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/);
            const d = new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]);
            d.setDate(d.getDate() + 60);
            const pad = function (n) { return ('0' + n).slice(-2); };
            ft.value = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }
        fe.addEventListener('input', calcTermino);
    });
</script>
<?php
renderFooter();
$conn->close();
?>