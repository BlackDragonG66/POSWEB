<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}
require_once 'conexion.php';
$mensaje = '';
// Registrar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $cantidad = intval($_POST['cantidad']);
    $precio = floatval($_POST['precio']);
    $codigo_barras = trim($_POST['codigo_barras']);
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto = 'fotos_productos/' . uniqid('prod_') . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $foto);
    }
    if ($cantidad <= 0) $cantidad = 999;
    $sql = "INSERT INTO productos (nombre, cantidad, precio, foto, codigo_barras) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('sidss', $nombre, $cantidad, $precio, $foto, $codigo_barras);
        if ($stmt->execute()) {
            $mensaje = 'Producto registrado correctamente.';
        } else {
            $mensaje = 'Error al registrar producto: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $mensaje = 'Error en la consulta: ' . $conn->error;
    }
}
// Listar productos
$productos = [];
$res = $conn->query("SELECT * FROM productos ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $productos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos</title>
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
        <div class="row">
            <div class="col-md-5">
                <div class="card mb-4">
                    <div class="card-header text-center">
                        <h4>Registrar Producto</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje) { echo '<div class="alert alert-info">' . $mensaje . '</div>'; } ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label for="cantidad" class="form-label">Cantidad</label>
                                <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" placeholder="999 si es sin control de stock">
                            </div>
                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio</label>
                                <input type="number" step="0.01" class="form-control" id="precio" name="precio" required>
                            </div>
                            <div class="mb-3">
                                <label for="codigo_barras" class="form-label">Código de Barras (opcional)</label>
                                <input type="text" class="form-control" id="codigo_barras" name="codigo_barras">
                            </div>
                            <div class="mb-3">
                                <label for="foto" class="form-label">Foto del Producto (opcional)</label>
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Registrar</button>
                        </form>
                    </div>
                </div>
                <a href="dashboard.php" class="btn btn-secondary w-100">Volver al Panel</a>
            </div>
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header text-center">
                        <h4>Lista de Productos</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>Foto</th>
                                        <th>Nombre</th>
                                        <th>Cantidad</th>
                                        <th>Precio</th>
                                        <th>Código de Barras</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $prod): ?>
                                    <tr>
                                        <td><?php if ($prod['foto']) echo '<img src="' . $prod['foto'] . '" width="50">'; ?></td>
                                        <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                                        <td><?php echo $prod['cantidad']; ?></td>
                                        <td>$<?php echo number_format($prod['precio'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($prod['codigo_barras']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($productos)): ?>
                                    <tr><td colspan="5" class="text-center">No hay productos registrados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
