<?php
session_start();
require_once 'conexion.php';
// Validar si existen usuarios
$res = $conn->query("SELECT COUNT(*) as total FROM usuarios");
$row = $res ? $res->fetch_assoc() : null;
if ($row && $row['total'] == 0) {
    // Si se envió el formulario para crear admin
    if (isset($_POST['crear_admin'])) {
        // Validar de nuevo que no existan usuarios
        $res2 = $conn->query("SELECT COUNT(*) as total FROM usuarios");
        $row2 = $res2 ? $res2->fetch_assoc() : null;
        if ($row2 && $row2['total'] == 0) {
            $nombre = 'Administrador';
            $usuario = 'admin';
            $password = md5('eldenring');
            $email = 'admin@posweb.com';
            $sql = "INSERT INTO usuarios (nombre, usuario, password, email, activo) VALUES (?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ssss', $nombre, $usuario, $password, $email);
                $stmt->execute();
                $stmt->close();
                header('Location: index.php?primera=1');
                exit();
            } else {
                $error_admin = 'Error al crear el usuario administrador.';
            }
        } else {
            header('Location: index.php');
            exit();
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bienvenido a POS Web</title>
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
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            document.querySelectorAll('.card').forEach(c=>c.classList.toggle('dark-mode'));
            let dark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkmode', dark ? '1' : '0');
            document.getElementById('darkBtn').innerText = dark ? '☀️' : '🌙';
        }
        window.onload = function() {
            if(localStorage.getItem('darkmode')==='1') {
                document.body.classList.add('dark-mode');
                document.querySelectorAll('.card').forEach(c=>c.classList.add('dark-mode'));
                document.getElementById('darkBtn').innerText = '☀️';
            }
        }
        </script>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header text-center">
                            <h3>¡Bienvenido a POS Web!</h3>
                        </div>
                        <div class="card-body text-center">
                            <p>Primera vez que usas el sistema, ¡felicidades! Espero que sea de tu total agrado.<br>
                            Este es el primer paso para llevar un control profesional de tus ventas e inventario.<br><br>
                            Se va a crear un usuario administrador para que puedas comenzar a usar el sistema.<br>
                            <b>Usuario:</b> <span class="badge bg-dark">admin</span><br>
                            <b>Contraseña:</b> <span class="badge bg-warning text-dark">eldenring</span><br>
                            (¿Gran contraseña verdad? Se desarrolló mientras jugaba Elden Ring 😄)
                            </p>
                            <?php if (isset($error_admin)) echo '<div class="alert alert-danger">'.$error_admin.'</div>'; ?>
                            <form method="POST">
                                <button type="submit" name="crear_admin" class="btn btn-success">Aceptar y crear usuario administrador</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Web - Inicio de Sesión</title>
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
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        document.querySelectorAll('.card').forEach(c=>c.classList.toggle('dark-mode'));
        let dark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkmode', dark ? '1' : '0');
        document.getElementById('darkBtn').innerText = dark ? '☀️' : '🌙';
    }
    window.onload = function() {
        if(localStorage.getItem('darkmode')==='1') {
            document.body.classList.add('dark-mode');
            document.querySelectorAll('.card').forEach(c=>c.classList.add('dark-mode'));
            document.getElementById('darkBtn').innerText = '☀️';
        }
    }
    </script>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>POS Web - Punto de Venta</h3>
                    </div>
                    <div class="card-body">
                        <p>Esta es una aplicación web de punto de venta (POS) pensada para el dominio público. Permite registrar usuarios, productos, realizar ventas y generar reportes de ventas en PDF. Ideal para pequeños negocios, tiendas y comercios.</p>
                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="usuario" class="form-label">Usuario</label>
                                <input type="text" class="form-control" id="usuario" name="usuario" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                        </form>
                        <div class="mt-2 text-center">
                            <a href="contacto.html">Contacto</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
