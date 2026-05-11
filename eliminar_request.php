<?php
session_start();
include 'conexion.php';

// Verificamos que el usuario tenga sesión iniciada
if (!isset($_SESSION['nombre'])) {
    header("Location: acceso.php");
    exit();
}

if (isset($_GET['id'])) {
    $id_ticket = intval($_GET['id']);
    
    // 1. Primero, borramos cualquier tarea del checklist asociada a este ticket para no dejar basura
    $sql_check = "DELETE FROM ticket_checklist WHERE ticket_id = $id_ticket";
    mysqli_query($conexion, $sql_check);
    
    // 2. Ahora sí, borramos el ticket (asegurándonos de que solo se puedan borrar Requests por seguridad)
    $sql_ticket = "DELETE FROM tickets WHERE id = $id_ticket AND estado = 'Request'";
    
    if (mysqli_query($conexion, $sql_ticket)) {
        echo "<script>
                alert('🗑️ El reporte fue rechazado y eliminado exitosamente de la bandeja.');
                window.location.href='Index.php?vista=requests';
              </script>";
    } else {
        echo "<script>
                alert('Error al intentar eliminar: " . mysqli_error($conexion) . "');
                window.location.href='Index.php?vista=requests';
              </script>";
    }
} else {
    header("Location: Index.php?vista=requests");
}
?>