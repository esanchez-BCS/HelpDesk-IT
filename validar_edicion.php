<?php
include 'conexion.php';

$folio = intval($_GET['folio']);
$accion = $_GET['accion'];

if($accion == 'aprobar'){
    mysqli_query($conexion, "UPDATE solicitudes_pos SET permiso_edicion = 'Aprobado' WHERE id = $folio");
    $msg = "Edición aprobada para el folio #$folio";
} else {
    mysqli_query($conexion, "UPDATE solicitudes_pos SET permiso_edicion = 'Bloqueado' WHERE id = $folio");
    $msg = "Edición rechazada";
}

echo "<h1>$msg</h1><p>Ya puedes cerrar esta ventana.</p>";