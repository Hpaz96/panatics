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
    $where[] = "DATE(g.fecha_entrega) >= ?";
    $params[] = $fDesde;
}
if ($fHasta !== '') {
    $where[] = "DATE(g.fecha_entrega) <= ?";
    $params[] = $fHasta;
}
if ($fTec > 0) {
    $where[] = "g.Id_tecnico = ?";
    $params[] = $fTec;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// Paginación básica
$porPagina = 15;
$pagina = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$offset = ($pagina - 1) * $porPagina;

$qTotal = $conn->prepare("SELECT COUNT(*) c FROM garantias g" . $whereSql);
if ($params) $qTotal->bind_param(str_repeat('s', count($params)), ...$params);
$qTotal->execute();
$total = $qTotal->get_result()->fetch_assoc()['c'];
$totalPaginas = max(1, ceil($total / $porPagina));

$sql = "SELECT g.*,
               r.Id_equipo, r.costo_total, r.estatus,
               CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
               e.tipo_equipo, e.marca, e.modelo,
               CONCAT(t.nombre, ' ', t.apellido) AS tecnico_nombre
        FROM garantias g
        INNER JOIN reparacion r ON r.Id_reparacion = g.Id_reparacion
        INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
        INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
        INNER JOIN tecnico t ON t.Id_tecnico = g.Id_tecnico" .
        $whereSql . " ORDER BY g.Id_reparacion DESC LIMIT $offset, $porPagina";
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

renderHeader('Garantías');
?>
<div class="page-header">
    <h1>Garantías (60 días naturales)</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="create.php" class="btn btn-success">+ Nueva Garantía</a>
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
                    <th>Rep.</th>
                    <th>Cliente</th>
                    <th>Equipo</th>
                    <th>Técnico</th>
                    <th>Inicio de Garantía</th>
                    <th>Vence Garantía</th>
                    <th>Motivo</th>
                    <th>Zona (H/S)</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['Id_reparacion']; ?></td>
                            <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['tipo_equipo'] . ' - ' . $row['marca'] . ' ' . $row['modelo']); ?></td>
                            <td><?php echo htmlspecialchars($row['tecnico_nombre']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_entrega'])); ?></td>
                            <td><strong><?php echo date('d/m/Y H:i', strtotime($row['fecha_termino_garantia'])); ?></strong></td>
                            <td><?php echo htmlspecialchars(mb_strimwidth($row['motivo_garantia'], 0, 35, '...')); ?></td>
                            <td><?php echo htmlspecialchars($row['zona_falla_h'] . ' / ' . $row['zona_falla_s']); ?></td>
                            <td class="actions">
                                <a href="pdf.php?id=<?php echo $row['Id_reparacion']; ?>" class="btn btn-danger btn-sm" title="Descargar PDF de garantía (60 días naturales)" target="_blank">PDF</a>
                                <?php if ($esAdmin || $row['Id_tecnico'] === tecnicoIdActual()): ?>
                                    <a href="edit.php?id=<?php echo $row['Id_reparacion']; ?>" class="btn btn-warning btn-sm" title="<?php echo $esAdmin ? 'Editar garantía' : 'Editar (tu garantía)'; ?>">Editar</a>
                                <?php endif; ?>
                                <?php if ($esAdmin): ?>
                                    <a href="../includes/delete.php?modulo=garantia&id=<?php echo $row['Id_reparacion']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta garantía?');">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;color:#888;">No hay garantías registradas.</td></tr>
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