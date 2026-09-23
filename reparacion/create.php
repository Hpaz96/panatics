<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$esAdmin = esAdmin();
$equipoPre = isset($_GET['equipo']) ? (int)$_GET['equipo'] : 0;

function catZona($tipoEquipo) {
    $t = strtolower($tipoEquipo);
    if (strpos($t, 'escritorio') !== false || strpos($t, 'laptop') !== false || strpos($t, 'todo en uno') !== false) return 'pc';
    if (strpos($t, 'control') !== false) return 'controles';
    if (strpos($t, 'videojuegos') !== false || strpos($t, 'consola') !== false) return 'videojuegos';
    return 'otro';
}

$equipos = $conn->query(
    "SELECT e.Id_equipo, e.marca, e.modelo, e.tipo_equipo, e.serie,
            CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre
     FROM equipo e
     INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
     ORDER BY e.marca"
);
$tecnicos = $conn->query("SELECT Id_tecnico, nombre, apellido FROM tecnico ORDER BY nombre");

renderHeader('Nueva Reparación');
?>
<div class="page-header">
    <h1>Nueva Reparación</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<?php renderStepper('reparacion'); ?>

<?php if (isset($_GET['msj'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msj']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="card">
    <form action="../includes/save.php" method="POST" id="form_reparacion">
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="modulo" value="reparacion">
        <div class="form-row">
            <div class="form-group">
                <label>Equipo</label>
                <select name="Id_equipo" id="Id_equipo" required>
                    <option value="">-- Seleccionar equipo --</option>
                    <?php while ($e = $equipos->fetch_assoc()): ?>
                        <option value="<?php echo $e['Id_equipo']; ?>" data-zona="<?php echo catZona($e['tipo_equipo']); ?>" data-tipo="<?php echo htmlspecialchars($e['tipo_equipo'], ENT_QUOTES); ?>" <?php echo $e['Id_equipo'] == $equipoPre ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($e['marca'] . ' ' . $e['modelo'] . ' (' . $e['tipo_equipo'] . ') - ' . $e['cliente_nombre'] . ' (#' . $e['Id_equipo'] . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <?php if ($equipos->num_rows == 0): ?>
                    <small style="color:#c5221f;">No hay equipos. <a href="../equipo/create.php">Crear equipo</a></small>
                <?php endif; ?>
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
                            <option value="<?php echo $t['Id_tecnico']; ?>">
                                <?php echo htmlspecialchars($t['nombre'] . ' ' . $t['apellido'] . ' (#' . $t['Id_tecnico'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if ($tecnicos->num_rows == 0): ?>
                        <small style="color:#c5221f;">No hay técnicos. <a href="../tecnico/create.php">Crear técnico</a></small>
                    <?php endif; ?>
                <?php else: ?>
                    <input type="hidden" name="Id_tecnico" value="<?php echo tecnicoIdActual(); ?>">
                    <input type="text" value="<?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Tú'); ?>" disabled>
                    <small style="color:#666;">La reparación quedará asignada a ti.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Fecha de Recepción</label>
                <input type="datetime-local" name="fecha_recepcion" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
            </div>
            <div class="form-group">
                <label>Estatus</label>
                <select name="estatus" required>
                    <option value="pendiente">Pendiente</option>
                    <option value="en reparación">En reparación</option>
                    <option value="reparado">Reparado</option>
                    <option value="entregado">Entregado</option>
                    <option value="no finalizado">No finalizado</option>
                </select>
            </div>
            <div class="form-group">
                <label>Costo Estimado</label>
                <input type="number" step="0.01" min="0" name="costo_estimado" value="0.00" required>
            </div>
            <div class="form-group">
                <label>Costo Total</label>
                <input type="number" step="0.01" min="0" name="costo_total" value="0.00" required>
            </div>
        </div>

        <div class="form-row">
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
            <div class="form-group">
                <label>Falla Reportada</label>
                <textarea name="falla_reportada" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label>Diagnóstico</label>
                <textarea name="diagnostico" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Trabajo Realizado</label>
                <textarea name="trabajo_realizado" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Observaciones</label>
                <textarea name="observaciones" rows="3"></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" name="proceso" value="1" id="btnSiguiente" class="btn btn-success">Siguiente &rarr; Fotos</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<script src="<?php echo BASE_URL; ?>assets/js/zonas.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/funciones.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initZonas('Id_equipo');
        initSiguiente('form_reparacion');
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