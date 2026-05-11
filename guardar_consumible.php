<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['nombre'])) {
    header("Location: acceso.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Atrapamos todos los datos de texto y números
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $n_parte = mysqli_real_escape_string($conexion, $_POST['n_parte'] ?? '');
    $categoria = mysqli_real_escape_string($conexion, $_POST['categoria'] ?? '');
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? '');
    $proveedores = mysqli_real_escape_string($conexion, $_POST['proveedores'] ?? '');
    $url = mysqli_real_escape_string($conexion, $_POST['url'] ?? '');

    $no_stock = intval($_POST['no_stock'] ?? 0);
    $stock_actual = intval($_POST['stock_actual'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
    $stock_deseado = intval($_POST['stock_deseado'] ?? 0);
    $lead_time_dias = intval($_POST['lead_time_dias'] ?? 0);
    
    $costo_unitario = floatval($_POST['costo_unitario'] ?? 0.00);

    // 2. Atrapamos los IDs foráneos (Si vienen vacíos, les ponemos NULL)
    $id_unidad = !empty($_POST['id_unidad']) ? intval($_POST['id_unidad']) : "NULL";
    $id_storeroom = !empty($_POST['id_storeroom']) ? intval($_POST['id_storeroom']) : "NULL";
    $id_ubicacion = !empty($_POST['id_ubicacion']) ? intval($_POST['id_ubicacion']) : "NULL";
    $id_asset = !empty($_POST['id_asset']) ? intval($_POST['id_asset']) : "NULL";

    // 3. Auditoría de creación
    $fecha_ahora = date('Y-m-d H:i:s');
    $usuario = mysqli_real_escape_string($conexion, $_SESSION['nombre']);

    // 4. Inserción a la base de datos
    $sql = "INSERT INTO consumibles (
                nombre, n_parte, categoria, descripcion, proveedores, url, 
                no_stock, stock_actual, stock_minimo, stock_deseado, lead_time_dias, costo_unitario,
                id_unidad, id_storeroom, id_ubicacion, id_asset, creado_por, fecha_creacion
            ) VALUES (
                '$nombre', '$n_parte', '$categoria', '$descripcion', '$proveedores', '$url',
                $no_stock, $stock_actual, $stock_minimo, $stock_deseado, $lead_time_dias, $costo_unitario,
                $id_unidad, $id_storeroom, $id_ubicacion, $id_asset, '$usuario', '$fecha_ahora'
            )";

    if (mysqli_query($conexion, $sql)) {
        echo "<script>
                alert('📦 ¡Insumo registrado con éxito en el inventario!');
                window.location.href='Index.php?vista=consumibles';
              </script>";
    } else {
        echo "<script>
                alert('⚠️ Error al registrar: " . mysqli_error($conexion) . "');
                window.history.back();
              </script>";
    }
} else {
    header("Location: Index.php?vista=consumibles");
}
?>