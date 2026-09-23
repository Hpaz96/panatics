<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();
$esAdmin = esAdmin();

$porPagina = 15;
$pagina = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$offset = ($pagina - 1) * $porPagina;

// Los técnicos solo ven los pagos de sus reparaciones agendadas
$filtro = $esAdmin ? '' : 'WHERE r.Id_tecnico = ' . tecnicoIdActual();

$total = $conn->query("SELECT COUNT(*) c FROM pago p INNER JOIN reparacion r ON r.Id_reparacion = p.Id_reparacion $filtro")->fetch_assoc()['c'];
$totalPaginas = max(1, ceil($total / $porPagina));

$sql = "SELECT p.*,
               CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
               e.tipo_equipo, e.marca, e.modelo
        FROM pago p
        INNER JOIN reparacion r ON r.Id_reparacion = p.Id_reparacion
        INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
        INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
        $filtro
        ORDER BY p.Id_pago DESC LIMIT $offset, $porPagina";
$result = $conn->query($sql);

renderHeader('Pagos');
?>
<div class="page-header">
    <h1>Pagos</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <?php if ($esAdmin): ?>
            <a href="create.php" class="btn btn-success">+ Nuevo Pago</a>
        <?php else: ?>
            <a href="../reparacion/index.php" class="btn btn-secondary">&laquo; Reparaciones</a>
        <?php endif; ?>
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
                    <th>Reparación</th>
                    <th>Cliente</th>
                    <th>Equipo</th>
                    <th>Tipo de Pago</th>
                    <th>Monto</th>
                    <th>Anticipo</th>
                    <th>Saldo Pendiente</th>
                    <th>Referencia</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Id_pago']; ?></td>
                            <td>#<?php echo $row['Id_reparacion']; ?></td>
                            <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['tipo_equipo'] . ' ' . $row['marca'] . ' ' . $row['modelo']); ?></td>
                            <td><?php echo htmlspecialchars($row['tipo_pago']); ?></td>
                            <td>$<?php echo number_format($row['monto'], 2); ?></td>
                            <td>$<?php echo number_format($row['anticipo'], 2); ?></td>
                            <td><strong>$<?php echo number_format($row['saldo_pendiente'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['referencia']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_pago'])); ?></td>
                            <td class="actions">
                                <a href="pdf.php?id=<?php echo $row['Id_pago']; ?>" class="btn btn-info btn-sm" title="Descargar PDF" target="_blank">PDF</a>
                                <a href="edit.php?id=<?php echo $row['Id_pago']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="liquidar.php?id=<?php echo $row['Id_pago']; ?>" class="btn btn-success btn-sm" title="Liquidar: saldo pendiente a 0, estatus a 'entregado' y genera el PDF de garantía" onclick="return confirm('¿Liquidar este pago? Se pondrá el saldo pendiente en 0, el equipo cambiará a estatus \'entregado\' y se generará el PDF de garantía.');">Liquidar</a>
                                <?php if ($esAdmin): ?>
                                    <a href="../includes/delete.php?modulo=pago&id=<?php echo $row['Id_pago']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este pago?');">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11" style="text-align:center;color:#888;">No hay pagos registrados.</td></tr>
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