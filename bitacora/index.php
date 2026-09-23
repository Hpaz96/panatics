<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();

requiereAdmin();

$porPagina = 30;
$pagina = isset($_GET['pg']) ? max(1, (int)$_GET['pg']) : 1;
$offset = ($pagina - 1) * $porPagina;

$total = $conn->query("SELECT COUNT(*) c FROM bitacora")->fetch_assoc()['c'];
$totalPaginas = max(1, ceil($total / $porPagina));

$result = $conn->query("SELECT * FROM bitacora ORDER BY fecha DESC, id DESC LIMIT $offset, $porPagina");

renderHeader('Bitácora de modificaciones');
?>
<div class="page-header">
    <h1>Bitácora de modificaciones</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
        <a href="../index.php" class="btn btn-secondary">&laquo; Volver</a>
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
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Módulo</th>
                    <th>Acción</th>
                    <th>Registro</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($row['usuario']); ?></td>
                            <td>
                                <?php
                                $color = '#1a73e8';
                                switch ($row['modulo']) {
                                    case 'tecnico':  $color = '#1a73e8'; break;
                                    case 'reparacion': $color = '#188038'; break;
                                    case 'cliente':  $color = '#007bff'; break;
                                    case 'equipo':   $color = '#ff9800'; break;
                                    case 'pago':     $color = '#9c27b0'; break;
                                    case 'fotos':    $color = '#795548'; break;
                                }
                                ?>
                                <span style="background:<?php echo $color; ?>;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">
                                    <?php echo htmlspecialchars($row['modulo']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['accion']); ?></td>
                            <td>#<?php echo $row['id_registro']; ?></td>
                            <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;color:#888;">Sin registros de modificaciones.</td></tr>
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
