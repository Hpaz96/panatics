<?php
// =============================================
//  DELETE.PHP - Eliminación genérica por módulo
// =============================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

requireLogin();
requiereAdmin();

$conn = conectarBD();
$modulo = $_GET['modulo'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$destino = BASE_URL . 'index.php';

function eliminar($table, $idField, $id, $ruta, $conn) {
    global $destino;
    if ($id <= 0) { header('Location: ' . BASE_URL . $ruta . '?err=' . urlencode('ID inválido.')); exit; }
    $sql = "DELETE FROM $table WHERE $idField = $id";
    $ok = $conn->query($sql);
    if ($ok) {
        registrarBitacora($conn, $table, 'eliminar', $id, 'Registro #' . $id . ' de ' . $table);
    }
    $tipo = $ok ? 'msj' : 'err';
    $mensaje = $ok ? 'Registro eliminado correctamente.' : 'Error: ' . $conn->error;
    header('Location: ' . BASE_URL . $ruta . '?' . $tipo . '=' . urlencode($mensaje));
    exit;
}

switch ($modulo) {
    case 'cliente':    eliminar('cliente', 'Id_cliente', $id, 'cliente/index.php', $conn); break;
    case 'tecnico':    eliminar('tecnico', 'Id_tecnico', $id, 'tecnico/index.php', $conn); break;
    case 'equipo':     eliminar('equipo', 'Id_equipo', $id, 'equipo/index.php', $conn); break;
    case 'reparacion': eliminar('reparacion', 'Id_reparacion', $id, 'reparacion/index.php', $conn); break;
    case 'pago':       eliminar('pago', 'Id_pago', $id, 'pago/index.php', $conn); break;
    case 'fotos':
        // Borrar también el archivo físico si existe
        $res = $conn->query("SELECT ruta_imagen FROM fotos WHERE id_foto = $id");
        if ($res && $res->num_rows) {
            $fila = $res->fetch_assoc();
            $archivo = dirname(__DIR__) . '/' . $fila['ruta_imagen'];
            if (!empty($fila['ruta_imagen']) && file_exists($archivo)) {
                @unlink($archivo);
            }
        }
        eliminar('fotos', 'id_foto', $id, 'fotos/index.php', $conn);
        break;
    case 'garantia':
        eliminar('garantias', 'Id_reparacion', $id, 'garantias/index.php', $conn);
        break;
    default:
        header('Location: ' . BASE_URL . 'index.php');
        exit;
}
$conn->close();
?>
