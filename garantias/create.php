<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$esAdmin = esAdmin();
$repPreseleccionada = isset($_GET['rep']) ? (int)$_GET['rep'] : 0;

// Los técnicos solo pueden dar garantía a reparaciones agendadas a ellos
$filtroTec = $esAdmin ? '' : 'AND r.Id_tecnico = ' . tecnicoIdActual();

// Solo reparaciones con estatus "entregado" y sin garantía registrada
$reparaciones = $conn->query(
    "SELECT r.Id_reparacion, r.costo_total, r.fecha_entrega,
            CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
            e.tipo_equipo, e.marca, e.modelo
     FROM reparacion r
     INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
     INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
     LEFT JOIN garantias g ON g.Id_reparacion = r.Id_reparacion
     WHERE r.estatus = 'entregado' AND r.fecha_entrega IS NOT NULL AND g.Id_reparacion IS NULL" .
     $filtroTec . "
     ORDER BY r.Id_reparacion DESC"
);
$repPre = null;
if ($repPreseleccionada > 0) {
    $qPre = $conn->query(
        "SELECT r.Id_reparacion, r.fecha_entrega
         FROM reparacion r
         LEFT JOIN garantias g ON g.Id_reparacion = r.Id_reparacion
         WHERE r.Id_reparacion = $repPreseleccionada AND r.estatus = 'entregado' AND r.fecha_entrega IS NOT NULL AND g.Id_reparacion IS NULL" .
        ($esAdmin ? '' : " AND r.Id_tecnico = " . tecnicoIdActual())
    );
    if ($qPre && $qPre->num_rows) {
        $repPre = $qPre->fetch_assoc();
    }
}
$tecnicos = $conn->query("SELECT Id_tecnico, nombre, apellido FROM tecnico ORDER BY nombre");

function catZonaSel($tipoEquipo) {
    $t = strtolower($tipoEquipo);
    if (strpos($t, 'escritorio') !== false || strpos($t, 'laptop') !== false || strpos($t, 'todo en uno') !== false) return 'pc';
    if (strpos($t, 'control') !== false) return 'controles';
    if (strpos($t, 'videojuegos') !== false || strpos($t, 'consola') !== false) return 'videojuegos';
    return 'otro';
}

renderHeader('Nueva Garantía');
?>
<div class="page-header">
    <h1>Nueva Garantía</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="card">
    <form action="../includes/save.php" method="POST" id="form_garantia">
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="modulo" value="garantia">
        <div class="form-row">
            <div class="form-group">
                <label>Reparación (entregada)</label>
                <select name="Id_reparacion" id="Id_rep_sel" required <?php echo $repPre ? 'disabled' : ''; ?>>
                    <option value="">-- Seleccionar reparación --</option>
                    <?php while ($r = $reparaciones->fetch_assoc()): ?>
                        <option value="<?php echo $r['Id_reparacion']; ?>" <?php echo $r['Id_reparacion'] == $repPreseleccionada ? 'selected' : ''; ?> data-zona="<?php echo catZonaSel($r['tipo_equipo']); ?>" data-fe="<?php echo htmlspecialchars($r['fecha_entrega']); ?>">
                            #<?php echo $r['Id_reparacion']; ?> - <?php echo htmlspecialchars($r['cliente_nombre'] . ' - ' . $r['tipo_equipo'] . ' ' . $r['marca'] . ' ' . $r['modelo']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <?php if ($repPre): ?>
                    <input type="hidden" name="Id_reparacion" value="<?php echo $repPre['Id_reparacion']; ?>">
                    <small style="color:#666;">Reparación #<?php echo $repPre['Id_reparacion']; ?> seleccionada.</small>
                <?php endif; ?>
                <?php if ($reparaciones->num_rows == 0 && !$repPre): ?>
                    <small style="color:#c5221f;">No hay reparaciones "entregado" sin garantía. <a href="../reparacion/index.php">Ver reparaciones</a></small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Técnico</label>
                <?php if ($esAdmin): ?>
                <select name="Id_tecnico" required>
                    <option value="">-- Seleccionar técnico --</option>
                    <?php while ($t = $tecnicos->fetch_assoc()): ?>
                        <option value="<?php echo $t['Id_tecnico']; ?>"><?php echo htmlspecialchars($t['nombre'] . ' ' . $t['apellido']); ?></option>
                    <?php endwhile; ?>
                </select>
                <?php if ($tecnicos->num_rows == 0): ?>
                    <small style="color:#c5221f;">No hay técnicos. <a href="../tecnico/create.php">Crear técnico</a></small>
                <?php endif; ?>
                <?php else: ?>
                    <input type="hidden" name="Id_tecnico" value="<?php echo tecnicoIdActual(); ?>">
                    <input type="text" value="Técnico actual" disabled>
                    <small style="color:#666;">Se registrará tu usuario como técnico responsable.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Inicio de Garantía (Fecha de Entrega)</label>
                <input type="datetime-local" name="fecha_entrega" id="fecha_entrega" readonly style="background:#f0f0f0;cursor:not-allowed;" title="Se toma automáticamente de la fecha en que el equipo cambió a estatus 'entregado'.">
                <small style="color:#666;">Fecha bloqueada: se toma de la fecha del cambio a "entregado".</small>
            </div>
            <div class="form-group">
                <label>Vencimiento de Garantía (60 días naturales)</label>
                <input type="datetime-local" name="fecha_termino_garantia" id="fecha_termino_garantia" readonly style="background:#f0f0f0;cursor:not-allowed;" title="Siempre se calcula como 60 días naturales después de la fecha de entrega.">
            </div>
            <div class="form-group">
                <label>Zona de falla (Hardware)</label>
                <select name="zona_falla_h" id="zona_falla_h" data-valor="">
                    <option value="">-- Seleccionar --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Zona de falla (Software)</label>
                <select name="zona_falla_s" id="zona_falla_s" data-valor="">
                    <option value="">-- Seleccionar --</option>
                </select>
            </div>
            <div class="form-group" style="flex:1 1 100%;">
                <label>Motivo de la Garantía</label>
                <textarea name="motivo_garantia" rows="3" placeholder="Describe el motivo o cobertura de esta garantía..." required></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<script src="<?php echo BASE_URL; ?>assets/js/zonas.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initZonas('Id_rep_sel');
        const sel = document.getElementById('Id_rep_sel');
        const fe = document.getElementById('fecha_entrega');
        const ft = document.getElementById('fecha_termino_garantia');
        function fmtDT(valor) {
            if (!valor) return '';
            const d = new Date(valor);
            if (isNaN(d.getTime())) return '';
            const pad = function (n) { return ('0' + n).slice(-2); };
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }
        function calcTermino() {
            if (!fe.value) { ft.value = ''; return; }
            const m = fe.value.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/);
            const d = new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]);
            d.setDate(d.getDate() + 60);
            const pad = function (n) { return ('0' + n).slice(-2); };
            ft.value = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }
        function aplicarFechaRep() {
            const opt = sel.selectedOptions[0];
            const feVal = opt ? (opt.dataset.fe || '') : '';
            fe.value = fmtDT(feVal);
            calcTermino();
        }
        sel.addEventListener('change', aplicarFechaRep);
        aplicarFechaRep();
        calcTermino();
        document.getElementById('form_garantia')?.addEventListener('submit', function () {
            sel.disabled = false;
            fe.disabled = false;
            ft.disabled = false;
        });
    });
</script>
<?php
renderFooter();
$conn->close();
?>