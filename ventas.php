<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}
require_once 'conexion.php';
$mensaje = '';
// Obtener productos
$productos = [];
$res = $conn->query("SELECT * FROM productos ORDER BY nombre ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $productos[] = $row;
    }
}
// Inicializar carrito en sesión
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}
// Agregar producto al carrito
if (isset($_POST['agregar'])) {
    $id = intval($_POST['producto_id']);
    $cantidad = intval($_POST['cantidad']);
    if ($cantidad < 1) $cantidad = 1;
    if (isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id] += $cantidad;
    } else {
        $_SESSION['carrito'][$id] = $cantidad;
    }
}
// Quitar producto del carrito
if (isset($_POST['quitar'])) {
    $id = intval($_POST['producto_id']);
    unset($_SESSION['carrito'][$id]);
}
// Vaciar carrito
if (isset($_POST['vaciar'])) {
    $_SESSION['carrito'] = [];
}
// Realizar venta (con confirmación de cambio)
if (isset($_POST['cobrar']) && !empty($_SESSION['carrito'])) {
    $medio_pago = $_POST['medio_pago'];
    $usuario_id = $_SESSION['usuario_id'];
    $total = 0;
    $detalles = [];
    foreach ($_SESSION['carrito'] as $id => $cantidad) {
        $prod = $conn->query("SELECT * FROM productos WHERE id = $id")->fetch_assoc();
        if ($prod) {
            $precio = $prod['precio'];
            $total += $precio * $cantidad;
            $detalles[] = ['id' => $id, 'cantidad' => $cantidad, 'precio' => $precio];
        }
    }
    $dinero_recibido = isset($_POST['dinero_recibido']) ? floatval($_POST['dinero_recibido']) : 0;
    $cambio = $dinero_recibido - $total;
    if ($medio_pago == 'Efectivo' && $dinero_recibido < $total && (!isset($_POST['confirmar_cobro']) || $_POST['confirmar_cobro'] != '1')) {
        $mensaje = '<div class="alert alert-danger">No se puede recibir una cantidad menor al pago total. Dinero recibido: $' . number_format($dinero_recibido,2) . ' | Total: $' . number_format($total,2) . '</div>';
    } else if ($medio_pago == 'Efectivo' && (!isset($_POST['confirmar_cobro']) || $_POST['confirmar_cobro'] != '1')) {
        // Mostrar confirmación de cambio
        $mensaje = '<form method="POST">';
        foreach ($_POST as $k => $v) {
            if ($k !== 'cobrar') {
                $mensaje .= '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '">';
            }
        }
        $mensaje .= '<input type="hidden" name="confirmar_cobro" value="1">';
        $mensaje .= '<div class="alert alert-warning">Dinero recibido: $' . number_format($dinero_recibido,2) . '<br>Cambio a devolver: $' . number_format($cambio,2) . '</div>';
        $mensaje .= '<button type="submit" name="cobrar" class="btn btn-success w-100">Confirmar y Registrar Venta</button>';
        $mensaje .= '</form>';
    } else {
        $conn->begin_transaction();
        $sql = "INSERT INTO ventas (usuario_id, total, medio_pago) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ids', $usuario_id, $total, $medio_pago);
        if ($stmt->execute()) {
            $venta_id = $stmt->insert_id;
            $ok = true;
            foreach ($detalles as $d) {
                $sql2 = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param('iiid', $venta_id, $d['id'], $d['cantidad'], $d['precio']);
                if (!$stmt2->execute()) {
                    $ok = false;
                    break;
                }
                $stmt2->close();
                // Actualizar stock si no es 999
                $prod = $conn->query("SELECT cantidad FROM productos WHERE id = {$d['id']}")->fetch_assoc();
                if ($prod && $prod['cantidad'] != 999) {
                    $nuevo_stock = $prod['cantidad'] - $d['cantidad'];
                    if ($nuevo_stock < 0) $nuevo_stock = 0;
                    $conn->query("UPDATE productos SET cantidad = $nuevo_stock WHERE id = {$d['id']}");
                }
            }
            if ($ok) {
                $conn->commit();
                $mensaje = 'Venta registrada correctamente.';
                $_SESSION['carrito'] = [];
            } else {
                $conn->rollback();
                $mensaje = 'Error al registrar la venta.';
            }
        } else {
            $conn->rollback();
            $mensaje = 'Error al registrar la venta.';
        }
        $stmt->close();
    }
}
// Mostrar carrito
$carrito = [];
$total = 0;
if (!empty($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $id => $cantidad) {
        $prod = $conn->query("SELECT * FROM productos WHERE id = $id")->fetch_assoc();
        if ($prod) {
            $prod['cantidad_carrito'] = $cantidad;
            $prod['subtotal'] = $prod['precio'] * $cantidad;
            $carrito[] = $prod;
            $total += $prod['subtotal'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas</title>
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
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h4>Selecciona productos</h4>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($productos as $prod): ?>
                    <div class="col">
                        <div class="card producto-card">
                            <?php if ($prod['foto']): ?>
                                <img src="<?php echo $prod['foto']; ?>" class="card-img-top producto-img" alt="Foto">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($prod['nombre']); ?></h5>
                                <p class="card-text">$<?php echo number_format($prod['precio'],2); ?></p>
                                <form method="POST" class="d-flex">
                                    <input type="hidden" name="producto_id" value="<?php echo $prod['id']; ?>">
                                    <input type="number" name="cantidad" value="1" min="1" class="form-control me-2" style="width:80px;">
                                    <button type="submit" name="agregar" class="btn btn-success">Agregar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-4">
                <h4>Carrito</h4>
                <?php if ($mensaje) { echo '<div class="alert alert-info">' . $mensaje . '</div>'; } ?>
                <form method="POST">
                    <button type="submit" name="vaciar" class="btn btn-warning btn-sm mb-2">Vaciar carrito</button>
                </form>
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($carrito)): ?>
                            <p class="text-center">El carrito está vacío.</p>
                        <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cant.</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($carrito as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                    <td><?php echo $item['cantidad_carrito']; ?></td>
                                    <td>$<?php echo number_format($item['subtotal'],2); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" name="quitar" class="btn btn-danger btn-sm">Quitar</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <h5 class="text-end">Total: $<?php echo number_format($total,2); ?></h5>
                        <form method="POST">
                            <div class="mb-2">
                                <label for="medio_pago" class="form-label">Medio de pago</label>
                                <select name="medio_pago" id="medio_pago" class="form-select" required onchange="mostrarCampoEfectivo()">
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Tarjeta">Tarjeta</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-2" id="campo_efectivo">
                                <label for="dinero_recibido" class="form-label">Dinero recibido</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="dinero_recibido" name="dinero_recibido" required>
                            </div>
                            <button type="submit" name="cobrar" class="btn btn-primary w-100">Cobrar</button>
                        </form>
                        <script>
                        function mostrarCampoEfectivo() {
                            var medio = document.getElementById('medio_pago').value;
                            var campo = document.getElementById('campo_efectivo');
                            if (medio === 'Efectivo') {
                                campo.style.display = 'block';
                                document.getElementById('dinero_recibido').required = true;
                            } else {
                                campo.style.display = 'none';
                                document.getElementById('dinero_recibido').required = false;
                            }
                        }
                        document.addEventListener('DOMContentLoaded', function() {
                            mostrarCampoEfectivo();
                        });
                        </script>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="dashboard.php" class="btn btn-secondary w-100 mt-3">Volver al Panel</a>
            </div>
        </div>
    </div>
</body>
</html>
