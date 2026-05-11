<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
    $password = $_POST['password'];

    // Buscamos al usuario
    $sql = "SELECT * FROM usuarios WHERE correo = '$correo'";
    $resultado = mysqli_query($conexion, $sql);

    if (mysqli_num_rows($resultado) > 0) {
        $usuario = mysqli_fetch_assoc($resultado);
        
        // --- 🛡️ CANDADO DE SEGURIDAD ---
        // Extraemos el estado de la base de datos (Si es NULL o no existe, asumimos Activo para no romper cuentas viejas)
        $estado_actual = isset($usuario['estado_cuenta']) ? $usuario['estado_cuenta'] : 'Activo';
        
        if ($estado_actual == 'Pendiente') {
            echo "<script>alert('Tu cuenta aún está pendiente de aprobación por el equipo de IT. Por favor, espera el correo de confirmación.'); window.location.href='acceso.php';</script>";
            exit();
        } elseif ($estado_actual == 'Rechazado') {
            echo "<script>alert('Tu solicitud de acceso fue rechazada. Contacta a IT para más información.'); window.location.href='acceso.php';</script>";
            exit();
        }
        // -------------------------------
        
        // Verificamos la contraseña
        if (password_verify($password, $usuario['password'])) {
            // REGISTRAMOS LAS VARIABLES DE SESIÓN
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre']; 
            $_SESSION['rol'] = $usuario['rol']; 

            // Redirigir al Index.php
            header("Location: Index.php"); 
            exit();
        } else {
            echo "<script>alert('Contraseña incorrecta'); window.location.href='acceso.php';</script>";
        }
    } else {
        echo "<script>alert('El correo no está registrado'); window.location.href='acceso.php';</script>";
    }
}
?>