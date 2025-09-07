<?php
// backend/reportes/reservas_pdf.php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/fpdf.php';

// Helper para acentos con FPDF (ISO-8859-1)
function t($s){ return iconv('UTF-8','ISO-8859-1//TRANSLIT',(string)$s); }

// ---- PDF en horizontal (L) ----
$pdf = new FPDF('L', 'mm', 'A4'); // Landscape
$pdf->SetTitle('Hotel Paraíso - Reporte de Reservaciones');
$pdf->AddPage();

// Encabezado
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, t('Hotel Paraíso - Reporte de Reservaciones'), 0, 1, 'C');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, t('Generado: '.date('Y-m-d H:i')), 0, 1, 'R');
$pdf->Ln(2);

// Encabezados de tabla
$pdf->SetFont('Arial', 'B', 10);
/*
  Anchos pensados para A4 horizontal (ancho útil ~277mm):
  ID (14) + Fecha (30) + Cliente (160) + Hab (18) + Precio (35) = 257mm
*/
$w = [14, 30, 160, 18, 35]; // ID, Fecha, Cliente, Hab, Precio

$pdf->Cell($w[0], 8, t('ID'),           1, 0, 'C');
$pdf->Cell($w[1], 8, t('Fecha'),        1, 0, 'C');
$pdf->Cell($w[2], 8, t('Cliente'),      1, 0, 'L');
$pdf->Cell($w[3], 8, t('Hab.'),         1, 0, 'C');
$pdf->Cell($w[4], 8, t('Precio'),       1, 1, 'R');

$pdf->SetFont('Arial', '', 10);

// Consulta
$sql = "SELECT 
  r.id_reservacion,
  r.fecha_reservacion,
  CONCAT(c.nombre,' ',c.apellido_paterno,' ',COALESCE(c.apellido_materno,'')) AS cliente,
  h.numero_habitacion,
  h.precio
FROM Reservacion r
JOIN Cliente c    ON r.id_cliente = c.id_cliente
JOIN Habitacion h ON r.id_habitacion = h.id_habitacion
ORDER BY r.id_reservacion DESC";

$res = $mysqli->query($sql);

if (!$res || $res->num_rows === 0) {
    $pdf->Cell(array_sum($w), 8, t('Sin datos'), 1, 1, 'C');
} else {
    while ($row = $res->fetch_assoc()) {
        $id      = (int)$row['id_reservacion'];
        $fecha   = substr((string)$row['fecha_reservacion'], 0, 10);
        $cliente = (string)$row['cliente'];
        $hab     = (string)$row['numero_habitacion'];
        // Agregamos ' MXN' al final del precio
        $precio  = number_format((float)$row['precio'], 2) . ' MXN';

        $pdf->Cell($w[0], 8, t($id),      1, 0, 'C');
        $pdf->Cell($w[1], 8, t($fecha),   1, 0, 'C');
        $pdf->Cell($w[2], 8, t($cliente), 1, 0, 'L');
        $pdf->Cell($w[3], 8, t($hab),     1, 0, 'C');
        $pdf->Cell($w[4], 8, t($precio),  1, 1, 'R');
    }
}

$pdf->Output('I', 'reservaciones.pdf');