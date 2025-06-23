<?php
// Archivo de conexión a la base de datos MySQL
$host = 'localhost';
$usuario = 'root';
$password = '';
$base_datos = 'posweb';
$puerto = 3306;

$conn = new mysqli($host, $usuario, $password, $base_datos, $puerto);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
?>
