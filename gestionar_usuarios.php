<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}
require_once 'conexion.php';
$mensaje = '';
// Cambiar estado activo/inactivo
if (isset($_POST['cambiar_estado'])) {
    $id = intval($_POST['usuario_id']);
    $nuevo_estado = intval($_POST['nuevo_estado']);
    if ($nuevo_estado === 0) {
        // Verificar cuántos usuarios activos hay
        $resActivos = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
        $rowActivos = $resActivos ? $resActivos->fetch_assoc() : null;
        if ($rowActivos && $rowActivos['total'] <= 1) {
            $mensaje = 'Debe haber al menos un usuario activo en el sistema.';
        } else {
            $conn->query("UPDATE usuarios SET activo = 0 WHERE id = $id");
        }
    } else {
        $conn->query("UPDATE usuarios SET activo = 1 WHERE id = $id");
    }
}
// Actualizar contraseña
if (isset($_POST['actualizar_password'])) {
    $id = intval($_POST['usuario_id']);
    $nueva = md5($_POST['nueva_password']);
    $conn->query("UPDATE usuarios SET password = '$nueva' WHERE id = $id");
    $mensaje = 'Contraseña actualizada.';
}
// Listar usuarios
$usuarios = [];
$res = $conn->query("SELECT id, nombre, usuario, email, activo FROM usuarios ORDER BY nombre ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $usuarios[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
    body.dark-mode { background-color: #181a1b !important; color: #f1f1f1 !important; }
    .card.dark-mode { background-color: #23272b !important; color: #f1f1f1 !important; }
    .btn-darkmode { position: fixed; top: 10px; right: 10px; z-index: 9999; }
    table.dark-mode, table.dark-mode th, table.dark-mode td { background-color: #23272b !important; color: #f1f1f1 !important; border-color: #444 !important; }
    .badge.bg-success { background-color: #28a745 !important; }
    .badge.bg-danger { background-color: #dc3545 !important; }
    </style>
</head>
<body class="bg-light" id="body">
    <button class="btn btn-warning btn-darkmode" onclick="toggleDarkMode()" id="darkBtn">🌙</button>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header text-center">
                        <h4>Gestión de Usuarios</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje) { echo '<div class="alert alert-info">' . $mensaje . '</div>'; } ?>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td><?php echo $u['id']; ?></td>
                                        <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($u['usuario']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <?php if ($u['activo']): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="nuevo_estado" value="<?php echo $u['activo'] ? 0 : 1; ?>">
                                                <button type="submit" name="cambiar_estado" class="btn btn-sm <?php echo $u['activo'] ? 'btn-danger' : 'btn-success'; ?>">
                                                    <?php echo $u['activo'] ? 'Desactivar' : 'Activar'; ?>
                                                </button>
                                            </form>
                                            <button class="btn btn-sm btn-warning" onclick="mostrarFormPass(<?php echo $u['id']; ?>)">Actualizar Contraseña</button>
                                            <form method="POST" id="formPass<?php echo $u['id']; ?>" style="display:none; margin-top:5px;">
                                                <input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">
                                                <div class="input-group input-group-sm">
                                                    <input type="password" name="nueva_password" class="form-control" placeholder="Nueva contraseña" required>
                                                    <button type="submit" name="actualizar_password" class="btn btn-primary">Guardar</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="dashboard.php" class="btn btn-secondary w-100">Volver al Panel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    function mostrarFormPass(id) {
        document.getElementById('formPass'+id).style.display = 'block';
    }
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
</body>
</html>
