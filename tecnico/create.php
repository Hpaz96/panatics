<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

requiereAdmin();

renderHeader('Nuevo Técnico');
?>
<div class="page-header">
    <h1>Nuevo Técnico</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="index.php" class="btn btn-secondary">&laquo; Volver</a>
    </div>
</div>

<div class="card">
    <form action="../includes/save.php" method="POST">
        <input type="hidden" name="accion" value="crear">
        <input type="hidden" name="modulo" value="tecnico">
        <div class="form-row">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" maxlength="30" required>
            </div>
            <div class="form-group">
                <label>Apellido</label>
                <input type="text" name="apellido" maxlength="30" required>
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
                <label>Puesto</label>
                <input type="text" name="puesto" maxlength="30" required>
            </div>
            <div class="form-group">
                <label>Zona</label>
                <select name="zona" required>
                    <option value="San Andres">San Andrés</option>
                    <option value="Tlazala">Tlazala</option>
                </select>
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select name="role" required>
                    <option value="tecnico">Técnico</option>
                    <option value="admin">Administrador</option>
                </select>
                <small style="color:#666;">El administrador ve todo y gestiona técnicos; el técnico solo ve sus reparaciones.</small>
            </div>
            <div class="form-group">
                <label>Usuario de acceso</label>
                <input type="text" name="User" maxlength="30">
                <small style="color:#666;">Se usa para iniciar sesión en el sistema.</small>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="text" name="pass" maxlength="30">
                <small style="color:#666;">Contraseña de inicio de sesión.</small>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<?php
renderFooter();
?>