<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();

$esAdmin = esAdmin();

// Filtros disponibles para todos los usuarios
$fDesde = isset($_GET['f_desde']) ? trim($_GET['f_desde']) : '';
$fHasta = isset($_GET['f_hasta']) ? trim($_GET['f_hasta']) : '';
$fTec = isset($_GET['f_tec']) ? (int)$_GET['f_tec'] : 0;

$where = [];
$params = [];
if ($fDesde !== '') {
    $where[] = "DATE(c.fecha) >= ?";
    $params[] = $fDesde;
}
if ($fHasta !== '') {
    $where[] = "DATE(c.fecha) <= ?";
    $params[] = $fHasta;
}
if ($fTec > 0) {
    $where[] = "EXISTS (SELECT 1 FROM equipo e2 INNER JOIN reparacion r2 ON r2.Id_equipo = e2.Id_equipo WHERE e2.Id_cliente = c.Id_cliente AND r2.Id_tecnico = ?)";
    $params[] = $fTec;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// Paginación básica
$porPagina = 15;
$pagina = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$offset = ($pagina - 1) * $porPagina;

$qTotal = $conn->prepare("SELECT COUNT(*) c FROM cliente c" . $whereSql);
if ($params) $qTotal->bind_param(str_repeat('s', count($params)), ...$params);
$qTotal->execute();
$total = $qTotal->get_result()->fetch_assoc()['c'];
$totalPaginas = max(1, ceil($total / $porPagina));

$sql = "SELECT c.*,
               (SELECT COUNT(*) FROM equipo e
                 INNER JOIN reparacion r ON r.Id_equipo = e.Id_equipo
                 WHERE e.Id_cliente = c.Id_cliente AND r.Id_tecnico = " . tecnicoIdActual() . ") AS n_designado
        FROM cliente c" . $whereSql . " ORDER BY c.Id_cliente DESC LIMIT $offset, $porPagina";
$q = $conn->prepare($sql);
if ($params) $q->bind_param(str_repeat('s', count($params)), ...$params);
$q->execute();
$result = $q->get_result();

// Lista de técnicos para el filtro
$tecs = $conn->query("SELECT Id_tecnico, nombre, apellido FROM tecnico ORDER BY nombre, apellido");

// Mantener filtros en la paginación
$qs = '';
if ($fDesde !== '') $qs .= '&f_desde=' . urlencode($fDesde);
if ($fHasta !== '') $qs .= '&f_hasta=' . urlencode($fHasta);
if ($fTec > 0) $qs .= '&f_tec=' . $fTec;

renderHeader('Clientes');
?>
<div class="page-header">
    <h1>Clientes</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="create.php" class="btn btn-success">+ Nuevo Cliente</a>
    </div>
</div>

<?php if (isset($_GET['msj'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msj']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="card">
    <form method="get" action="index.php" class="form-row" style="background:#f7f7f7;padding:12px;margin-bottom:0;border-radius:6px;">
        <div class="form-group">
            <label>Fecha desde</label>
            <input type="date" name="f_desde" value="<?php echo htmlspecialchars($fDesde); ?>">
        </div>
        <div class="form-group">
            <label>Fecha hasta</label>
            <input type="date" name="f_hasta" value="<?php echo htmlspecialchars($fHasta); ?>">
        </div>
        <div class="form-group">
            <label>Técnico</label>
            <select name="f_tec">
                <option value="0">Todos</option>
                <?php if ($tecs): while ($tec = $tecs->fetch_assoc()): ?>
                    <option value="<?php echo $tec['Id_tecnico']; ?>" <?php echo ($fTec === (int)$tec['Id_tecnico']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tec['nombre'] . ' ' . $tec['apellido']); ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
        </div>
        <div class="form-group" style="align-self:flex-end;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <?php if ($fDesde !== '' || $fHasta !== '' || $fTec > 0): ?>
                <a href="index.php" class="btn btn-secondary">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>
    <div class="table-responsive">
        <table class="data">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Fecha</th>
                    <th>Sucursal</th>
                    <th>RFC</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Id_cliente']; ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($row['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($row['correo']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($row['sucursal']); ?></td>
                            <td><?php echo htmlspecialchars($row['rfc']); ?></td>
                            <td class="actions">
                                <?php if ($esAdmin || $row['n_designado'] > 0): ?>
                                    <a href="edit.php?id=<?php echo $row['Id_cliente']; ?>" class="btn btn-warning btn-sm" title="<?php echo $esAdmin ? 'Editar cliente' : 'Editar (técnico designado)'; ?>">Editar</a>
                                    <?php if ($esAdmin): ?>
                                        <a href="../includes/delete.php?modulo=cliente&id=<?php echo $row['Id_cliente']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este cliente? Se eliminarán sus equipos y reparaciones asociados.');">Eliminar</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#888;font-size:12px;">Solo lectura</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;color:#888;">No hay clientes registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="pager">
        Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?> (<?php echo $total; ?> registros)
        <?php if ($pagina > 1): ?><a href="?pg=<?php echo $pagina - 1 . $qs; ?>">&laquo; Anterior</a><?php endif; ?>
        <?php if ($pagina < $totalPaginas): ?><a href="?pg=<?php echo $pagina + 1 . $qs; ?>">Siguiente &raquo;</a><?php endif; ?>
    </div>
</div>
<?php
renderFooter();
$conn->close();
?>
