<?php
session_start();
session_unset(); // Libera todas las variables de sesión
session_destroy(); // Destruye la sesión
header("Location: acceso.php"); // Redirige al inicio
exit();
?>