<?php
session_start();
include 'conexion.php';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    mysqli_query($conexion, "DELETE FROM consumibles WHERE id = $id");
}
header("Location: Index.php?vista=consumibles");
?>