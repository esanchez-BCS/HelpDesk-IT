<?php
include 'conexion.php';

$tipo = mysqli_real_escape_string($conexion, $_GET['tipo'] ?? '');

$sql = "SELECT id, descripcion_error FROM errores_comunes WHERE tipo_equipo = '$tipo' ORDER BY descripcion_error ASC";
$res = mysqli_query($conexion, $sql);

$errores = [];
while($row = mysqli_fetch_assoc($res)) {
    $errores[] = $row;
}

header('Content-Type: application/json');
echo json_encode($errores);
?>