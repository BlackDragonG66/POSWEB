<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}
require_once 'conexion.php';
$productos = [];
$res = $conn->query("SELECT * FROM productos ORDER BY nombre ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $productos[] = $row;
    }
}
// PDF
if (isset($_POST['pdf'])) {
    require('fpdf/fpdf.php');
    $pdf = new FPDF('P','mm','A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,'Inventario de Productos',0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(15,8,'ID',1);
    $pdf->Cell(60,8,'Nombre',1);
    $pdf->Cell(25,8,'Cantidad',1);
    $pdf->Cell(30,8,'Precio',1);
    $pdf->Cell(50,8,'Código Barras',1);
    $pdf->Ln();
    $pdf->SetFont('Arial','',10);
    foreach ($productos as $p) {
        $pdf->Cell(15,8,$p['id'],1);
        $pdf->Cell(60,8,utf8_decode($p['nombre']),1);
        $pdf->Cell(25,8,$p['cantidad'],1);
        $pdf->Cell(30,8,'$'.number_format($p['precio'],2),1);
        $pdf->Cell(50,8,$p['codigo_barras'],1);
        $pdf->Ln();
    }
    $pdf->Output('D','inventario.pdf');
    exit();
}
if (isset($_POST['csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=inventario.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID','Nombre','Cantidad','Precio','Código de Barras']);
    foreach ($productos as $p) {
        fputcsv($output, [$p['id'], $p['nombre'], $p['cantidad'], $p['precio'], $p['codigo_barras']]);
    }
    fclose($output);
    exit();
}
// Actualizar producto
if (isset($_POST['editar_producto'])) {
    $id = intval($_POST['producto_id']);
    $nombre = trim($_POST['nombre']);
    $cantidad = intval($_POST['cantidad']);
    $precio = floatval($_POST['precio']);
    $codigo_barras = trim($_POST['codigo_barras']);
    if ($nombre !== '' && $cantidad >= 0 && $precio >= 0 && $codigo_barras !== '') {
        $stmt = $conn->prepare("UPDATE productos SET nombre=?, cantidad=?, precio=?, codigo_barras=? WHERE id=?");
        $stmt->bind_param('sidss', $nombre, $cantidad, $precio, $codigo_barras, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: inventario.php');
        exit();
    }
}
// Actualizar imagen del producto
if (isset($_POST['actualizar_foto']) && isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['error'] === UPLOAD_ERR_OK) {
    $id = intval($_POST['producto_id']);
    $ext = pathinfo($_FILES['nueva_foto']['name'], PATHINFO_EXTENSION);
    $foto = 'fotos_productos/' . uniqid('prod_') . '.' . $ext;
    move_uploaded_file($_FILES['nueva_foto']['tmp_name'], $foto);
    $stmt = $conn->prepare("UPDATE productos SET foto=? WHERE id=?");
    $stmt->bind_param('si', $foto, $id);
    $stmt->execute();
    $stmt->close();
    header('Location: inventario.php');
    exit();
}
// Importar inventario desde CSV
if (isset($_POST['importar_csv']) && isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['archivo_csv']['tmp_name'];
    $handle = fopen($archivo, 'r');
    if ($handle) {
        // Leer encabezado
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            // Espera columnas: ID, Nombre, Cantidad, Precio, Código de Barras
            $nombre = isset($data[1]) ? trim($data[1]) : '';
            $cantidad = isset($data[2]) ? intval($data[2]) : 0;
            $precio = isset($data[3]) ? floatval($data[3]) : 0;
            $codigo_barras = isset($data[4]) ? trim($data[4]) : '';
            if ($codigo_barras !== '') {
                // Buscar por código de barras
                $resProd = $conn->query("SELECT id FROM productos WHERE codigo_barras='".$conn->real_escape_string($codigo_barras)."'");
                if ($resProd && $resProd->num_rows > 0) {
                    // Actualizar existente
                    $rowProd = $resProd->fetch_assoc();
                    $stmt = $conn->prepare("UPDATE productos SET nombre=?, cantidad=?, precio=? WHERE id=?");
                    $stmt->bind_param('sidi', $nombre, $cantidad, $precio, $rowProd['id']);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Insertar nuevo
                    $stmt = $conn->prepare("INSERT INTO productos (nombre, cantidad, precio, codigo_barras) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param('sids', $nombre, $cantidad, $precio, $codigo_barras);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        fclose($handle);
        header('Location: inventario.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario</title>
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
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header text-center">
                        <h4>Inventario de Productos</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-3">
                            <button type="submit" name="pdf" class="btn btn-danger">Imprimir PDF</button>
                            <button type="submit" name="csv" class="btn btn-success">Exportar CSV</button>
                        </form>
                        <form method="POST" enctype="multipart/form-data" class="mb-3">
                            <div class="row g-2 align-items-center">
                                <div class="col-auto">
                                    <input type="file" name="archivo_csv" accept=".csv" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" name="importar_csv" class="btn btn-info btn-sm">Cargar Inventario CSV</button>
                                </div>
                                <div class="col-auto">
                                    <span class="text-danger small">* Es necesario que el archivo CSV esté completo y no omita el código de barras.</span>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Cantidad</th>
                                        <th>Precio</th>
                                        <th>Código de Barras</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $p): ?>
                                    <tr>
                                        <form method="POST" enctype="multipart/form-data" class="align-middle">
                                            <td><?php echo $p['id']; ?><input type="hidden" name="producto_id" value="<?php echo $p['id']; ?>"></td>
                                            <td><input type="text" name="nombre" value="<?php echo htmlspecialchars($p['nombre']); ?>" class="form-control form-control-sm" required></td>
                                            <td><input type="number" name="cantidad" value="<?php echo $p['cantidad']; ?>" class="form-control form-control-sm" min="0" required></td>
                                            <td><input type="number" name="precio" value="<?php echo $p['precio']; ?>" class="form-control form-control-sm" min="0" step="0.01" required></td>
                                            <td><input type="text" name="codigo_barras" value="<?php echo htmlspecialchars($p['codigo_barras']); ?>" class="form-control form-control-sm" required></td>
                                            <td>
                                                <button type="submit" name="editar_producto" class="btn btn-primary btn-sm">Guardar</button>
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="abrirModalImagen(<?php echo $p['id']; ?>, '<?php echo isset($p['foto']) && $p['foto'] ? $p['foto'] : ''; ?>')">Actualizar Imagen</button>
                                            </td>
                                        </form>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($productos)): ?>
                                    <tr><td colspan="5" class="text-center">No hay productos registrados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="dashboard.php" class="btn btn-secondary w-100">Volver al Panel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal para actualizar imagen -->
    <div class="modal fade" id="modalImagen" tabindex="-1" aria-labelledby="modalImagenLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="POST" enctype="multipart/form-data" id="formActualizarImagen">
            <div class="modal-header">
              <h5 class="modal-title" id="modalImagenLabel">Actualizar Imagen del Producto</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
              <input type="hidden" name="producto_id" id="modalProductoId">
              <img id="modalImgActual" src="" alt="Imagen actual" class="img-fluid mb-3" style="max-height:200px;">
              <div class="mb-3">
                <input type="file" name="nueva_foto" accept="image/*" class="form-control" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" name="actualizar_foto" class="btn btn-primary">Actualizar Imagen</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- Scripts para modal de imagen -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function abrirModalImagen(id, foto) {
        document.getElementById('modalProductoId').value = id;
        document.getElementById('modalImgActual').src = foto ? foto : 'https://via.placeholder.com/200x200?text=Sin+Imagen';
        var modal = new bootstrap.Modal(document.getElementById('modalImagen'));
        modal.show();
    }
    </script>
</body>
</html>
