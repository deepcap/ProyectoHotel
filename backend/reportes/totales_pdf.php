<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/fpdf.php';

function t($s){ return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string)$s); }

$pdf = new FPDF('L', 'mm', 'A4'); // Horizontal para más espacio
$pdf->SetTitle('Reporte de Totales por Fecha');
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10, t('Hotel Paraíso - Totales por fecha'),0,1,'C');

$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6, t('Generado: '.date('Y-m-d H:i')),0,1,'R');
$pdf->Ln(2);

// Encabezados
$pdf->SetFont('Arial','B',10);
$w = [24, 50, 20, 50]; // Año, Mes, Día, Total
$pdf->Cell($w[0],8, t('Año'),1,0,'C');
$pdf->Cell($w[1],8, t('Mes'),1,0,'C');
$pdf->Cell($w[2],8, t('Día'),1,0,'C');
$pdf->Cell($w[3],8, t('Total cobrado'),1,1,'R');

$pdf->SetFont('Arial','',10);

$sql = "SELECT 
  YEAR(c.fecha_transaccion)       AS Anio,
  MONTHNAME(c.fecha_transaccion)  AS Mes,
  MONTH(c.fecha_transaccion)      AS MesNum,
  DAY(c.fecha_transaccion)        AS Dia,
  SUM(c.monto)                    AS TotalCobrado
FROM Cobro c
GROUP BY Anio, Mes, MesNum, Dia
ORDER BY Anio, MesNum, Dia";

$res = $mysqli->query($sql);
if (!$res || $res->num_rows === 0) {
    $pdf->Cell(0,8, t('Sin datos'),1,1,'C');
} else {
    while($row = $res->fetch_assoc()){
        $anio = $row['Anio'];
        $mes  = $row['Mes'];
        $dia  = $row['Dia'];
        // Aquí agregamos MXN después del número
        $tot  = number_format((float)$row['TotalCobrado'], 2) . ' MXN';

        $pdf->Cell($w[0],8, t($anio),1,0,'C');
        $pdf->Cell($w[1],8, t($mes),1,0,'C');
        $pdf->Cell($w[2],8, t($dia),1,0,'C');
        $pdf->Cell($w[3],8, t($tot),1,1,'R');
    }
}

$pdf->Output('I', 'totales_por_fecha.pdf');