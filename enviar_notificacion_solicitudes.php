<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Reemplaza el require 'vendor/autoload.php'; por esto:
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function enviarCorreoSolicitud($datos, $botones, $tipo_accion, $imagenes = []) {
    $mail = new PHPMailer(true);

    try {
        // --- CONFIGURACIÓN DEL SERVIDOR (Usa tus datos reales) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sticketit@gmail.com';
        $mail->Password   = 'gthu ruyc xjsk cyrl';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // --- DESTINATARIOS ---
        $mail->setFrom('sticketit@gmail.com', 'Sistema HelpDesk IT');
        $mail->addAddress($datos['correo_solicitante']); // El que eligió el usuario
        $mail->addAddress('it@acreresort.com');      // Correo de IT
        $mail->addCC('esanchez@acreresort.com'); // Tu correo de grupo (CC)

        // --- PROCESAR IMÁGENES PARA QUE SE VEAN EN EL CUERPO ---
        $html_imagenes = "";
        foreach ($imagenes as $index => $ruta) {
            if (file_exists($ruta)) {
                $cid = "img_pos_" . $index;
                $mail->addEmbeddedImage($ruta, $cid);
                $html_imagenes .= "<div style='margin:10px;'><img src='cid:$cid' style='max-width:500px; border-radius:10px;'></div>";
            }
        }

        // --- CUERPO DEL MENSAJE ---
        $mail->isHTML(true);
        $mail->Subject = "[$tipo_accion] Folio #{$datos['folio']} - Portal POS";
        
        // Construimos la tabla de botones para el correo
        $tabla_botones = "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%;'>
                            <tr style='background:#4318ff; color:white;'>
                                <th>Botón</th><th>Precio</th><th>Major</th><th>Family</th>
                            </tr>";
        foreach ($botones as $b) {
            $tabla_botones .= "<tr>
                                <td>{$b['nombre']}</td>
                                <td>$".number_format($b['precio'], 2)."</td>
                                <td>{$b['major']}</td>
                                <td>{$b['family']}</td>
                               </tr>";
        }
        $tabla_botones .= "</table>";



        $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2 style='color:#4318ff;'>Notificación de Solicitud de Botones</h2>
            <p>Se ha realizado un movimiento tipo: <b>$tipo_accion</b></p>
            <p><b>Folio:</b> #{$datos['folio']}<br>
               <b>Solicitante:</b> {$datos['nombre_solicitante']}<br>
               <b>Fecha:</b> {$datos['fecha']}</p>
            
            <h3>Detalle de Botones:</h3>
            $tabla_botones
            
            <h3>Imágenes Adjuntas:</h3>
            $html_imagenes
            

            
            <div style='margin-top: 30px; padding: 20px; background: #f9f9f9; text-align: center; border-radius: 10px;'>
                <p>¿Autorizas que el usuario realice cambios?</p>
                <a href='http://localhost/SistemaTickets/validar_edicion.php?folio={$datos['folio']}&accion=aprobar' 
                   style='background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-right: 10px;'>APROBAR EDICIÓN</a>
                
                <a href='http://localhost/SistemaTickets/validar_edicion.php?folio={$datos['folio']}&accion=rechazar' 
                   style='background: #dc3545; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>RECHAZAR</a>
            </div>
            <hr>

            <p style='font-size:12px; color:#888;'>Este es un correo automático, no responder.</p>


        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: {$mail->ErrorInfo}");
        return false;
    }
}

// --- NUEVA FUNCIÓN PARA NOTIFICAR EDICIONES CON ANTES Y DESPUÉS ---
function enviarCorreoEdicion($datos, $botones_viejos, $botones_nuevos) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sticketit@gmail.com';
        $mail->Password   = 'gthu ruyc xjsk cyrl';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Destinatarios
        $mail->setFrom('sticketit@gmail.com', 'Sistema HelpDesk IT');
        $mail->addAddress($datos['correo_solicitante']); 
        $mail->addAddress('it@acreresort.com');
        $mail->addCC('esanchez@acreresort.com');

        $mail->isHTML(true);
        $mail->Subject = "[EDICIÓN] Folio #{$datos['folio']} - Modificado por el Solicitante";

        // Tablas
        $tabla_viejos = "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%; margin-bottom: 20px;'>
                            <tr style='background:#d9534f; color:white;'><th>Botón</th><th>Precio</th><th>Major</th><th>Family</th></tr>";
        foreach ($botones_viejos as $b) {
            $tabla_viejos .= "<tr><td>{$b['nombre']}</td><td>$".number_format($b['precio'], 2)."</td><td>{$b['major']}</td><td>{$b['family']}</td></tr>";
        }
        $tabla_viejos .= "</table>";

        $tabla_nuevos = "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%;'>
                            <tr style='background:#01b574; color:white;'><th>Botón</th><th>Precio</th><th>Major</th><th>Family</th></tr>";
        foreach ($botones_nuevos as $b) {
            $tabla_nuevos .= "<tr><td>{$b['nombre']}</td><td>$".number_format($b['precio'], 2)."</td><td>{$b['major']}</td><td>{$b['family']}</td></tr>";
        }
        $tabla_nuevos .= "</table>";

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2 style='color:#ffb800;'>Modificación de Solicitud de Botones</h2>
            <p>El usuario <b>{$datos['nombre_solicitante']}</b> ha editado su solicitud desde el portal.</p>
            <p><b>Folio:</b> #{$datos['folio']}<br>
               <b>Fecha de Edición:</b> {$datos['fecha']}</p>
            
            <h3 style='color:#d9534f;'>VERSIÓN ANTERIOR (Lo que se borró):</h3>
            $tabla_viejos
            
            <h3 style='color:#01b574;'>NUEVA VERSIÓN (Cómo quedó):</h3>
            $tabla_nuevos
            <hr>
            <p style='font-size:12px; color:#888;'>Este es un correo automático, no responder.</p>
        </div>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar correo de edición: {$mail->ErrorInfo}");
    }
}