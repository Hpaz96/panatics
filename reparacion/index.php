<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();

$porPagina = 15;
$pagina = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$offset = ($pagina - 1) * $porPagina;

$esAdmin = esAdmin();
$tecnicoId = tecnicoIdActual();

$where = '';
if (!$esAdmin) {
    $where = "WHERE r.Id_tecnico = $tecnicoId";
}
if (isset($_GET['filtro'])) {
    $filtro = $conn->real_escape_string($_GET['filtro']);
    if ($filtro !== '') {
        $where = ($where === '') ? "WHERE r.estatus = '$filtro'" : $where . " AND r.estatus = '$filtro'";
    }
}

$sqlCount = "SELECT COUNT(*) c FROM reparacion r $where";
$total = $conn->query($sqlCount)->fetch_assoc()['c'];
$totalPaginas = max(1, ceil($total / $porPagina));

$sql = "SELECT r.*, g.fecha_entrega AS entrega_g,
               (SELECT COUNT(*) FROM fotos f WHERE f.Id_reparacion = r.Id_reparacion) AS n_fotos,
               (SELECT COUNT(*) FROM pago p WHERE p.Id_reparacion = r.Id_reparacion) AS n_pagos,
               CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
               e.tipo_equipo, e.marca, e.modelo,
               CONCAT(t.nombre, ' ', t.apellido) AS tecnico_nombre
        FROM reparacion r
        INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
        INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
        INNER JOIN tecnico t ON t.Id_tecnico = r.Id_tecnico
        LEFT JOIN garantias g ON g.Id_reparacion = r.Id_reparacion
        $where
        ORDER BY r.Id_reparacion DESC LIMIT $offset, $porPagina";
$result = $conn->query($sql);

renderHeader('Reparaciones');
?>
<div class="page-header">
    <h1>Reparaciones</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="create.php" class="btn btn-success">+ Nueva Reparación</a>
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
                <label>Filtrar por estatus</label>
                <select name="filtro" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="pendiente" <?php echo (isset($_GET['filtro']) && $_GET['filtro'] == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="en reparación" <?php echo (isset($_GET['filtro']) && $_GET['filtro'] == 'en reparación') ? 'selected' : ''; ?>>En reparación</option>
                    <option value="reparado" <?php echo (isset($_GET['filtro']) && $_GET['filtro'] == 'reparado') ? 'selected' : ''; ?>>Reparado</option>
                    <option value="entregado" <?php echo (isset($_GET['filtro']) && $_GET['filtro'] == 'entregado') ? 'selected' : ''; ?>>Entregado</option>
                    <option value="no finalizado" <?php echo (isset($_GET['filtro']) && $_GET['filtro'] == 'no finalizado') ? 'selected' : ''; ?>>No finalizado</option>
                </select>
            </div>
        </div>
    </form>
    <div class="table-responsive">
        <table class="data">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Equipo</th>
                    <th>Técnico</th>
                    <th>Recepción</th>
                    <th>Entrega</th>
                    <th>Falla</th>
                    <th>Estatus</th>
                    <th>Costo Est.</th>
                    <th>Costo Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                            $badge = '';
                            switch ($row['estatus']) {
                                case 'pendiente': $badge = '#f9a825'; break;
                                case 'en reparación': $badge = '#1a73e8'; break;
                                case 'reparado': $badge = '#188038'; break;
                                case 'entregado': $badge = '#0d47a1'; break;
                                default: $badge = '#d93025';
                            }
                        ?>
                        <tr>
                            <td><?php echo $row['Id_reparacion']; ?></td>
                            <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['tipo_equipo'] . ' - ' . $row['marca'] . ' ' . $row['modelo']); ?></td>
                            <td><?php echo htmlspecialchars($row['tecnico_nombre']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_recepcion'])); ?></td>
                            <td><?php echo ($row['fecha_entrega']) ? date('d/m/Y H:i', strtotime($row['fecha_entrega'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars(mb_strimwidth($row['falla_reportada'], 0, 40, '...')); ?></td>
                            <td><span style="background:<?php echo $badge; ?>;color:#fff;padding:3px 10px;border-radius:12px;font-size:12px;"><?php echo htmlspecialchars($row['estatus']); ?></span></td>
                            <td>$<?php echo number_format((float)$row['costo_estimado'], 2); ?></td>
                            <td>$<?php echo number_format($row['costo_total'], 2); ?></td>
                            <td class="actions">
                                <a href="edit.php?id=<?php echo $row['Id_reparacion']; ?>" class="btn btn-warning btn-sm" title="Editar">Editar</a>
                                <?php if (in_array($row['estatus'], ['reparado', 'entregado'])): ?>
                                    <?php if ($row['n_fotos'] > 0 && $row['n_pagos'] > 0): ?>
                                        <a href="pdf.php?id=<?php echo $row['Id_reparacion']; ?>" class="btn btn-danger btn-sm" title="Descargar PDF" target="_blank">PDF</a>
                                    <?php else: ?>
                                        <span style="color:#999;font-size:11px;" title="Proceso incompleto. Faltan: <?php echo trim(($row['n_fotos'] < 1 ? 'Fotos' : '') . ' ' . ($row['n_pagos'] < 1 ? 'Pagos' : '')); ?>">PDF</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <a href="../pago/create.php?rep=<?php echo $row['Id_reparacion']; ?>" class="btn btn-success btn-sm" title="Registrar pago">+ Pago</a>
                                <?php if ($esAdmin && $row['estatus'] === 'entregado'): ?>
                                    <?php if ($row['entrega_g']): ?>
                                        <a href="../garantias/pdf.php?id=<?php echo $row['Id_reparacion']; ?>" class="btn btn-primary btn-sm" target="_blank" title="Descargar PDF de garantía (60 días)">PDF Garantía</a>
                                        <a href="../garantias/edit.php?id=<?php echo $row['Id_reparacion']; ?>" class="btn btn-info btn-sm" title="Editar garantía">Garantía</a>
                                    <?php else: ?>
                                        <a href="../garantias/create.php?rep=<?php echo $row['Id_reparacion']; ?>" class="btn btn-primary btn-sm" title="Generar garantía (60 días)">Garantía</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($esAdmin): ?>
                                    <a href="../fotos/create.php?rep=<?php echo $row['Id_reparacion']; ?>" class="btn btn-secondary btn-sm" title="Subir foto">Foto</a>
                                    <a href="../includes/delete.php?modulo=reparacion&id=<?php echo $row['Id_reparacion']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta reparación? Sus pagos y fotos asociados también se eliminarán.');">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11" style="text-align:center;color:#888;">No hay reparaciones.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="pager">
        Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?> (<?php echo $total; ?> registros)
        <?php if ($pagina > 1): ?><a href="?pg=<?php echo $pagina - 1; ?><?php echo isset($_GET['filtro']) ? '&filtro=' . urlencode($_GET['filtro']) : ''; ?>">&laquo; Anterior</a><?php endif; ?>
        <?php if ($pagina < $totalPaginas): ?><a href="?pg=<?php echo $pagina + 1; ?><?php echo isset($_GET['filtro']) ? '&filtro=' . urlencode($_GET['filtro']) : ''; ?>">Siguiente &raquo;</a><?php endif; ?>
    </div>
</div>
<?php
renderFooter();
$conn->close();
?>