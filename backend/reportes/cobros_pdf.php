<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/fpdf.php';

function t($s){ return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s); }

$pdf = new FPDF('L', 'mm', 'A4'); // horizontal porque son más columnas
$pdf->SetTitle('Reporte de Cobros Detallados');
$pdf->AddPage();

// Título
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10, t('Hotel Paraíso - Cobros detallados'),0,1,'C');

// Meta y leyenda de moneda
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6, t('Generado: '.date('Y-m-d H:i')),0,1,'R');
$pdf->Cell(0,6, t('Moneda: MXN'),0,1,'L');
$pdf->Ln(2);

// Encabezados
$pdf->SetFont('Arial','B',10);
// Anchos: ID, Fecha, Monto, Método, Ticket, Cliente, Habitación
$w = [18, 36, 34, 38, 32, 96, 20];
$pdf->Cell($w[0],8, t('ID'),1,0,'C');
$pdf->Cell($w[1],8, t('Fecha'),1,0,'C');
$pdf->Cell($w[2],8, t('Monto (MXN)'),1,0,'R');
$pdf->Cell($w[3],8, t('Método'),1,0,'C');
$pdf->Cell($w[4],8, t('Ticket'),1,0,'C');
$pdf->Cell($w[5],8, t('Cliente'),1,0,'L');
$pdf->Cell($w[6],8, t('Hab.'),1,1,'C');

$pdf->SetFont('Arial','',10);

// Consulta
$sql = "SELECT 
  cbr.id_cobro,
  cbr.fecha_transaccion,
  cbr.monto,
  tp.metodo AS metodo_pago,
  t.numero_ticket,
  CONCAT(cl.nombre,' ',cl.apellido_paterno,' ',COALESCE(cl.apellido_materno,'')) AS cliente,
  h.numero_habitacion
FROM Cobro cbr
JOIN Ticket t       ON cbr.id_ticket = t.id_ticket
JOIN TipoPago tp    ON cbr.id_tipo_pago = tp.id_tipo_pago
JOIN Reservacion r  ON t.id_reservacion = r.id_reservacion
JOIN Cliente cl     ON r.id_cliente = cl.id_cliente
JOIN Habitacion h   ON r.id_habitacion = h.id_habitacion
ORDER BY cbr.fecha_transaccion DESC";

$res = $mysqli->query($sql);

$total = 0.0;

if (!$res || $res->num_rows === 0) {
    $pdf->Cell(0,8, t('Sin datos'),1,1,'C');
} else {
    while($row = $res->fetch_assoc()){
        $monto = (float)$row['monto'];
        $total += $monto;
        $montoStr = '$' . number_format($monto, 2) . ' MXN';

        $pdf->Cell($w[0],8, t($row['id_cobro']),1,0,'C');
        $pdf->Cell($w[1],8, t(substr($row['fecha_transaccion'],0,19)),1,0,'C');
        $pdf->Cell($w[2],8, t($montoStr),1,0,'R');
        $pdf->Cell($w[3],8, t($row['metodo_pago']),1,0,'C');
        $pdf->Cell($w[4],8, t($row['numero_ticket']),1,0,'C');
        $pdf->Cell($w[5],8, t($row['cliente']),1,0,'L');
        $pdf->Cell($w[6],8, t($row['numero_habitacion']),1,1,'C');
    }

    // Fila de total
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell($w[0] + $w[1],8, t('Totales'),1,0,'R');
    $pdf->Cell($w[2],8, t('$'.number_format($total,2).' MXN'),1,0,'R');
    // celdas vacías para completar la fila
    $pdf->Cell($w[3],8,'',1,0);
    $pdf->Cell($w[4],8,'',1,0);
    $pdf->Cell($w[5],8,'',1,0);
    $pdf->Cell($w[6],8,'',1,1);
}

$pdf->Output('I', 'cobros_detallados.pdf');