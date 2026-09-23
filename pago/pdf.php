<?php
// =============================================
//  PDF DE PAGO - Datos principales de la
//  reparación + fotos (200 px) + totales del pago
// =============================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = conectarBD();
$esAdmin = esAdmin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php?err=' . urlencode('Pago no válido.'));
    exit;
}

$sql = "SELECT p.*,
               r.Id_equipo, r.fecha_recepcion, r.diagnostico, r.observaciones AS obs_reparacion,
               c.nombre AS cliente_nombre, c.apellido AS cliente_apellido, c.telefono,
               e.tipo_equipo, e.marca, e.modelo, e.serie, e.color, e.accesorios,
               t.nombre AS tec_nombre, t.apellido AS tec_apellido
        FROM pago p
        INNER JOIN reparacion r ON r.Id_reparacion = p.Id_reparacion
        INNER JOIN equipo e ON e.Id_equipo = r.Id_equipo
        INNER JOIN cliente c ON c.Id_cliente = e.Id_cliente
        INNER JOIN tecnico t ON t.Id_tecnico = r.Id_tecnico
        WHERE p.Id_pago = $id";
$res = $conn->query($sql);
if ($res->num_rows == 0) {
    header('Location: index.php?err=' . urlencode('Pago no encontrado.'));
    exit;
}
$d = $res->fetch_assoc();

// Permisos: administrador o técnico agendado de la reparación del pago
if (!$esAdmin) {
    $chkP = $conn->query("SELECT p.Id_pago FROM pago p INNER JOIN reparacion r ON r.Id_reparacion = p.Id_reparacion WHERE p.Id_pago = $id AND r.Id_tecnico = " . tecnicoIdActual());
    if (!$chkP || $chkP->num_rows == 0) {
        header('Location: index.php?err=' . urlencode('Solo el técnico agendado puede ver este PDF.'));
        exit;
    }
}

// Fotos del equipo (hasta 5, una por vista)
$fotos = [];
$fres = $conn->query("SELECT ruta_imagen, tipo_foto FROM fotos WHERE Id_reparacion = " . (int)$d['Id_reparacion'] . " ORDER BY tipo_foto, id_foto DESC");
if ($fres) {
    $vistas = [];
    while ($f = $fres->fetch_assoc()) {
        $tf = (int)$f['tipo_foto'];
        if (isset($vistas[$tf])) continue;
        $vistas[$tf] = $f;
        $fotos[] = $f;
        if (count($fotos) >= 5) break;
    }
}

require_once __DIR__ . '/../includes/fpdf/fpdf.php';

function u($s) {
    $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
    return ($out === false) ? $s : $out;
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
$pdf->Cell(0, 8, u('Comprobante de pago N° ') . $d['Id_pago'], 0, 1, 'L');
$pdf->SetFont('Helvetica', '', 11);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 7, u('Generado por: ') . u($d['tec_nombre'] . ' ' . $d['tec_apellido']), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);

$y = $pdf->GetY() + 4;

// ---------- Cuadro de datos del pago ----------
$pdf->SetY($y);
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(19, 41, 75);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, u('DATOS DEL PAGO'), 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', '', 11);

$filasPago = [
    ['Reparación:', '#' . $d['Id_reparacion']],
    ['Fecha de recepción:', date('d/m/Y H:i', strtotime($d['fecha_recepcion']))],
    ['Tipo de pago:', u($d['tipo_pago'])],
    ['Referencia:', u($d['referencia'])],
];
$pdf->SetDrawColor(220, 220, 220);
foreach ($filasPago as $x) {
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(55, 7, u($x[0]), 'LR', 0);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 7, u($x[1]), 'LR', 1);
}
$pdf->Cell(0, 0, '', 'T');
$pdf->Ln(5);

// ---------- Cliente, equipo y técnico ----------
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetFillColor(19, 41, 75);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(0, 8, u('TÉCNICO, CLIENTE Y EQUIPO'), 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Helvetica', '', 11);

$filasCE = [
    ['Técnico:', u($d['tec_nombre'] . ' ' . $d['tec_apellido'])],
    ['Cliente:', u($d['cliente_nombre'] . ' ' . $d['cliente_apellido'])],
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
    ['Monto total:', number_format((float)$d['monto'], 2)],
    ['Anticipo:', number_format((float)$d['anticipo'], 2)],
    ['Saldo pendiente:', number_format((float)$d['saldo_pendiente'], 2)],
];
$pdf->SetFont('Helvetica', '', 11);
foreach ($totales as $i => $x) {
    $esSaldo = ($i === 2);
    $pdf->SetFont('Helvetica', $esSaldo ? 'B' : '', 11);
    $pdf->Cell(55, 8, u($x[0]), 'LR', 0, 'L', $esSaldo);
    $pdf->Cell(0, 8, '$' . $x[1], 'LR', 1, 'R', $esSaldo);
}
$pdf->Cell(0, 0, '', 'T');

// ---------- Fotos del equipo (200 px = 50 mm c/u) ----------
if (count($fotos) > 0) {
    $pdf->Ln(4);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetFillColor(19, 41, 75);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, u('FOTOS DEL EQUIPO'), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);

    $wFoto = 50;
    $hFoto = 50;
    $cols = 3;
    $spanX = 5;
    $totalW = $wFoto * $cols + $spanX * ($cols - 1);
    $startX = (215.9 - $totalW) / 2;
    $x = $startX;
    $y = $pdf->GetY() + 4;
    $yBottom = $y;
    $i = 0;
    foreach ($fotos as $foto) {
        $ruta = dirname(__DIR__) . '/' . $foto['ruta_imagen'];
        if (!file_exists($ruta)) { $i++; continue; }
        $pdf->Image($ruta, $x, $y, $wFoto, $hFoto);
        $yBottom = $y + $hFoto;
        $x += $wFoto + $spanX;
        $i++;
        if ($i % $cols === 0) {
            $x = $startX;
            $y += $hFoto + $spanX;
        }
    }
    $pdf->SetY($yBottom);
}

// ---------- Aviso sobre equipos no recogidos ----------
$pdf->Ln(4);
require_once __DIR__ . '/../includes/pdf_aviso.php';
pdfAvisoEquipos($pdf);

// ---------- Pie ----------
$pdf->SetY(-20);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 5, u('Documento generado por el sistema PANATICS ERP'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

$pdf->Output('I', 'Pago_' . $id . '.pdf');
exit;