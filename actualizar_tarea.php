<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $completada = intval($_POST['completada']); // Será 1 (marcado) o 0 (desmarcado)
    
    $sql = "UPDATE ticket_checklist SET completada = $completada WHERE id = $id";
    if(mysqli_query($conexion, $sql)) {
        echo "Exito";
    } else {
        echo "Error";
    }
}
?>