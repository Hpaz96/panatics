<?php
// =============================================
//  LIQUIDAR PAGO - Reduce saldo_pendiente a 0,
//  cambia el estatus de la reparación a
//  "entregado", registra la fecha de entrega,
//  crea la garantía (si no existe) y genera el
//  PDF de garantía automáticamente.
// =============================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = conectarBD();
$esAdmin = esAdmin();
$pid = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pid <= 0) {
    header('Location: index.php?err=' . urlencode('Pago no válido.'));
    exit;
}

$res = $conn->query(
    "SELECT p.Id_pago, p.Id_reparacion, r.Id_tecnico, r.estatus, r.fecha_entrega
     FROM pago p
     INNER JOIN reparacion r ON r.Id_reparacion = p.Id_reparacion
     WHERE p.Id_pago = $pid"
);
if ($res->num_rows == 0) {
    header('Location: index.php?err=' . urlencode('Pago no encontrado.'));
    exit;
}
$d = $res->fetch_assoc();
$repId = (int)$d['Id_reparacion'];
$tecId = (int)$d['Id_tecnico'];

// Permisos: administrador o técnico agendado de la reparación del pago
if (!$esAdmin && $tecId !== tecnicoIdActual()) {
    header('Location: index.php?err=' . urlencode('Solo el técnico agendado puede liquidar este pago.'));
    exit;
}

// 1) Reducir saldo pendiente a 0
$conn->query("UPDATE pago SET saldo_pendiente = 0 WHERE Id_pago = $pid");
registrarBitacora($conn, 'pago', 'editar', $pid, 'Pago #' . $pid . ' liquidado (saldo pendiente a 0)');

// 2) Cambiar estatus a "entregado" y registrar la fecha de entrega (fecha del cambio)
$ahora = date('Y-m-d H:i:s');
if ($d['estatus'] !== 'entregado') {
    $conn->query("UPDATE reparacion SET estatus = 'entregado', fecha_entrega = '$ahora' WHERE Id_reparacion = $repId");
    registrarBitacora($conn, 'reparacion', 'editar', $repId, 'Reparación #' . $repId . ': entregado (por liquidación)');
} elseif ($d['fecha_entrega'] === null) {
    $conn->query("UPDATE reparacion SET fecha_entrega = '$ahora' WHERE Id_reparacion = $repId");
}

// 3) Crear garantía automáticamente si no existe (60 días naturales desde la entrega)
$g = $conn->query("SELECT Id_reparacion FROM garantias WHERE Id_reparacion = $repId");
if (!$g || $g->num_rows == 0) {
    $q = $conn->query("SELECT fecha_entrega, zona_falla_h, zona_falla_s FROM reparacion WHERE Id_reparacion = $repId");
    $r = $q->fetch_assoc();
    $fe = $r['fecha_entrega'] ?: $ahora;
    $ftg = date('Y-m-d H:i:s', strtotime($fe . ' +60 days'));
    $zh = $conn->real_escape_string($r['zona_falla_h']);
    $zs = $conn->real_escape_string($r['zona_falla_s']);
    $motivo = 'Garantía generada automáticamente por liquidación (60 días naturales)';
    $ok = $conn->query(
        "INSERT INTO garantias (Id_reparacion, Id_tecnico, fecha_entrega, fecha_termino_garantia, motivo_garantia, zona_falla_h, zona_falla_s)
         VALUES ($repId, $tecId, '$fe', '$ftg', '$motivo', '$zh', '$zs')"
    );
    if ($ok) { registrarBitacora($conn, 'garantias', 'crear', $repId, 'Garantía automática de reparación #' . $repId); }
}

$conn->close();

// 4) Generar automáticamente el PDF de garantía
header('Location: ' . BASE_URL . 'garantias/pdf.php?id=' . $repId);
exit;