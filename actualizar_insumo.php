<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recolección de datos
    $id = intval($_POST['id']);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $n_parte = mysqli_real_escape_string($conexion, $_POST['n_parte']);
    $categoria = mysqli_real_escape_string($conexion, $_POST['categoria']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    
    $stock = intval($_POST['stock']);
    $min = intval($_POST['stock_minimo']);
    $deseado = intval($_POST['stock_deseado']);
    $costo = floatval($_POST['costo']);
    $id_storeroom = intval($_POST['id_storeroom']);

    // 2. Consulta única
    $sql = "UPDATE consumibles SET 
            nombre = '$nombre', 
            n_parte = '$n_parte',
            categoria = '$categoria',
            descripcion = '$descripcion',
            stock_actual = $stock, 
            stock_minimo = $min, 
            stock_deseado = $deseado, 
            costo_unitario = $costo,
            id_storeroom = $id_storeroom
            WHERE id = $id";

    // 3. Ejecución y redirección con mensaje
    if (mysqli_query($conexion, $sql)) {
        // Éxito: Mandamos el parámetro msg=updated
        header("Location: Index.php?vista=consumibles&msg=updated");
        exit(); // Muy importante poner exit() después de un header
    } else {
        // Error: Mandamos el parámetro msg=error y guardamos el log técnico si quieres
        header("Location: Index.php?vista=consumibles&msg=error");
        exit();
    }
}
?>