<?php
session_start();
include 'conexion.php';

// Si no hay sesión, pa' fuera
if (!isset($_SESSION['nombre'])) {
    header("Location: acceso.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Capturar todos los campos del nuevo formulario
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre_equipo']);
    $ubicacion = mysqli_real_escape_string($conexion, $_POST['ubicacion']);
    $tipo = mysqli_real_escape_string($conexion, $_POST['tipo']);
    $marca = mysqli_real_escape_string($conexion, $_POST['marca']);
    $modelo = mysqli_real_escape_string($conexion, $_POST['modelo'] ?? '');
    $serie = mysqli_real_escape_string($conexion, $_POST['serie']);
    $estado = mysqli_real_escape_string($conexion, $_POST['estado']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? '');
    $proveedores = mysqli_real_escape_string($conexion, $_POST['proveedores'] ?? '');
    $url = mysqli_real_escape_string($conexion, $_POST['url'] ?? '');

    // Parent ID: Si no seleccionan nada, lo guardamos como NULL
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : "NULL";

    // 2. Autocompletar la auditoría
    $fecha_ahora = date('Y-m-d H:i:s');
    $usuario = mysqli_real_escape_string($conexion, $_SESSION['nombre']);

    // 3. ¡Inyectar en la base de datos!
    $sql = "INSERT INTO assets (
                nombre_equipo, ubicacion, tipo, marca, modelo, serie, estado, 
                descripcion, proveedores, url, parent_id, creado_por, fecha_creacion
            ) VALUES (
                '$nombre', '$ubicacion', '$tipo', '$marca', '$modelo', '$serie', '$estado', 
                '$descripcion', '$proveedores', '$url', $parent_id, '$usuario', '$fecha_ahora'
            )";

    if (mysqli_query($conexion, $sql)) {
        echo "<script>
                alert('📦 ¡Equipo registrado con éxito en el inventario!');
                window.location.href='Index.php?vista=assets';
              </script>";
    } else {
        echo "<script>
                alert('⚠️ Error al registrar: " . mysqli_error($conexion) . "');
                window.history.back();
              </script>";
    }
} else {
    header("Location: Index.php?vista=assets");
}
?>