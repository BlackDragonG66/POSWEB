<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
    body.dark-mode { background-color: #181a1b !important; color: #f1f1f1 !important; }
    .card.dark-mode { background-color: #23272b !important; color: #f1f1f1 !important; }
    .btn-darkmode { position: fixed; top: 10px; right: 10px; z-index: 9999; }
    </style>
</head>
<body class="bg-light" id="body">
    <button class="btn btn-warning btn-darkmode" onclick="toggleDarkMode()" id="darkBtn">🌙</button>
    <script>
    function setGestionarUsuariosBtnColor() {
        const btn = document.getElementById('btnGestionarUsuarios');
        if (!btn) return;
        if(document.body.classList.contains('dark-mode')) {
            btn.classList.remove('btn-outline-dark');
            btn.classList.add('btn-outline-light');
        } else {
            btn.classList.remove('btn-outline-light');
            btn.classList.add('btn-outline-dark');
        }
    }
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        document.querySelectorAll('.card').forEach(c=>c.classList.toggle('dark-mode'));
        let dark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkmode', dark ? '1' : '0');
        document.getElementById('darkBtn').innerText = dark ? '☀️' : '🌙';
        setGestionarUsuariosBtnColor();
    }
    window.onload = function() {
        if(localStorage.getItem('darkmode')==='1') {
            document.body.classList.add('dark-mode');
            document.querySelectorAll('.card').forEach(c=>c.classList.add('dark-mode'));
            document.getElementById('darkBtn').innerText = '☀️';
        }
        setGestionarUsuariosBtnColor();
    }
    </script>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!</h3>
                    </div>
                    <div class="card-body text-center">
                        <p>Este es el panel principal del sistema POS Web.</p>
                        <div class="d-grid gap-2 col-6 mx-auto">
                            <a href="productos.php" class="btn btn-outline-primary">Registrar Productos</a>
                            <a href="ventas.php" class="btn btn-outline-success">Realizar Venta</a>
                            <a href="reportes.php" class="btn btn-outline-info">Reportes de Ventas</a>
                            <a href="inventario.php" class="btn btn-outline-warning">Inventario</a>
                            <a href="registro.php" class="btn btn-outline-secondary">Registrar Usuario</a>
                            <a href="gestionar_usuarios.php" class="btn btn-outline-dark" id="btnGestionarUsuarios">Gestionar Usuarios</a>
                            <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
