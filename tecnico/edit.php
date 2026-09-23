<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();

requiereAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$res = $conn->query("SELECT * FROM tecnico WHERE Id_tecnico = $id");
if ($res->num_rows == 0) {
    header('Location: index.php?err=' . urlencode('Técnico no encontrado.'));
    exit;
}
$d = $res->fetch_assoc();

renderHeader('Editar Técnico');
?>
<div class="page-header">
    <h1>Editar Técnico #<?php echo $d['Id_tecnico']; ?></h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<div class="card">
    <form action="../includes/save.php" method="POST">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="modulo" value="tecnico">
        <input type="hidden" name="id" value="<?php echo $d['Id_tecnico']; ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" maxlength="30" value="<?php echo htmlspecialchars($d['nombre']); ?>" required>
            </div>
            <div class="form-group">
                <label>Apellido</label>
                <input type="text" name="apellido" maxlength="30" value="<?php echo htmlspecialchars($d['apellido']); ?>" required>
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" maxlength="15" value="<?php echo htmlspecialchars($d['telefono']); ?>" required>
            </div>
            <div class="form-group">
                <label>Correo</label>
                <input type="email" name="correo" maxlength="30" value="<?php echo htmlspecialchars($d['correo']); ?>" required>
            </div>
            <div class="form-group">
                <label>Puesto</label>
                <input type="text" name="puesto" maxlength="30" value="<?php echo htmlspecialchars($d['puesto']); ?>" required>
            </div>
            <div class="form-group">
                <label>Zona</label>
                <select name="zona" required>
                    <option value="San Andres" <?php echo $d['zona'] == 'San Andres' ? 'selected' : ''; ?>>San Andrés</option>
                    <option value="Tlazala" <?php echo $d['zona'] == 'Tlazala' ? 'selected' : ''; ?>>Tlazala</option>
                </select>
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select name="role" required>
                    <option value="tecnico" <?php echo $d['role'] == 'tecnico' ? 'selected' : ''; ?>>Técnico</option>
                    <option value="admin" <?php echo $d['role'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                </select>
                <small style="color:#666;">El administrador ve todo y gestiona técnicos; el técnico solo ve sus reparaciones.</small>
            </div>
            <div class="form-group">
                <label>Usuario de acceso</label>
                <input type="text" name="User" maxlength="30" value="<?php echo htmlspecialchars($d['User']); ?>">
                <small style="color:#666;">Se usa para iniciar sesión en el sistema.</small>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="text" name="pass" maxlength="30" value="<?php echo htmlspecialchars($d['pass']); ?>">
                <small style="color:#666;">Contraseña de inicio de sesión.</small>
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