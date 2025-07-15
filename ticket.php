<?php
session_start();
if (!isset($_SESSION['ticket_pdf'])) {
    echo 'No hay ticket para mostrar.';
    exit();
}
require('fpdf/fpdf.php');
require_once 'conexion.php';
// Obtener configuración
$res = $conn->query("SELECT * FROM configuracion ORDER BY id DESC LIMIT 1");
$config = $res ? $res->fetch_assoc() : null;
$venta_id = $_SESSION['ticket_pdf']['venta_id'];
$total = $_SESSION['ticket_pdf']['total'];
$medio_pago = $_SESSION['ticket_pdf']['medio_pago'];
$dinero_recibido = $_SESSION['ticket_pdf']['dinero_recibido'];
$cambio = $_SESSION['ticket_pdf']['cambio'];
$nombre_tienda = $config ? $config['nombre_tienda'] : 'POS Web';
$github = 'github.com/BlackDragonG66/POSWEB';
$direccion = $config ? $config['direccion_local'] : 'Tu dirección aquí';
$logo = $config && $config['logo_negocio'] ? $config['logo_negocio'] : '';
// Obtener productos de la venta
$productos = [];
$sql = "SELECT p.nombre, dv.cantidad, dv.precio_unitario FROM detalle_ventas dv JOIN productos p ON dv.producto_id = p.id WHERE dv.venta_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $venta_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $productos[] = $row;
}
$stmt->close();
$pdf = new FPDF('P','mm',[58,150]); // Ticket pequeño
$pdf->AddPage();
if ($logo) {
    $pdf->Image($logo, 14, 2, 30, 0, '', '', true);
    $pdf->Ln(18);
}
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,5,utf8_decode($nombre_tienda),0,1,'C');
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,4,utf8_decode($direccion),0,1,'C');
$pdf->Cell(0,4,date('d/m/Y H:i'),0,1,'C');
$pdf->Cell(0,0,str_repeat('-',30),0,1,'C');
$pdf->Ln(2);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(0,4,'Productos:',0,1);
$pdf->SetFont('Arial','',8);
foreach ($productos as $p) {
    $nombre = utf8_decode($p['nombre']);
    $cantidad = $p['cantidad'];
    $precio = $p['precio_unitario'];
    $subtotal = $cantidad * $precio;
    $pdf->Cell(0,4,sprintf('%s x%d $%.2f',substr($nombre,0,16),$cantidad,$subtotal),0,1);
}
$pdf->Cell(0,0,str_repeat('-',30),0,1,'C');
$pdf->Ln(2);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(0,4,'Total: $'.number_format($total,2),0,1,'R');
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,4,'Pago: '.utf8_decode($medio_pago),0,1,'R');
if ($medio_pago == 'Efectivo') {
    $pdf->Cell(0,4,'Recibido: $'.number_format($dinero_recibido,2),0,1,'R');
    $pdf->Cell(0,4,'Cambio: $'.number_format($cambio,2),0,1,'R');
}
$pdf->Ln(2);
$pdf->Cell(0,0,str_repeat('-',30),0,1,'C');
$pdf->SetFont('Arial','B',7);
$pdf->Cell(0,4,'POS Web',0,1,'C');
$pdf->SetFont('Arial','',7);
$pdf->Cell(0,4,'Desarrollado por Gabriel Guzman Rodriguez',0,1,'C');
$pdf->SetFont('Arial','',7);
$pdf->Cell(0,4,$github,0,1,'C');
$pdf->Output('I','ticket.pdf');
unset($_SESSION['ticket_pdf']);
exit();
?>
