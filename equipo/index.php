<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();

$esAdmin = esAdmin();

// Filtros disponibles para todos los usuarios (fecha = fecha de recepción de la reparación del equipo)
$fDesde = isset($_GET['f_desde']) ? trim($_GET['f_desde']) : '';
$fHasta = isset($_GET['f_hasta']) ? trim($_GET['f_hasta']) : '';
$fTec = isset($_GET['f_tec']) ? (int)$_GET['f_tec'] : 0;

$where = [];
$params = [];
if ($fDesde !== '') {
    $where[] = "EXISTS (SELECT 1 FROM reparacion r1 WHERE r1.Id_equipo = e.Id_equipo AND DATE(r1.fecha_recepcion) >= ?)";
    $params[] = $fDesde;
}
if ($fHasta !== '') {
    $where[] = "EXISTS (SELECT 1 FROM reparacion r2 WHERE r2.Id_equipo = e.Id_equipo AND DATE(r2.fecha_recepcion) <= ?)";
    $params[] = $fHasta;
}
if ($fTec > 0) {
    $where[] = "EXISTS (SELECT 1 FROM reparacion r3 WHERE r3.Id_equipo = e.Id_equipo AND r3.Id_tecnico = ?)";
    $params[] = $fTec;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$porPagina = 15;
$pagina = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$offset = ($pagina - 1) * $porPagina;

$qTotal = $conn->prepare("SELECT COUNT(*) c FROM equipo e" . $whereSql);
if ($params) $qTotal->bind_param(str_repeat('s', count($params)), ...$params);
$qTotal->execute();
$total = $qTotal->get_result()->fetch_assoc()['c'];
$totalPaginas = max(1, ceil($total / $porPagina));

$sql = "SELECT e.*, CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
               (SELECT COUNT(*) FROM reparacion r WHERE r.Id_equipo = e.Id_equipo AND r.Id_tecnico = " . tecnicoIdActual() . ") AS n_agendado
        FROM equipo e
        INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente" .
        $whereSql . " ORDER BY e.Id_equipo DESC LIMIT $offset, $porPagina";
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

renderHeader('Equipos');
?>
<div class="page-header">
    <h1>Equipos</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="create.php" class="btn btn-success">+ Nuevo Equipo</a>
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
            <label>Fecha de recepción desde</label>
            <input type="date" name="f_desde" value="<?php echo htmlspecialchars($fDesde); ?>">
        </div>
        <div class="form-group">
            <label>Fecha de recepción hasta</label>
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
                    <th>Cliente</th>
                    <th>Tipo Equipo</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Serie</th>
                    <th>Color</th>
                    <th>Accesorios</th>
                    <th>Estado Físico</th>
                    <th>Obs.</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Id_equipo']; ?></td>
                            <td><?php echo htmlspecialchars($row['cliente_nombre']); ?> <small style="color:#888;">(#<?php echo $row['Id_cliente']; ?>)</small></td>
                            <td><?php echo htmlspecialchars($row['tipo_equipo']); ?></td>
                            <td><?php echo htmlspecialchars($row['marca']); ?></td>
                            <td><?php echo htmlspecialchars($row['modelo']); ?></td>
                            <td><?php echo htmlspecialchars($row['serie']); ?></td>
                            <td><?php echo htmlspecialchars($row['color']); ?></td>
                            <td><?php echo htmlspecialchars($row['accesorios']); ?></td>
                            <td><?php echo htmlspecialchars($row['estado_fisico']); ?></td>
                            <td><?php echo htmlspecialchars($row['observaciones']); ?></td>
<td class="actions">
                                <?php if ($esAdmin || $row['n_agendado'] > 0): ?>
                                    <a href="edit.php?id=<?php echo $row['Id_equipo']; ?>" class="btn btn-warning btn-sm" title="<?php echo $esAdmin ? 'Editar equipo' : 'Editar (técnico agendado)'; ?>">Editar</a>
                                    <?php if ($esAdmin): ?>
                                        <a href="../includes/delete.php?modulo=equipo&id=<?php echo $row['Id_equipo']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este equipo? Se eliminarán sus reparaciones asociadas.');">Eliminar</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#888;font-size:12px;">Solo lectura</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11" style="text-align:center;color:#888;">No hay equipos registrados.</td></tr>
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