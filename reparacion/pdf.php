<?php
// =============================================
//  PDF DE REPARACIÓN - Se genera cuando la
//  reparación tiene estatus "reparado"
// =============================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = conectarBD();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?err=' . urlencode('Reparación no válida.'));
    exit;
}

$sql = "SELECT r.*,
               c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
               c.telefono, c.correo, c.fecha AS cliente_fecha, c.sucursal, c.rfc,
               e.tipo_equipo, e.marca, e.modelo, e.serie, e.color,
               e.accesorios, e.estado_fisico, e.observaciones AS obs_equipo,
               t.nombre AS tec_nombre, t.apellido AS tec_apellido
        FROM reparacion r
        INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
        INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
        INNER JOIN tecnico t ON t.Id_tecnico = r.Id_tecnico
        WHERE r.Id_reparacion = $id";
$res = $conn->query($sql);
if ($res->num_rows == 0) {
    header('Location: index.php?err=' . urlencode('Reparación no encontrada.'));
    exit;
}
$d = $res->fetch_assoc();

// El PDF solo se genera cuando el proceso está completo:
// Cliente → Equipo → Reparación → Fotos → Pagos
$nFotos = (int)$conn->query("SELECT COUNT(*) c FROM fotos WHERE Id_reparacion = $id")->fetch_assoc()['c'];
$nPagos = (int)$conn->query("SELECT COUNT(*) c FROM pago WHERE Id_reparacion = $id")->fetch_assoc()['c'];
$pendientes = [];
if (!in_array($d['estatus'], ['reparado', 'entregado'])) {
    $pendientes[] = 'Estatus "reparado" o "entregado"';
}
if ($nFotos < 1) {
    $pendientes[] = 'Fotos del equipo (mínimo 1)';
}
if ($nPagos < 1) {
    $pendientes[] = 'Pago registrado (mínimo 1)';
}
if ($pendientes) {
    header('Location: index.php?err=' . urlencode('Para generar el PDF debes completar el proceso Cliente → Equipo → Reparación → Fotos → Pagos. Pendiente: ' . implode(', ', $pendientes) . '.'));
    exit;
}

// Garantía asociada (60 días naturales a partir de la entrega al cliente)
$garantia = null;
$gres = $conn->query("SELECT * FROM garantias WHERE Id_reparacion = $id");
if ($gres && $gres->num_rows) {
    $garantia = $gres->fetch_assoc();
}

// Anticipos totales y saldo pendiente (último pago)
$pg = $conn->query("SELECT COALESCE(SUM(anticipo),0) AS anticipo_total,
                           (SELECT saldo_pendiente FROM pago WHERE Id_reparacion = $id
                            ORDER BY fecha_pago DESC, Id_pago DESC LIMIT 1) AS saldo_pendiente
                    FROM pago WHERE Id_reparacion = $id")->fetch_assoc();
$anticipo_total = (float)$pg['anticipo_total'];
$saldo_pendiente = ($pg['saldo_pendiente'] !== null) ? (float)$pg['saldo_pendiente'] : 0.0;

require_once __DIR__ . '/../includes/fpdf/fpdf.php';

function u($s) {
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
    return ($out === false) ? $s : $out;
}

function fmtFecha($dt) {
    return ($dt && $dt !== '0000-00-00 00:00:00') ? date('d/m/Y H:i', strtotime($dt)) : '';
}

$pdf = new FPDF('P', 'mm', 'Letter');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// ---------- Logotipo (altura máx. 200 px = 50 mm) ----------
$logo = dirname(__DIR__) . '/assets/img/logo_pdf.jpg';
if (file_exists($logo)) {
    $pdf->Image($logo, 15, 10, 0, 50);
}

// ---------- Encabezado ----------
$pdf->SetXY(70, 14);
$pdf->SetFont('Helvetica', 'B', 22);
$pdf->Cell(0, 10, u('PANA TICS'), 0, 1, 'R');
$pdf->SetX(70);
$pdf->SetFont('Helvetica', 'B', 13);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(0, 8, u('Orden de reparación N° ') . $d['Id_reparacion'], 0, 1, 'R');
$pdf->SetX(70);
$pdf->SetFont('Helvetica', '', 11);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 7, u('Generado por: ') . u($d['tec_nombre'] . ' ' . $d['tec_apellido']), 0, 1, 'R');
$pdf->SetTextColor(0, 0, 0);

$y = 66;

// ---------- Cuadro de fechas ----------
$pdf->SetY($y);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(19, 41, 75);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, u('FECHAS'), 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', '', 11);

$entrega_cliente = $d['fecha_entrega'] ?: (($garantia) ? $garantia['fecha_entrega'] : '');
$filasFechas = [
    ['Fecha de recepción:', fmtFecha($d['fecha_recepcion'])],
    ['Fecha de entrega:', fmtFecha($entrega_cliente)],
];
$pdf->SetDrawColor(220, 220, 220);
foreach ($filasFechas as $x) {
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(55, 8, u($x[0]), 'LR', 0);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 8, u($x[1]), 'LR', 1);
}
$pdf->Cell(0, 0, '', 'T');
$pdf->Ln(5);

// ---------- Cuadro cliente + equipo ----------
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(19, 41, 75);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, u('DATOS DEL CLIENTE Y DEL EQUIPO'), 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', '', 11);

$filasCE = [
    ['Nombre:', u($d['cliente_nombre'] . ' ' . $d['cliente_apellido'])],
    ['Teléfono:', u($d['telefono'])],
    ['Correo:', u($d['correo'])],
    ['Sucursal:', u($d['sucursal'])],
    ['RFC:', u($d['rfc'])],
    ['Tipo de equipo:', u($d['tipo_equipo'])],
    ['Marca:', u($d['marca'])],
    ['Modelo:', u($d['modelo'])],
    ['Serie:', u($d['serie'])],
    ['Color:', u($d['color'])],
    ['Accesorios:', u($d['accesorios'])],
    ['Estado físico:', u($d['estado_fisico'])],
];
foreach ($filasCE as $x) {
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(55, 7, u($x[0]), 'LR', 0);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 7, $x[1], 'LR', 1);
}
$pdf->Cell(0, 0, '', 'T');
$pdf->Ln(5);

// ---------- Lista de detalles ----------
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(19, 41, 75);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, u('DETALLES DE LA REPARACIÓN'), 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', '', 11);

$detalles = [
    'Falla reportada' => $d['falla_reportada'],
    'Zona de falla (Hardware)' => $d['zona_falla_h'],
    'Zona de falla (Software)' => $d['zona_falla_s'],
    'Costo estimado' => '$' . number_format((float)$d['costo_estimado'], 2),
    'Accesorios' => $d['accesorios'],
    'Diagnóstico' => $d['diagnostico'],
    'Observaciones' => $d['observaciones'],
];
foreach ($detalles as $label => $valor) {
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->MultiCell(0, 6, u($label . ':'), 0, 1);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->MultiCell(0, 6, u($valor), 'B', 1);
    $pdf->Ln(2);
}
$pdf->Ln(2);

// ---------- Totales ----------
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(19, 41, 75);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, u('TOTALES'), 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);

$totales = [
    ['Costo total:', number_format((float)$d['costo_total'], 2)],
    ['Anticipo:', number_format($anticipo_total, 2)],
    ['Saldo pendiente:', number_format($saldo_pendiente, 2)],
];
$pdf->SetFont('Helvetica', '', 11);
foreach ($totales as $i => $x) {
    $esSaldo = ($i === 2);
    $pdf->SetFont('Helvetica', $esSaldo ? 'B' : '', 11);
    $pdf->Cell(55, 8, u($x[0]), 'LR', 0, 'L', $esSaldo);
    $pdf->Cell(0, 8, '$' . $x[1], 'LR', 1, 'R', $esSaldo);
}
$pdf->Cell(0, 0, '', 'T');

// ---------- Garantía (60 días) ----------
if ($garantia) {
    $pdf->Ln(4);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetFillColor(19, 41, 75);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, u('GARANTÍA (60 DÍAS NATURALES)'), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetDrawColor(220, 220, 220);
    $filasG = [
        ['Fecha de entrega al cliente:', fmtFecha($garantia['fecha_entrega'])],
        ['Fecha de término de la garantía:', fmtFecha($garantia['fecha_termino_garantia'])],
        ['Motivo de la garantía:', u($garantia['motivo_garantia'])],
        ['Zona de falla (Hardware):', u($garantia['zona_falla_h'])],
        ['Zona de falla (Software):', u($garantia['zona_falla_s'])],
    ];
    foreach ($filasG as $x) {
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(75, 8, u($x[0]), 'LR', 0);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->Cell(0, 8, $x[1], 'LR', 1);
    }
    $pdf->Cell(0, 0, '', 'T');
}

// ---------- Pie ----------
$pdf->SetY(-20);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 5, u('Documento generado por el sistema PANATICS ERP'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

$pdf->Output('I', 'Reparacion_' . $id . '.pdf');
exit;