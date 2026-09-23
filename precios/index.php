<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/templates.php';

$conn = conectarBD();

$where = '';
$filtros = '';
$cat = isset($_GET['categoria']) ? $conn->real_escape_string($_GET['categoria']) : '';
$tipo = isset($_GET['tipo']) ? $conn->real_escape_string($_GET['tipo']) : '';
$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

if ($cat !== '') { $where .= " WHERE categoria = '$cat'"; }
if ($tipo !== '') { $where .= ($where === '') ? " WHERE tipo = '$tipo'" : " AND tipo = '$tipo'"; }
if ($q !== '') { $where .= ($where === '') ? " WHERE servicio LIKE '%$q%'" : " AND servicio LIKE '%$q%'"; }
if ($where === '') { $where .= " ORDER BY categoria, tipo, servicio"; }

$sql = "SELECT servicio, mano_de_obra, categoria, tipo FROM precios$where";
$result = $conn->query($sql);

renderHeader('Precios');
?>
<div class="page-header">
    <h1>Precios</h1>
    <div style="display:flex;gap:8px;">
        <a href="../index.php" class="btn btn-secondary">Volver al panel</a>
    </div>
</div>

<div class="card">
    <form method="GET" action="index.php" style="margin-bottom:15px;">
        <div class="form-row">
            <div class="form-group">
                <label>Categoría</label>
                <select name="categoria" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <option value="Hardware" <?php echo $cat === 'Hardware' ? 'selected' : ''; ?>>Hardware</option>
                    <option value="Software" <?php echo $cat === 'Software' ? 'selected' : ''; ?>>Software</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="pc" <?php echo $tipo === 'pc' ? 'selected' : ''; ?>>PC (Escritorio / Todo en uno / Laptop)</option>
                    <option value="videojuegos" <?php echo $tipo === 'videojuegos' ? 'selected' : ''; ?>>Videojuegos</option>
                    <option value="controles" <?php echo $tipo === 'controles' ? 'selected' : ''; ?>>Controles</option>
                    <option value="otro" <?php echo $tipo === 'otro' ? 'selected' : ''; ?>>Otro</option>
                </select>
            </div>
            <div class="form-group" style="flex:1 1 220px;">
                <label>Buscar servicio</label>
                <div style="display:flex;gap:6px;">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ej. pantalla, formateo...">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <?php if ($cat !== '' || $tipo !== '' || $q !== ''): ?>
                        <a href="index.php" class="btn btn-secondary">Limpiar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
    <div class="table-responsive">
        <table class="data">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Mano de Obra</th>
                    <th>Categoría</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['servicio']); ?></td>
                            <td>$<?php echo number_format((float)$row['mano_de_obra'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                            <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;color:#888;">No hay precios que coincidan con el filtro.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p style="color:#666;margin-top:10px;"><strong><?php echo $result->num_rows; ?></strong> servicios encontrados. Lista informativa; los precios pueden variar según el diagnóstico final.</p>
</div>
<?php
renderFooter();
$conn->close();
?>