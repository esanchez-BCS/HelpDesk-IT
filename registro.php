<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuarios - Help Desk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #4318ff; --bg: #f4f7fe; --text: #2b3674; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 100%; max-width: 450px; }
        h2 { color: var(--primary); text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; color: var(--text); }
        input, select { width: 100%; padding: 12px; border: 1px solid #d1d9e6; border-radius: 10px; box-sizing: border-box; }
        .btn { background: var(--primary); color: white; border: none; padding: 15px; border-radius: 10px; width: 100%; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn:hover { background: #3310cc; }
        .link { text-align: center; margin-top: 20px; font-size: 14px; }
        .link a { color: var(--primary); text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h2><i class="fa fa-user-plus"></i> Crear Cuenta</h2>
        <form action="procesar_registro.php" method="POST">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre" placeholder="Ej. Juan Pérez" required>
            </div>
            <div class="form-group">
                <label>Correo Electrónico</label>
                <input type="email" name="correo" placeholder="correo@empresa.com" required>
            </div>
            <div class="form-group">
                <label>Departamento</label>
                <input type="text" name="departamento" placeholder="Sistemas, Ventas, etc." required>
            </div>
            <div class="form-group">
                <label>Rol</label>
                <select name="rol" required>
                    <option value="usuario">Usuario (Solicitante)</option>
                    <option value="tecnico">Técnico</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn">Registrar Usuario</button>
        </form>
        <div class="link">¿Ya tienes cuenta? <a href="acceso.php">Inicia sesión</a></div>
    </div>
</body>
</html>