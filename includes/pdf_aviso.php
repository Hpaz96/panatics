<?php
// =============================================
// PANATICS - Leyenda "AVISO SOBRE EQUIPOS NO
// RECOGIDOS" para los PDFs de recepción y de
// garantía. Recibe el objeto FPDF en $pdf.
// =============================================
function pdfAvisoEquipos($pdf) {
    $x0 = $pdf->GetX();
    $yIni = $pdf->GetY();

    if ($yIni > 230) {
        $pdf->AddPage();
        $yIni = $pdf->GetY();
    }

    $pdf->SetFillColor(253, 247, 238);
    $pdf->SetTextColor(122, 26, 26);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->MultiCell(185.9, 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'AVISO SOBRE EQUIPOS NO RECOGIDOS'), 0, 'C');

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', '', 10);

    $parrafos = [
        'Una vez notificado al cliente que su equipo se encuentra listo, contará con un plazo de <b>30 días naturales para recogerlo</b>.',
        'Transcurrido dicho plazo, podrán generarse <b>cargos por almacenamiento de $10 MXN por día</b>, previa notificación al cliente.',
        'Después de <b>90 días naturales sin recoger el equipo</b>, y conforme a la legislación aplicable, PanaTics podrá ejercer las acciones correspondientes respecto del equipo para recuperar los gastos, adeudos y costos generados por el servicio y almacenamiento.',
        'El cliente acepta que es su responsabilidad proporcionar datos de contacto correctos y mantenerse atento a las notificaciones relacionadas con su equipo.',
    ];

    foreach ($parrafos as $parrafo) {
        pdfWriteSegments($pdf, $parrafo);
    }

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', '', 10);
}

function pdfWriteSegments($pdf, $text) {
    $parts = preg_split('#(<\/?b>)#', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $part) {
        if ($part === '<b>') { $pdf->SetFont('Helvetica', 'B', 10); continue; }
        if ($part === '</b>') { $pdf->SetFont('Helvetica', '', 10); continue; }
        if ($part === '') { continue; }
        $s = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $part);
        $pdf->Write(5, ($s === false) ? $part : $s);
    }
    $pdf->Ln(5);
    $pdf->SetFont('Helvetica', '', 10);
}