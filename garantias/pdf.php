<?php
// =============================================
//  PDF DE GARANTÍA - Se genera cuando el
//  estatus de la reparación es "entregado".
//  Período: 60 días naturales a partir de la
//  fecha en que el equipo pasó a "entregado".
// =============================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = conectarBD();
$esAdmin = esAdmin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?err=' . urlencode('Reparación no válida.'));
    exit;
}

$sql = "SELECT g.*,
               r.Id_equipo, r.diagnostico, r.observaciones AS obs_reparacion,
               r.trabajo_realizado, r.estatus, r.fecha_entrega AS fecha_entrega_rep,
               CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre, c.telefono,
               e.tipo_equipo, e.marca, e.modelo, e.serie, e.color, e.accesorios,
               CONCAT(t.nombre, ' ', t.apellido) AS tecnico_nombre
        FROM garantias g
        INNER JOIN reparacion r ON r.Id_reparacion = g.Id_reparacion
        INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
        INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
        INNER JOIN tecnico t ON t.Id_tecnico = g.Id_tecnico
        WHERE g.Id_reparacion = $id";
$res = $conn->query($sql);
if ($res->num_rows == 0) {
    header('Location: index.php?err=' . urlencode('Garantía no encontrada.'));
    exit;
}
$d = $res->fetch_assoc();

if ($d['estatus'] !== 'entregado') {
    header('Location: index.php?err=' . urlencode('La garantía solo se imprime cuando la reparación está en estatus "entregado".'));
    exit;
}

// Monto total, anticipo y saldo pendiente (del último pago de la reparación)
$pg = $conn->query("SELECT monto, anticipo, saldo_pendiente
                    FROM pago WHERE Id_reparacion = $id
                    ORDER BY fecha_pago DESC, Id_pago DESC LIMIT 1");
$monto_total = 0.0; $anticipo_total = 0.0; $saldo_pendiente = 0.0;
if ($pg && $pg->num_rows) {
    $p = $pg->fetch_assoc();
    $monto_total = (float)$p['monto'];
    $anticipo_total = (float)$p['anticipo'];
    $saldo_pendiente = (float)$p['saldo_pendiente'];
}

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

// ---------- Logotipo (472 px a 96 dpi = 124.9 mm, alto proporcional, esquina sup. izq.) ----------
$logo = dirname(__DIR__) . '/assets/img/logo_pdf.jpg';
if (file_exists($logo)) {
    // Calcular altura proporcional (imagen 2400x829)
    $logoW = 124.9;
    $logoH = $logoW * (829 / 2400);
    $pdf->Image($logo, 15, 8, $logoW);
    $yBase = 8 + $logoH + 4;
} else {
    $yBase = 12;
}

// ---------- Encabezado debajo del logotipo (izquierda) ----------
$pdf->SetY($yBase);
$pdf->SetFont('Helvetica', 'B', 22);
$pdf->Cell(0, 10, u('PANA TICS'), 0, 1, 'L');
$pdf->SetFont('Helvetica', 'B', 13);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(0, 8, u('Garantía de reparación N° ') . $id, 0, 1, 'L');
$pdf->SetFont('Helvetica', '', 11);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 7, u('Generado por: ') . u($d['tecnico_nombre']), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);

$y = $pdf->GetY() + 4;

// ---------- Periodo de garantía: 60 días naturales desde la entrega ----------
$fechaEntrega = $d['fecha_entrega_rep'] ?: $d['fecha_entrega'];
$fechaTermino = date('Y-m-d H:i:s', strtotime($fechaEntrega . ' +60 days'));

// ---------- Cuadro de vigencia de la garantía ----------
$pdf->SetY($y);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(19, 41, 75);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, u('VIGENCIA DE LA GARANTÍA'), 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', '', 11);

$filasVig = [
    ['Fecha de entrega del equipo reparado:', fmtFecha($fechaEntrega)],
    ['Garantía hasta:', fmtFecha($fechaTermino)],
];
$pdf->SetDrawColor(220, 220, 220);
foreach ($filasVig as $x) {
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(100, 8, u($x[0]), 'LR', 0);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 8, u($x[1]), 'LR', 1);
}
$pdf->Cell(0, 0, '', 'T');
$pdf->Ln(5);

// ---------- Técnico, cliente y equipo ----------
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(19, 41, 75);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, u('TÉCNICO, CLIENTE Y EQUIPO'), 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', '', 11);

$filasCE = [
    ['Técnico:', u($d['tecnico_nombre'])],
    ['Cliente:', u($d['cliente_nombre'])],
    ['Teléfono:', u($d['telefono'])],
    ['Tipo de equipo:', u($d['tipo_equipo'])],
    ['Marca:', u($d['marca'])],
    ['Modelo:', u($d['modelo'])],
    ['Serie:', u($d['serie'])],
    ['Color:', u($d['color'])],
    ['Accesorios:', u($d['accesorios'])],
];
foreach ($filasCE as $x) {
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(55, 7, u($x[0]), 'LR', 0);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 7, $x[1], 'LR', 1);
}
$pdf->Cell(0, 0, '', 'T');
$pdf->Ln(5);

// ---------- Detalles de la reparación ----------
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(19, 41, 75);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, u('DETALLES DE LA REPARACIÓN'), 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', '', 11);

$detalles = [
    'Diagnóstico' => $d['diagnostico'],
    'Observaciones' => $d['obs_reparacion'],
    'Trabajo realizado' => $d['trabajo_realizado'],
    'Motivo de la garantía' => $d['motivo_garantia'],
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
    ['Monto total:', number_format($monto_total, 2)],
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
$pdf->Ln(5);

// ---------- Leyenda del período de garantía (antes del aviso) ----------
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(235, 238, 250);
$pdf->SetDrawColor(19, 41, 75);
$pdf->MultiCell(0, 8, u('Período de garantía: 60 días naturales a partir de la fecha de entrega del equipo'), 1, 'L', true);
$pdf->Ln(5);

// ---------- Aviso sobre equipos no recogidos ----------
require_once __DIR__ . '/../includes/pdf_aviso.php';
pdfAvisoEquipos($pdf);

// ---------- Pie ----------
$pdf->SetY(-20);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 5, u('Documento generado por el sistema PANATICS ERP'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

$pdf->Output('I', 'Garantia_' . $id . '.pdf');
exit;