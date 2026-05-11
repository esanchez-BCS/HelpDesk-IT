<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Capturamos los datos
    $id = intval($_POST['id']);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre_equipo']);
    $ubicacion = mysqli_real_escape_string($conexion, $_POST['ubicacion']);
    $tipo = mysqli_real_escape_string($conexion, $_POST['tipo']);
    $marca = mysqli_real_escape_string($conexion, $_POST['marca']);
    $modelo = mysqli_real_escape_string($conexion, $_POST['modelo']);
    $serie = mysqli_real_escape_string($conexion, $_POST['serie']);
    $estado = mysqli_real_escape_string($conexion, $_POST['estado']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $proveedores = mysqli_real_escape_string($conexion, $_POST['proveedores']);
    $url = mysqli_real_escape_string($conexion, $_POST['url']);
    
    // Parent ID: Si está vacío va como NULL
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : "NULL";

    // 2. Info automática
    $fecha_ahora = date('Y-m-d H:i:s');
    $usuario = $_SESSION['nombre'] ?? 'Sistema';

    // 3. Ejecutamos el UPDATE
    $sql = "UPDATE assets SET 
            nombre_equipo = '$nombre',
            ubicacion = '$ubicacion',
            tipo = '$tipo',
            marca = '$marca',
            modelo = '$modelo',
            serie = '$serie',
            estado = '$estado',
            descripcion = '$descripcion',
            proveedores = '$proveedores',
            url = '$url',
            parent_id = $parent_id,
            actualizado_por = '$usuario',
            fecha_actualizacion = '$fecha_ahora'
            WHERE id = $id";

    if (mysqli_query($conexion, $sql)) {
        header("Location: Index.php?vista=assets&status=updated");
        exit();
    } else {
        echo "Error al actualizar: " . mysqli_error($conexion);
    }
} else {
    header("Location: Index.php?vista=assets");
}
?>