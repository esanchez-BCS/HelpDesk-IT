<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['nombre'])) {
    header("Location: acceso.php");
    exit();
}

// ==========================================
// 🗑️ LÓGICA PARA ELIMINAR
// ==========================================
if (isset($_GET['eliminar']) && isset($_GET['tabla']) && isset($_GET['id'])) {
    $tabla = mysqli_real_escape_string($conexion, $_GET['tabla']);
    $id = intval($_GET['id']);
    
    $tablas_permitidas = ['storerooms', 'unidades_medida', 'errores_comunes', 'usuarios'];
    if (in_array($tabla, $tablas_permitidas)) {
        mysqli_query($conexion, "DELETE FROM $tabla WHERE id = $id");
    }
    
    header("Location: Index.php?vista=catalogos&tipo=$tabla");
    exit();
}

// ==========================================
// 💾 LÓGICA PARA CREAR / EDITAR
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion']; 
    $tabla  = mysqli_real_escape_string($conexion, $_POST['tabla']);
    
    if ($tabla == 'storerooms' || $tabla == 'unidades_medida') {
        $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
        if ($accion == 'nuevo') {
            $sql = "INSERT INTO $tabla (nombre) VALUES ('$nombre')";
        } else {
            $id = intval($_POST['id']);
            $sql = "UPDATE $tabla SET nombre = '$nombre' WHERE id = $id";
        }
    } 
    
    elseif ($tabla == 'errores_comunes') {
        $tipo = mysqli_real_escape_string($conexion, $_POST['tipo_equipo']);
        $desc = mysqli_real_escape_string($conexion, $_POST['descripcion_error']);
        if ($accion == 'nuevo') {
            $sql = "INSERT INTO errores_comunes (tipo_equipo, descripcion_error) VALUES ('$tipo', '$desc')";
        } else {
            $id = intval($_POST['id']);
            $sql = "UPDATE errores_comunes SET tipo_equipo = '$tipo', descripcion_error = '$desc' WHERE id = $id";
        }
    } 
    
    elseif ($tabla == 'usuarios') {
        $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
        $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
        $depto  = mysqli_real_escape_string($conexion, $_POST['departamento']);
        $rol    = mysqli_real_escape_string($conexion, $_POST['rol']);
        $password_plana = $_POST['password'] ?? ''; // Atrapamos lo que escribió en contraseña
        
        if ($accion == 'nuevo') {
            // CIFRAMOS la contraseña nueva de forma segura
            $password_cifrada = password_hash($password_plana, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nombre, correo, departamento, password, rol) VALUES ('$nombre', '$correo', '$depto', '$password_cifrada', '$rol')";
        } else {
            $id = intval($_POST['id']);
            
            if (!empty($password_plana)) {
                // 1. Obtener la contraseña actual encriptada de la base de datos
                $res_user = mysqli_query($conexion, "SELECT password FROM usuarios WHERE id = $id");
                $user_db = mysqli_fetch_assoc($res_user);
                
                // 2. Verificar si la que escribieron es IGUAL a la actual
                if (password_verify($password_plana, $user_db['password'])) {
                    // Son iguales, no la actualizamos para ahorrar recursos
                    $sql = "UPDATE usuarios SET nombre = '$nombre', correo = '$correo', departamento = '$depto', rol = '$rol' WHERE id = $id";
                } else {
                    // Es diferente, la CIFRAMOS y la actualizamos
                    $password_cifrada = password_hash($password_plana, PASSWORD_DEFAULT);
                    $sql = "UPDATE usuarios SET nombre = '$nombre', correo = '$correo', departamento = '$depto', rol = '$rol', password = '$password_cifrada' WHERE id = $id";
                }
            } else {
                // El campo vino vacío, actualizamos solo la info, SIN tocar la contraseña
                $sql = "UPDATE usuarios SET nombre = '$nombre', correo = '$correo', departamento = '$depto', rol = '$rol' WHERE id = $id";
            }
        }
    }

    // Ejecutamos y regresamos
    if (mysqli_query($conexion, $sql)) {
        header("Location: Index.php?vista=catalogos&tipo=$tabla");
    } else {
        echo "Error: " . mysqli_error($conexion);
    }
}
?>