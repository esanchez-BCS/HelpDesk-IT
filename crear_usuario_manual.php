<?php
session_start();
include 'conexion.php'; // Asegúrate de que conexion.php esté en la misma carpeta

// --- 1. SEGURIDAD: Solo el Admin puede ver esta página ---
// Si no hay sesión o el rol no es 'admin', lo mandamos al login
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
    header("Location: acceso.php");
    exit();
}

// --- 2. LÓGICA DE CREACIÓN ---
$mensaje = ""; // Variable para mostrar mensajes de éxito o error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturamos y limpiamos los datos del formulario
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
    $departamento = mysqli_real_escape_string($conexion, $_POST['departamento']);
    
    // Forzamos el rol a minúsculas para que coincida con el ENUM de la BD ('admin', 'tecnico', 'usuario')
    $rol = strtolower(mysqli_real_escape_string($conexion, $_POST['rol']));
    
    // Encriptamos la contraseña de forma segura
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // SQL de Inserción
    $sql = "INSERT INTO usuarios (nombre, correo, password, departamento, rol) 
            VALUES ('$nombre', '$correo', '$password', '$departamento', '$rol')";

    if (mysqli_query($conexion, $sql)) {
        $mensaje = "<div class='alert alert-success'>¡Usuario <strong>$nombre</strong> creado con éxito, mi vida! Ya puede iniciar sesión.</div>";
    } else {
        $mensaje = "<div class='alert alert-danger'>Error técnico: " . mysqli_error($conexion) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario Manualmente - Panel Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4318ff;
            --bg-light: #f4f7fe;
            --text-dark: #2b3674;
            --white: #ffffff;
            --border: #e0e5f2;
            --success: #01b574;
            --danger: #f53939;
        }

        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg-light); display: flex; justify-content: center; align-items: center; height: 100vh; color: var(--text-dark); }
        
        .form-container { background: var(--white); padding: 40px; border-radius: 20px; width: 100%; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); position: relative; }
        
        .btn-back { position: absolute; top: 20px; left: 20px; text-decoration: none; color: var(--primary); font-weight: 600; font-size: 14px; }
        
        h2 { text-align: center; color: var(--primary); margin-bottom: 30px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        input, select { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 12px; box-sizing: border-box; font-family: inherit; }
        
        .btn-primary { background: var(--primary); color: white; border: none; padding: 14px; border-radius: 12px; cursor: pointer; width: 100%; font-weight: bold; font-size: 16px; margin-top: 10px; transition: 0.3s; }
        .btn-primary:hover { background: #3310cc; transform: translateY(-2px); }
        
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert-success { background: #e6f9f1; color: var(--success); border: 1px solid #c3e6cb; }
        .alert-danger { background: #ffe6e6; color: var(--danger); border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <div class="form-container">
        <a href="Index.php" class="btn-back"><i class="fa fa-arrow-left"></i> Volver al Panel</a>
        
        <h2><i class="fa fa-user-plus"></i> Crear Usuario Nuevo</h2>
        
        <?php echo $mensaje; // Aquí se mostrará si se creó o si hubo error ?>
        
        <form action="crear_usuario_manual.php" method="POST">
            
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre" placeholder="Ej: Juan Pérez" required>
            </div>

            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="correo" placeholder="juan.perez@empresa.com" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Departamento</label>
                    <input type="text" name="departamento" placeholder="Ej: Sistemas, Ventas..." required>
                </div>
                
                <div class="form-group">
                    <label>Rol de Usuario</label>
                    <select name="rol" required>
                        <option value="usuario">Usuario (Solicitante)</option>
                        <option value="tecnico">Técnico</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Contraseña Temporal</label>
                <input type="password" name="password" placeholder="••••••••" required>
                <small style="color: #a3aed0; font-size: 12px;">Dale esta clave al usuario para su primer ingreso.</small>
            </div>
            
            <button type="submit" class="btn-primary">Crear y Guardar Usuario</button>
        </form>
    </div>

</body>
</html>