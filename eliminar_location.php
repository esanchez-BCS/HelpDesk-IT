<?php
session_start();
include 'conexion.php';

// Verificamos sesión por seguridad
if (!isset($_SESSION['nombre'])) {
    header("Location: acceso.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Ejecutamos el borrado
    $sql = "DELETE FROM locations WHERE id = $id";
    
    if(mysqli_query($conexion, $sql)){
        echo "<script>
                alert('🗑️ Ubicación eliminada con éxito.');
                window.location.href='Index.php?vista=locations';
              </script>";
    } else {
        // Por si intentas borrar una Zona Padre que aún tiene hijos adentro
        echo "<script>
                alert('⚠️ Error al eliminar: " . mysqli_error($conexion) . "');
                window.location.href='Index.php?vista=locations';
              </script>";
    }
} else {
    header("Location: Index.php?vista=locations");
}
?>