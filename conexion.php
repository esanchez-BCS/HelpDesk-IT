<?php
$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "tickets_db";

$conexion = mysqli_connect($servidor, $usuario, $password, $base_datos);

if (!$conexion) {
    die("<div style='padding:20px; background:#f53939; color:white; font-family:sans-serif; text-align:center;'>
            <h2>❌ Error de Conexión</h2>
            <p>No pudimos conectar con la base de datos. Verifica que XAMPP esté encendido.</p>
            <small>" . mysqli_connect_error() . "</small>
         </div>");
}
?>
