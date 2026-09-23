<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();

requiereAdmin();

$porPagina = 15;
$pagina = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$offset = ($pagina - 1) * $porPagina;

$total = $conn->query("SELECT COUNT(*) c FROM tecnico")->fetch_assoc()['c'];
$totalPaginas = max(1, ceil($total / $porPagina));
$result = $conn->query("SELECT t.*,
        (SELECT MAX(b.fecha) FROM bitacora b WHERE b.modulo = 'tecnico' AND b.id_registro = t.Id_tecnico) AS ult_mod
        FROM tecnico t
        ORDER BY t.Id_tecnico DESC LIMIT $offset, $porPagina");

renderHeader('Técnicos');
?>
<div class="page-header">
    <h1>Técnicos</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="create.php" class="btn btn-success">+ Nuevo Técnico</a>
    </div>
</div>

<?php if (isset($_GET['msj'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msj']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="data">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Puesto</th>
                    <th>Zona</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Últ. modificación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Id_tecnico']; ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($row['correo']); ?></td>
                            <td><?php echo htmlspecialchars($row['puesto']); ?></td>
                            <td><?php echo htmlspecialchars($row['zona']); ?></td>
                            <td><?php echo htmlspecialchars($row['User']); ?></td>
                            <td>
                                <?php if ($row['role'] === 'admin'): ?>
                                    <span style="background:#188038;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">Administrador</span>
                                <?php else: ?>
                                    <span style="background:#1a73e8;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">Técnico</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['ult_mod'] ? date('d/m/Y H:i', strtotime($row['ult_mod'])) : '-'; ?></td>
                            <td class="actions">
                                <a href="edit.php?id=<?php echo $row['Id_tecnico']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="../includes/delete.php?modulo=tecnico&id=<?php echo $row['Id_tecnico']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este técnico?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11" style="text-align:center;color:#888;">No hay técnicos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="pager">
        Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?> (<?php echo $total; ?> registros)
        <?php if ($pagina > 1): ?><a href="?pg=<?php echo $pagina - 1; ?>">&laquo; Anterior</a><?php endif; ?>
        <?php if ($pagina < $totalPaginas): ?><a href="?pg=<?php echo $pagina + 1; ?>">Siguiente &raquo;</a><?php endif; ?>
    </div>
</div>
<?php
renderFooter();
$conn->close();
?>