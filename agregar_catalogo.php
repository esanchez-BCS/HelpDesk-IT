<?php
include "conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tabla = $_POST['tabla'];
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);

    // Insertamos el nuevo elemento
    $sql = "INSERT INTO $tabla (nombre) VALUES ('$nombre')";

    if (mysqli_query($conexion, $sql)) {
        // En agregar_catalogo.php, busca el header y cámbialo a:
            header("Location: Index.php?vista=catalogos&status=added");
    } else {
        echo "Error: " . mysqli_error($conexion);
    }
}
?>