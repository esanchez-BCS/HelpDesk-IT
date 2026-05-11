<?php
include 'conexion.php';

$ubicacion = mysqli_real_escape_string($conexion, $_GET['ubicacion'] ?? '');

// Buscamos los equipos que pertenezcan a la ubicación seleccionada
$sql = "SELECT id, nombre_equipo, tipo, marca FROM assets WHERE ubicacion = '$ubicacion' ORDER BY nombre_equipo ASC";
$res = mysqli_query($conexion, $sql);

$equipos = [];
while($row = mysqli_fetch_assoc($res)) {
    $equipos[] = $row;
}

header('Content-Type: application/json');
echo json_encode($equipos);
?>