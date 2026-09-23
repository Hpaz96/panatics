<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$datos = [
    'nombre' => '', 'apellido' => '', 'telefono' => '', 'correo' => '',
    'fecha' => date('Y-m-d\TH:i'), 'sucursal' => 'san andres', 'rfc' => ''
];

renderHeader('Nuevo Cliente');
?>
<div class="page-header">
    <h1>Nuevo Cliente</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<?php renderStepper('cliente'); ?>

<?php if (isset($_GET['msj'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msj']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="card">
    <form action="../includes/save.php" method="POST" id="form_cliente">
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="modulo" value="cliente">
        <div class="form-row">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" maxlength="40" required>
            </div>
            <div class="form-group">
                <label>Apellido</label>
                <input type="text" name="apellido" maxlength="50" required>
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" maxlength="15" required>
            </div>
            <div class="form-group">
                <label>Correo</label>
                <input type="email" name="correo" maxlength="30" required>
            </div>
            <div class="form-group">
                <label>Fecha</label>
                <input type="datetime-local" name="fecha" value="<?php echo $datos['fecha']; ?>" required>
            </div>
            <div class="form-group">
                <label>Sucursal</label>
                <select name="sucursal" required>
                    <option value="san andres">San Andrés</option>
                    <option value="tlazala">Tlazala</option>
                </select>
            </div>
            <div class="form-group">
                <label>RFC</label>
                <input type="text" name="rfc" maxlength="30">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" name="proceso" value="1" id="btnSiguiente" class="btn btn-success">Siguiente &rarr; Equipo</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<script src="<?php echo BASE_URL; ?>assets/js/funciones.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () { initSiguiente('form_cliente'); });
</script>
<?php
renderFooter();
$conn->close();
?>
