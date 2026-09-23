<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$esAdmin = esAdmin();

$porPagina = 20;
$pagina = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$offset = ($pagina - 1) * $porPagina;

// Filtro por reparación
$where = '';
if (isset($_GET['rep']) && $_GET['rep'] !== '') {
    $rep = (int)$_GET['rep'];
    $where = "f.Id_reparacion = $rep";
}

// El técnico solo ve las fotos de las reparaciones que tiene agendadas
$filtroTec = '';
if (!$esAdmin) {
    $filtroTec = 'r.Id_tecnico = ' . tecnicoIdActual();
}
if ($where !== '' && $filtroTec !== '') {
    $where = $where . ' AND ' . $filtroTec;
} elseif ($filtroTec !== '') {
    $where = $filtroTec;
}
$whereSql = $where !== '' ? "WHERE $where" : '';

$total = $conn->query("SELECT COUNT(*) c FROM fotos f INNER JOIN reparacion r ON r.Id_reparacion = f.Id_reparacion $whereSql")->fetch_assoc()['c'];
$totalPaginas = max(1, ceil($total / $porPagina));

$sql = "SELECT f.*, r.Id_equipo
        FROM fotos f
        INNER JOIN reparacion r ON r.Id_reparacion = f.Id_reparacion
        $whereSql
        ORDER BY f.tipo_foto, f.id_foto DESC LIMIT $offset, $porPagina";
$result = $conn->query($sql);

$reparaciones = $conn->query(
    "SELECT Id_reparacion FROM reparacion r "
    . (!$esAdmin ? "WHERE r.Id_tecnico = " . tecnicoIdActual() . ' ' : '')
    . "ORDER BY Id_reparacion DESC"
);

// Etiquetas de las 5 vistas del equipo
$vistas = [1 => 'Vista superior', 2 => 'Vista inferior', 3 => 'Vista lateral derecha', 4 => 'Vista lateral izquierda', 5 => 'Vista frontal'];

renderHeader('Fotos');
?>
<div class="page-header">
    <h1>Fotos</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="create.php" class="btn btn-success">+ Nueva Foto</a>
    </div>
</div>

<?php if (isset($_GET['msj'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msj']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="card">
    <form method="GET" action="index.php" style="margin-bottom:15px;">
        <div class="form-row">
            <div class="form-group">
                <label>Filtrar por reparación</label>
                <select name="rep" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <?php while ($r = $reparaciones->fetch_assoc()): ?>
                        <option value="<?php echo $r['Id_reparacion']; ?>" <?php echo (isset($_GET['rep']) && $_GET['rep'] == $r['Id_reparacion']) ? 'selected' : ''; ?>>
                            Reparación #<?php echo $r['Id_reparacion']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </form>
    <div class="table-responsive">
        <table class="data">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Reparación</th>
                    <th>Vista</th>
                    <th>Imagen</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_foto']; ?></td>
                            <td>Rep. #<?php echo $row['Id_reparacion']; ?></td>
                            <td><?php echo isset($vistas[(int)$row['tipo_foto']]) ? $vistas[(int)$row['tipo_foto']] : 'Tipo ' . $row['tipo_foto']; ?></td>
                            <td>
                                <a href="<?php echo BASE_URL . htmlspecialchars($row['ruta_imagen']); ?>" target="_blank">
                                    <img src="<?php echo BASE_URL . htmlspecialchars($row['ruta_imagen']); ?>" class="thumbnail" alt="Foto">
                                </a>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                            <td class="actions">
                                <a href="edit.php?id=<?php echo $row['id_foto']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                <?php if ($esAdmin): ?>
                                    <a href="../includes/delete.php?modulo=fotos&id=<?php echo $row['id_foto']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta foto? Se borrará el archivo de imagen.');">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#888;">No hay fotos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="pager">
        Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?> (<?php echo $total; ?> registros)
        <?php if ($pagina > 1): ?><a href="?pg=<?php echo $pagina - 1; ?><?php echo isset($_GET['rep']) ? '&rep=' . urlencode($_GET['rep']) : ''; ?>">&laquo; Anterior</a><?php endif; ?>
        <?php if ($pagina < $totalPaginas): ?><a href="?pg=<?php echo $pagina + 1; ?><?php echo isset($_GET['rep']) ? '&rep=' . urlencode($_GET['rep']) : ''; ?>">Siguiente &raquo;</a><?php endif; ?>
    </div>
</div>
<?php
renderFooter();
$conn->close();
?>