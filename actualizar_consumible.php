<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['nombre'])) {
    header("Location: acceso.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
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

    // Atrapamos los IDs con soporte para NULL
    $id_unidad = !empty($_POST['id_unidad']) ? intval($_POST['id_unidad']) : "NULL";
    $id_storeroom = !empty($_POST['id_storeroom']) ? intval($_POST['id_storeroom']) : "NULL";
    $id_ubicacion = !empty($_POST['id_ubicacion']) ? intval($_POST['id_ubicacion']) : "NULL";
    $id_asset = !empty($_POST['id_asset']) ? intval($_POST['id_asset']) : "NULL";

    $fecha_ahora = date('Y-m-d H:i:s');
    $usuario = mysqli_real_escape_string($conexion, $_SESSION['nombre']);

    $sql = "UPDATE consumibles SET 
            nombre = '$nombre',
            n_parte = '$n_parte',
            categoria = '$categoria',
            descripcion = '$descripcion',
            proveedores = '$proveedores',
            url = '$url',
            no_stock = $no_stock,
            stock_actual = $stock_actual,
            stock_minimo = $stock_minimo,
            stock_deseado = $stock_deseado,
            lead_time_dias = $lead_time_dias,
            costo_unitario = $costo_unitario,
            id_unidad = $id_unidad,
            id_storeroom = $id_storeroom,
            id_ubicacion = $id_ubicacion,
            id_asset = $id_asset,
            actualizado_por = '$usuario',
            fecha_actualizacion = '$fecha_ahora'
            WHERE id = $id";

    if (mysqli_query($conexion, $sql)) {
        header("Location: Index.php?vista=consumibles&msg=updated");
        exit();
    } else {
        echo "Error al actualizar: " . mysqli_error($conexion);
    }
} else {
    header("Location: Index.php?vista=consumibles");
}
?>