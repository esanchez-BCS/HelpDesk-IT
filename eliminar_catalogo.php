<?php
// 1. Incluimos la conexión a la base de datos
include "conexion.php"; 

// 2. Recibimos los datos que enviamos por la URL (GET)
// 'tabla' nos dice si es storerooms o unidades_medida
// 'id' nos dice cuál registro específico borrar
if (isset($_GET['tabla']) && isset($_GET['id'])) {
    
    $tabla = $_GET['tabla'];
    $id = $_GET['id'];

    // 3. Creamos la consulta para borrar
    // Usamos las variables que recibimos para que sirva para ambas tablas
    $sql = "DELETE FROM $tabla WHERE id = '$id'";

    // 4. Ejecutamos la consulta
    if (mysqli_query($conexion, $sql)) {
        // Si sale bien, regresamos al catálogo con un mensaje de éxito
        header("Location: Index.php?vista=catalogos&status=added");
    } else {
        // Si hay un error (por ejemplo, si el almacén tiene insumos dentro), nos avisa
        echo "Error al eliminar: " . mysqli_error($conexion);
    }

} else {
    // Si alguien intenta entrar al archivo sin mandar datos, lo mandamos de regreso
    header("Location: Index.php?vista=catalogo");
}
?>