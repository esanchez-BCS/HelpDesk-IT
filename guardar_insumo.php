<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Captura de datos con limpieza para evitar errores de SQL
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $n_parte = mysqli_real_escape_string($conexion, $_POST['n_parte']);
    $categoria = mysqli_real_escape_string($conexion, $_POST['categoria']);
    
    // 2. Valores numéricos (usamos intval y floatval para asegurar que sean números)
    $stock_actual = intval($_POST['stock']);
    $stock_minimo = intval($_POST['stock_minimo']);
    $stock_deseado = intval($_POST['stock_deseado']);
    $costo = floatval($_POST['costo']);
    $id_unidad = intval($_POST['id_unidad']);
    $id_storeroom = intval($_POST['id_storeroom']);

    // 3. El nuevo INSERT que cubre los 11 campos de tu tabla actualizada
    $sql = "INSERT INTO consumibles (
                nombre, 
                descripcion, 
                stock_actual, 
                stock_minimo, 
                stock_deseado, 
                categoria, 
                costo_unitario, 
                id_unidad, 
                id_storeroom, 
                n_parte
            ) VALUES (
                '$nombre', 
                '$descripcion', 
                $stock_actual, 
                $stock_minimo, 
                $stock_deseado, 
                '$categoria', 
                $costo, 
                $id_unidad, 
                $id_storeroom, 
                '$n_parte'
            )";

    if (mysqli_query($conexion, $sql)) {
        // Si todo sale bien, regresamos a la vista de consumibles
        header("Location: Index.php?vista=consumibles");
        exit();
    } else {
        // Si hay un error, nos dirá exactamente qué pasó
        echo "Error al guardar el insumo: " . mysqli_error($conexion);
    }
}
?>