<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$res = $conn->query("SELECT * FROM reparacion WHERE Id_reparacion = $id");
if ($res->num_rows == 0) {
    header('Location: index.php?err=' . urlencode('Reparación no encontrada.'));
    exit;
}
$d = $res->fetch_assoc();

// Un técnico solo puede editar sus propias reparaciones
if (!esAdmin() && $d['Id_tecnico'] != tecnicoIdActual()) {
    header('Location: index.php?err=' . urlencode('Solo puedes modificar tus propias reparaciones.'));
    exit;
}
$esAdmin = esAdmin();
$tecnicoId = tecnicoIdActual();

function catZonaEq($tipoEquipo) {
    $t = strtolower($tipoEquipo);
    if (strpos($t, 'escritorio') !== false || strpos($t, 'laptop') !== false || strpos($t, 'todo en uno') !== false) return 'pc';
    if (strpos($t, 'control') !== false) return 'controles';
    if (strpos($t, 'videojuegos') !== false || strpos($t, 'consola') !== false) return 'videojuegos';
    return 'otro';
}

$equipos = $conn->query(
    "SELECT e.Id_equipo, e.marca, e.modelo, e.tipo_equipo,
            CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre
     FROM equipo e
     INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
     ORDER BY e.marca"
);
$tecnicos = $conn->query("SELECT Id_tecnico, nombre, apellido FROM tecnico ORDER BY nombre");

$fr = date('Y-m-d\TH:i', strtotime($d['fecha_recepcion']));

renderHeader('Editar Reparación');
?>
<div class="page-header">
    <h1>Editar Reparación #<?php echo $d['Id_reparacion']; ?></h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<div class="card">
    <form action="../includes/save.php" method="POST">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="modulo" value="reparacion">
        <input type="hidden" name="id" value="<?php echo $d['Id_reparacion']; ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Equipo</label>
                <select name="Id_equipo" id="Id_equipo" required>
                    <option value="">-- Seleccionar equipo --</option>
                    <?php $equipos->data_seek(0); while ($e = $equipos->fetch_assoc()): ?>
                        <?php
                            $sel = ($e['Id_equipo'] == $d['Id_equipo']);
                            $extra = ' data-zona="' . catZonaEq($e['tipo_equipo']) . '"';
                            $extra .= ' data-tipo="' . htmlspecialchars($e['tipo_equipo'], ENT_QUOTES) . '"';
                            if ($sel) {
                                $extra .= ' data-zh="' . htmlspecialchars($d['zona_falla_h'], ENT_QUOTES) . '"';
                                $extra .= ' data-zs="' . htmlspecialchars($d['zona_falla_s'], ENT_QUOTES) . '"';
                            }
                        ?>
                        <option value="<?php echo $e['Id_equipo']; ?>" <?php echo $sel ? 'selected' : ''; ?><?php echo $extra; ?>>
                            <?php echo htmlspecialchars($e['marca'] . ' ' . $e['modelo'] . ' (' . $e['tipo_equipo'] . ') - ' . $e['cliente_nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de equipo</label>
                <input type="text" id="tipo_equipo_rep" value="" readonly style="background:#f0f0f0;cursor:not-allowed;" placeholder="Se muestra según el equipo seleccionado">
            </div>
            <div class="form-group">
                <label>Técnico asignado</label>
                <?php if ($esAdmin): ?>
                    <select name="Id_tecnico" required>
                        <option value="">-- Seleccionar técnico --</option>
                        <?php $tecnicos->data_seek(0); while ($t = $tecnicos->fetch_assoc()): ?>
                            <option value="<?php echo $t['Id_tecnico']; ?>" <?php echo $t['Id_tecnico'] == $d['Id_tecnico'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['nombre'] . ' ' . $t['apellido']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                <?php else: ?>
                    <input type="hidden" name="Id_tecnico" value="<?php echo $tecnicoId; ?>">
                    <input type="text" value="<?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Tú'); ?>" disabled>
                    <small style="color:#666;">Tu técnico está asignado a esta reparación y no puede cambiarse.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Fecha de Recepción</label>
                <input type="datetime-local" name="fecha_recepcion" value="<?php echo $fr; ?>" required>
            </div>
            <div class="form-group">
                <label>Estatus</label>
                <select name="estatus" required>
                    <?php $arr = ['pendiente' => 'Pendiente', 'en reparación' => 'En reparación', 'reparado' => 'Reparado', 'entregado' => 'Entregado', 'no finalizado' => 'No finalizado'];
                    foreach ($arr as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo $d['estatus'] == $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Costo Estimado</label>
                <input type="number" step="0.01" min="0" name="costo_estimado" value="<?php echo $d['costo_estimado']; ?>" required>
            </div>
            <div class="form-group">
                <label>Costo Total</label>
                <input type="number" step="0.01" min="0" name="costo_total" value="<?php echo $d['costo_total']; ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Zona de falla (Hardware)</label>
                <select name="zona_falla_h" id="zona_falla_h" data-valor="<?php echo htmlspecialchars($d['zona_falla_h'], ENT_QUOTES); ?>">
                    <option value="">-- Seleccionar --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Zona de falla (Software)</label>
                <select name="zona_falla_s" id="zona_falla_s" data-valor="<?php echo htmlspecialchars($d['zona_falla_s'], ENT_QUOTES); ?>">
                    <option value="">-- Seleccionar --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Falla Reportada</label>
                <textarea name="falla_reportada" rows="3" required><?php echo htmlspecialchars($d['falla_reportada']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Diagnóstico</label>
                <textarea name="diagnostico" rows="3"><?php echo htmlspecialchars($d['diagnostico']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Trabajo Realizado</label>
                <textarea name="trabajo_realizado" rows="3"><?php echo htmlspecialchars($d['trabajo_realizado']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Observaciones</label>
                <textarea name="observaciones" rows="3"><?php echo htmlspecialchars($d['observaciones']); ?></textarea>
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
        initZonas('Id_equipo');
        const sel = document.getElementById('Id_equipo');
        const tipo = document.getElementById('tipo_equipo_rep');
        if (sel && tipo) {
            function mostrarTipo() {
                const opcion = sel.options[sel.selectedIndex];
                tipo.value = opcion && opcion.getAttribute('data-tipo') ? opcion.getAttribute('data-tipo') : '';
            }
            sel.addEventListener('change', mostrarTipo);
            mostrarTipo();
        }
    });
</script>
<?php
renderFooter();
$conn->close();
?>