<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}
require_once 'conexion.php';
$mensaje = '';
$ventas = [];
$detalle_ventas = [];
$total_general = 0;
$fecha_inicio = date('Y-m-d');
$fecha_fin = date('Y-m-d');
// Obtener lista de empleados/usuarios
$usuarios = [];
$res_usuarios = $conn->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC");
if ($res_usuarios) {
    while ($row = $res_usuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
}
$usuario_filtro = isset($_POST['usuario_filtro']) ? intval($_POST['usuario_filtro']) : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_inicio = isset($_POST['fecha_inicio']) ? htmlspecialchars($_POST['fecha_inicio']) : date('Y-m-d');
    $fecha_fin = isset($_POST['fecha_fin']) ? htmlspecialchars($_POST['fecha_fin']) : date('Y-m-d');
    $sql = "SELECT v.id, v.fecha, v.total, v.medio_pago, u.nombre as cajero FROM ventas v JOIN usuarios u ON v.usuario_id = u.id WHERE DATE(v.fecha) BETWEEN ? AND ?";
    $params = [$fecha_inicio, $fecha_fin];
    $types = 'ss';
    if ($usuario_filtro > 0) {
        $sql .= " AND v.usuario_id = ?";
        $params[] = $usuario_filtro;
        $types .= 'i';
    }
    $sql .= " ORDER BY v.fecha DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ventas[] = $row;
        $total_general += $row['total'];
    }
    $stmt->close();
    // Si es reporte diario, obtener detalle
    if (isset($_POST['diario'])) {
        foreach ($ventas as $v) {
            $det = [];
            $sql2 = "SELECT p.nombre, dv.cantidad FROM detalle_ventas dv JOIN productos p ON dv.producto_id = p.id WHERE dv.venta_id = ?";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param('i', $v['id']);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            while ($row2 = $res2->fetch_assoc()) {
                $det[] = htmlspecialchars($row2['nombre']) . ' (x' . intval($row2['cantidad']) . ')';
            }
            $detalle_ventas[$v['id']] = $det;
            $stmt2->close();
        }
    }
    // PDF
    if (isset($_POST['pdf'])) {
        require('fpdf/fpdf.php');
        $pdf = new FPDF('P','mm','A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,'Reporte de Ventas',0,1,'C');
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,10,"Del $fecha_inicio al $fecha_fin",0,1,'C');
        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(20,8,'ID',1);
        $pdf->Cell(35,8,'Fecha',1);
        $pdf->Cell(35,8,'Cajero',1);
        $pdf->Cell(30,8,'Medio Pago',1);
        $pdf->Cell(30,8,'Total',1);
        $pdf->Ln();
        $pdf->SetFont('Arial','',10);
        foreach ($ventas as $v) {
            $pdf->Cell(20,8,$v['id'],1);
            $pdf->Cell(35,8,substr($v['fecha'],0,19),1);
            $pdf->Cell(35,8,utf8_decode($v['cajero']),1);
            $pdf->Cell(30,8,utf8_decode($v['medio_pago']),1);
            $pdf->Cell(30,8,'$'.number_format($v['total'],2),1);
            $pdf->Ln();
        }
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(120,8,'TOTAL',1);
        $pdf->Cell(30,8,'$'.number_format($total_general,2),1);
        $pdf->Ln();
        $pdf->Output('D',"reporte_ventas_{$fecha_inicio}_{$fecha_fin}.pdf");
        exit();
    }
    // PDF Detallado solo para reporte diario
    if (isset($_POST['diario'])) {
        require('fpdf/fpdf.php');
        $pdf = new FPDF('P','mm','A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,'Reporte Diario Detallado',0,1,'C');
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,10,"Fecha: $fecha_inicio",0,1,'C');
        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(15,8,'ID',1);
        $pdf->Cell(30,8,'Fecha',1);
        $pdf->Cell(30,8,'Cajero',1);
        $pdf->Cell(30,8,'Medio',1);
        $pdf->Cell(30,8,'Total',1);
        $pdf->Cell(50,8,'Detalle',1);
        $pdf->Ln();
        $pdf->SetFont('Arial','',10);
        foreach ($ventas as $v) {
            $pdf->Cell(15,8,$v['id'],1);
            $pdf->Cell(30,8,substr($v['fecha'],0,19),1);
            $pdf->Cell(30,8,utf8_decode($v['cajero']),1);
            $pdf->Cell(30,8,utf8_decode($v['medio_pago']),1);
            $pdf->Cell(30,8,'$'.number_format($v['total'],2),1);
            $detalle = isset($detalle_ventas[$v['id']]) ? utf8_decode(implode(", ", $detalle_ventas[$v['id']])) : '';
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->MultiCell(50,8,$detalle,1);
            $pdf->SetXY($x+50, $y);
            $pdf->Ln();
        }
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(105,8,'TOTAL',1);
        $pdf->Cell(30,8,'$'.number_format($total_general,2),1);
        $pdf->Cell(50,8,'',1);
        $pdf->Ln();
        $pdf->Output('D',"reporte_diario_detallado_{$fecha_inicio}.pdf");
        exit();
    }
    // Exportar a CSV
    if (isset($_POST['csv'])) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_ventas_' . $fecha_inicio . '_'. $fecha_fin . '.csv');
        $output = fopen('php://output', 'w');
        $header = ['ID', 'Fecha', 'Cajero', 'Medio Pago', 'Total'];
        if (isset($_POST['diario'])) $header[] = 'Detalle';
        fputcsv($output, $header);
        foreach ($ventas as $v) {
            $row = [$v['id'], $v['fecha'], $v['cajero'], $v['medio_pago'], $v['total']];
            if (isset($_POST['diario'])) {
                $row[] = isset($detalle_ventas[$v['id']]) ? implode(", ", $detalle_ventas[$v['id']]) : '';
            }
            fputcsv($output, $row);
        }
        fclose($output);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Ventas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
    body.dark-mode { background-color: #181a1b !important; color: #f1f1f1 !important; }
    .card.dark-mode { background-color: #23272b !important; color: #f1f1f1 !important; }
    .btn-darkmode { position: fixed; top: 10px; right: 10px; z-index: 9999; }
    table.dark-mode, table.dark-mode th, table.dark-mode td { background-color: #23272b !important; color: #f1f1f1 !important; border-color: #444 !important; }
    </style>
</head>
<body class="bg-light" id="body">
    <button class="btn btn-warning btn-darkmode" onclick="toggleDarkMode()" id="darkBtn">🌙</button>
    <script>
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        document.querySelectorAll('.card').forEach(c=>c.classList.toggle('dark-mode'));
        document.querySelectorAll('table').forEach(t=>t.classList.toggle('dark-mode'));
        let dark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkmode', dark ? '1' : '0');
        document.getElementById('darkBtn').innerText = dark ? '☀️' : '🌙';
    }
    window.onload = function() {
        if(localStorage.getItem('darkmode')==='1') {
            document.body.classList.add('dark-mode');
            document.querySelectorAll('.card').forEach(c=>c.classList.add('dark-mode'));
            document.querySelectorAll('table').forEach(t=>t.classList.add('dark-mode'));
            document.getElementById('darkBtn').innerText = '☀️';
        }
    }
    </script>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h4>Reporte de Ventas</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="fecha_inicio" class="form-label">Desde</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>" required>
                                </div>
                                <div class="col">
                                    <label for="fecha_fin" class="form-label">Hasta</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>" required>
                                </div>
                                <div class="col">
                                    <label for="usuario_filtro" class="form-label">Empleado</label>
                                    <select class="form-select" id="usuario_filtro" name="usuario_filtro">
                                        <option value="0">Todos</option>
                                        <?php foreach ($usuarios as $u): ?>
                                            <option value="<?php echo $u['id']; ?>" <?php if ($usuario_filtro == $u['id']) echo 'selected'; ?>><?php echo htmlspecialchars($u['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Ver Reporte</button>
                                <button type="submit" name="pdf" class="btn btn-danger">Descargar PDF</button>
                                <button type="submit" name="csv" class="btn btn-success">Exportar CSV</button>
                                <button type="submit" name="diario" class="btn btn-warning">Reporte Diario</button>
                            </div>
                        </form>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Cajero</th>
                                        <th>Medio Pago</th>
                                        <th>Total</th>
                                        <?php if (isset($_POST['diario'])): ?><th>Detalle</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventas as $v): ?>
                                    <tr>
                                        <td><?php echo $v['id']; ?></td>
                                        <td><?php echo htmlspecialchars($v['fecha']); ?></td>
                                        <td><?php echo htmlspecialchars($v['cajero']); ?></td>
                                        <td><?php echo htmlspecialchars($v['medio_pago']); ?></td>
                                        <td>$<?php echo number_format($v['total'],2); ?></td>
                                        <?php if (isset($_POST['diario'])): ?>
                                        <td><?php echo isset($detalle_ventas[$v['id']]) ? implode(", ", $detalle_ventas[$v['id']]) : ''; ?></td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($ventas)): ?>
                                    <tr><td colspan="<?php echo isset($_POST['diario']) ? '6' : '5'; ?>" class="text-center">No hay ventas en el rango seleccionado.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (!empty($ventas)): ?>
                                <tfoot>
                                    <tr>
                                        <th colspan="<?php echo isset($_POST['diario']) ? '4' : '4'; ?>" class="text-end">TOTAL</th>
                                        <th colspan="<?php echo isset($_POST['diario']) ? '2' : '1'; ?>">$<?php echo number_format($total_general,2); ?></th>
                                    </tr>
                                </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                        <a href="dashboard.php" class="btn btn-secondary w-100">Volver al Panel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
