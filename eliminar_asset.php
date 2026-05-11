<?php
session_start();
include 'conexion.php';

// 🔒 Seguridad: Verificamos que el usuario tenga sesión activa
if (!isset($_SESSION['nombre'])) {
    header("Location: acceso.php");
    exit();
}

// 🎯 Verificamos si recibimos el ID por la URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // 🔍 Paso extra: Verificamos si este equipo es "Padre" de otros (ej: una Laptop que tiene un Mouse vinculado)
    $check_hijos = mysqli_query($conexion, "SELECT id FROM assets WHERE parent_id = $id");
    
    if (mysqli_num_rows($check_hijos) > 0) {
        // Si tiene hijos, primero "desvinculamos" a los hijos poniendo su parent_id en NULL
        // para que no se rompa la base de datos
        mysqli_query($conexion, "UPDATE assets SET parent_id = NULL WHERE parent_id = $id");
    }

    // 🗑️ Ahora sí, procedemos a borrar el equipo
    $sql = "DELETE FROM assets WHERE id = $id";
    
    if(mysqli_query($conexion, $sql)){
        // Éxito: Mandamos una alerta bonita y regresamos al inventario
        echo "<script>
                alert('🗑️ Equipo eliminado del inventario con éxito.');
                window.location.href='Index.php?vista=assets';
              </script>";
    } else {
        // Error: Mostramos qué falló
        echo "<script>
                alert('⚠️ Error al eliminar el equipo: " . mysqli_error($conexion) . "');
                window.location.href='Index.php?vista=assets';
              </script>";
    }
} else {
    // Si entran al archivo sin ID, los regresamos al inicio
    header("Location: Index.php?vista=assets");
}
?>