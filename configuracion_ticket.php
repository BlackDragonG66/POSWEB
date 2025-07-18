<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}
require_once 'conexion.php';
$mensaje = '';
// Obtener configuración actual
$res = $conn->query("SELECT * FROM configuracion ORDER BY id DESC LIMIT 1");
$config = $res ? $res->fetch_assoc() : null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_tienda = trim($_POST['nombre_tienda']);
    $direccion = trim($_POST['direccion_local']);
    $logo = $config ? $config['logo_negocio'] : '';
    if (isset($_FILES['logo_negocio']) && $_FILES['logo_negocio']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['logo_negocio']['name'], PATHINFO_EXTENSION);
        $logo = 'fotos_productos/logo_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['logo_negocio']['tmp_name'], $logo);
    }
    if ($config) {
        $stmt = $conn->prepare("UPDATE configuracion SET nombre_tienda=?, direccion_local=?, logo_negocio=? WHERE id=?");
        $stmt->bind_param('sssi', $nombre_tienda, $direccion, $logo, $config['id']);
        $stmt->execute();
        $stmt->close();
        $mensaje = 'Configuración actualizada.';
    } else {
        $stmt = $conn->prepare("INSERT INTO configuracion (nombre_tienda, direccion_local, logo_negocio) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $nombre_tienda, $direccion, $logo);
        $stmt->execute();
        $stmt->close();
        $mensaje = 'Configuración guardada.';
    }
    header('Location: configuracion_ticket.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración del Ticket</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
    body.dark-mode { background-color: #181a1b !important; color: #f1f1f1 !important; }
    .card.dark-mode { background-color: #23272b !important; color: #f1f1f1 !important; }
    .btn-darkmode { position: fixed; top: 10px; right: 10px; z-index: 9999; }
    label.dark-mode, .form-label.dark-mode { color: #f1f1f1 !important; }
    input.dark-mode, .form-control.dark-mode { background-color: #23272b !important; color: #f1f1f1 !important; border-color: #444 !important; }
    </style>
</head>
<body class="bg-light" id="body">
    <button class="btn btn-warning btn-darkmode" onclick="toggleDarkMode()" id="darkBtn">🌙</button>
    <script>
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        document.querySelectorAll('.card').forEach(c=>c.classList.toggle('dark-mode'));
        document.querySelectorAll('label, .form-label').forEach(l=>l.classList.toggle('dark-mode'));
        document.querySelectorAll('input, .form-control').forEach(i=>i.classList.toggle('dark-mode'));
        let dark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkmode', dark ? '1' : '0');
        document.getElementById('darkBtn').innerText = dark ? '☀️' : '🌙';
    }
    window.onload = function() {
        if(localStorage.getItem('darkmode')==='1') {
            document.body.classList.add('dark-mode');
            document.querySelectorAll('.card').forEach(c=>c.classList.add('dark-mode'));
            document.querySelectorAll('label, .form-label').forEach(l=>l.classList.add('dark-mode'));
            document.querySelectorAll('input, .form-control').forEach(i=>i.classList.add('dark-mode'));
            document.getElementById('darkBtn').innerText = '☀️';
        }
    }
    </script>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Configuración del Ticket</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje) { echo '<div class="alert alert-info">' . $mensaje . '</div>'; } ?>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="nombre_tienda" class="form-label">Nombre de la tienda</label>
                                <input type="text" name="nombre_tienda" id="nombre_tienda" class="form-control" value="<?php echo $config ? htmlspecialchars($config['nombre_tienda']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="direccion_local" class="form-label">Dirección del local</label>
                                <input type="text" name="direccion_local" id="direccion_local" class="form-control" value="<?php echo $config ? htmlspecialchars($config['direccion_local']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="logo_negocio" class="form-label">Logo del negocio</label>
                                <input type="file" name="logo_negocio" id="logo_negocio" class="form-control" accept="image/*">
                                <?php if ($config && $config['logo_negocio']): ?>
                                    <img src="<?php echo $config['logo_negocio']; ?>" alt="Logo actual" class="img-fluid mt-2" style="max-height:100px;">
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Guardar configuración</button>
                        </form>
                        <a href="dashboard.php" class="btn btn-secondary w-100 mt-3">Volver al Panel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
