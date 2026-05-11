<?php
include 'conexion.php';
include_once 'enviar_notificacion.php'; // Traemos tu función de correos

// =========================================================================
// 🪄 PARTE 1: CUANDO IT HACE CLIC EN EL CORREO (Aprobar o Rechazar)
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['accion']) && isset($_GET['id'])) {
    $id_usuario = intval($_GET['id']);
    $accion = $_GET['accion'];

    // Buscamos al usuario para saber a quién notificar
    $res = mysqli_query($conexion, "SELECT nombre, correo FROM usuarios WHERE id = $id_usuario");
    if ($usuario = mysqli_fetch_assoc($res)) {
        
        if ($accion == 'aprobar') {
            mysqli_query($conexion, "UPDATE usuarios SET estado_cuenta = 'Activo' WHERE id = $id_usuario");
            
            // Le avisamos al usuario que ya puede entrar
            $cuerpo = "<h2>¡Tu cuenta ha sido aprobada! 🎉</h2>
                       <p>Hola, <strong>{$usuario['nombre']}</strong>. El equipo de IT ha validado tu acceso.</p>
                       <p>Ya puedes iniciar sesión en el portal del HelpDesk.</p>";
            enviarEmail($usuario['correo'], "Cuenta Aprobada - HelpDesk IT Acre", $cuerpo);
            
            echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
                    <h2 style='color:#01b574;'>✅ Usuario Aprobado Exitosamente</h2>
                    <p>Se ha notificado al usuario por correo. Puedes cerrar esta ventana.</p>
                  </div>";

        } elseif ($accion == 'rechazar') {
            mysqli_query($conexion, "UPDATE usuarios SET estado_cuenta = 'Rechazado' WHERE id = $id_usuario");
            
            // Le avisamos al usuario que no pasó
            $cuerpo = "<h2>Actualización de tu solicitud</h2>
                       <p>Hola, <strong>{$usuario['nombre']}</strong>. Lo sentimos, el equipo de IT no ha validado tu acceso al sistema.</p>";
            enviarEmail($usuario['correo'], "Solicitud Rechazada - HelpDesk IT", $cuerpo);
            
            echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
                    <h2 style='color:#f53939;'>❌ Usuario Rechazado</h2>
                    <p>Se ha notificado al usuario por correo. Puedes cerrar esta ventana.</p>
                  </div>";
        }
    } else {
        echo "<h3 style='text-align:center; font-family:sans-serif; margin-top:50px;'>Error: Usuario no encontrado.</h3>";
    }
    exit(); // Detenemos aquí para que no lea la parte de registro
}

// =========================================================================
// 📝 PARTE 2: CUANDO ALGUIEN LLENA EL FORMULARIO DE REGISTRO
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recibimos los datos
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $correo = mysqli_real_escape_string($conexion, $_POST['correo']);
    $depto = mysqli_real_escape_string($conexion, $_POST['departamento']);
    $rol = mysqli_real_escape_string($conexion, $_POST['rol']);
    
    // Encriptamos la contraseña
    $pass_encriptada = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insertamos forzando el estado a 'Pendiente'
    $sql = "INSERT INTO usuarios (nombre, correo, password, departamento, rol, estado_cuenta) 
            VALUES ('$nombre', '$correo', '$pass_encriptada', '$depto', '$rol', 'Pendiente')";

    if (mysqli_query($conexion, $sql)) {
        // Obtenemos el ID del usuario que se acaba de guardar
        $nuevo_id = mysqli_insert_id($conexion);

        // --- 📧 NOTIFICAR A IT PARA APROBACIÓN ---
        $correo_it = 'it@acreresort.com'; 
        
        // CUIDADO AQUÍ: Asegúrate de que "localhost" sea la ruta correcta de tu proyecto
        // Si lo subes a internet, cambia "http://localhost/SistemaTickets/" por "https://tudominio.com/"
        $link_aprobar = "http://localhost/SistemaTickets/procesar_registro.php?id=$nuevo_id&accion=aprobar";
        $link_rechazar = "http://localhost/SistemaTickets/procesar_registro.php?id=$nuevo_id&accion=rechazar";

        $cuerpo_it = "<h2>🛡️ Nueva Solicitud de Acceso</h2>
                      <p>Un usuario quiere registrarse en el HelpDesk:</p>
                      <ul style='font-size:14px; color:#2b3674;'>
                        <li><strong>Nombre:</strong> $nombre</li>
                        <li><strong>Correo:</strong> $correo</li>
                        <li><strong>Departamento:</strong> $depto</li>
                        <li><strong>Rol Solicitado:</strong> $rol</li>
                      </ul>
                      <br>
                      <a href='$link_aprobar' style='padding:12px 20px; background-color:#01b574; color:white; text-decoration:none; border-radius:8px; font-weight:bold; display:inline-block; margin-right:10px;'>✅ Aprobar Acceso</a>
                      <a href='$link_rechazar' style='padding:12px 20px; background-color:#f53939; color:white; text-decoration:none; border-radius:8px; font-weight:bold; display:inline-block;'>❌ Rechazar</a>";
                      
        enviarEmail($correo_it, "Nueva solicitud de acceso: $nombre", $cuerpo_it);

        // Mensaje final para el usuario
        echo "<script>
                alert('¡Registro enviado! Tu cuenta está en revisión por el equipo de IT. Recibirás un correo cuando sea validada.');
                window.location.href='acceso.php';
              </script>";
    } else {
        echo "Error: " . mysqli_error($conexion);
    }
}
?>