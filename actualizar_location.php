<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Capturamos los datos
    $id = intval($_POST['id']);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre_ubicacion']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? '');
    
    // Si no se selecciona un padre, el valor debe ser NULL
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : "NULL";

    // 2. MAGIA AUTOMÁTICA: Fecha de actualización
    $fecha_actual = date('Y-m-d H:i:s'); 

    // 3. Ejecutamos el UPDATE
    // Nota: Solo actualizamos 'updated_at', 'created_at' y 'created_by' se quedan intactos para saber el origen
    $sql = "UPDATE locations SET 
            nombre_ubicacion = '$nombre', 
            parent_id = $parent_id, 
            descripcion = '$descripcion', 
            updated_at = '$fecha_actual' 
            WHERE id = $id";
            
    if (mysqli_query($conexion, $sql)) {
        // Redirigimos con un mensaje de éxito (opcional)
        header("Location: Index.php?vista=locations&status=updated");
        exit();
    } else {
        echo "Error al actualizar la ubicación: " . mysqli_error($conexion);
    }
} else {
    header("Location: Index.php?vista=locations");
}
?>