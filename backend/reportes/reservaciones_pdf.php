<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/fpdf.php'; // <- ajusta si tu ruta es diferente

function t($s){ return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s); }

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetTitle('Reporte de Reservaciones');
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10, t('Hotel Paraíso - Reporte de Reservaciones'),0,1,'C');

$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6, t('Generado: '.date('Y-m-d H:i')),0,1,'R');
$pdf->Ln(2);

// Encabezados
$pdf->SetFont('Arial','B',10);
$w = [20, 38, 92, 20, 20]; // ID, Fecha, Cliente, Hab, Precio
$pdf->Cell($w[0],8, t('ID'),1,0,'C');
$pdf->Cell($w[1],8, t('Fecha'),1,0,'C');
$pdf->Cell($w[2],8, t('Cliente'),1,0,'C');
$pdf->Cell($w[3],8, t('Hab.'),1,0,'C');
$pdf->Cell($w[4],8, t('Precio'),1,1,'C');

$pdf->SetFont('Arial','',10);

$sql = "SELECT 
  r.id_reservacion,
  r.fecha_reservacion,
  CONCAT(c.nombre,' ',c.apellido_paterno,' ',COALESCE(c.apellido_materno,'')) AS cliente,
  h.numero_habitacion,
  h.precio
FROM Reservacion r
JOIN Cliente c   ON r.id_cliente = c.id_cliente
JOIN Habitacion h ON r.id_habitacion = h.id_habitacion
ORDER BY r.id_reservacion DESC";

$res = $mysqli->query($sql);
if (!$res || $res->num_rows === 0) {
    $pdf->Cell(0,8, t('Sin datos'),1,1,'C');
} else {
    while($row = $res->fetch_assoc()){
        $pdf->Cell($w[0],8, t($row['id_reservacion']),1,0,'C');
        $pdf->Cell($w[1],8, t(substr($row['fecha_reservacion'],0,10)),1,0,'C');
        $pdf->Cell($w[2],8, t($row['cliente']),1,0,'L');
        $pdf->Cell($w[3],8, t($row['numero_habitacion']),1,0,'C');
        $pdf->Cell($w[4],8, t(number_format((float)$row['precio'],2)),1,1,'R');
    }
}
$pdf->Output('I', 'reservaciones.pdf');