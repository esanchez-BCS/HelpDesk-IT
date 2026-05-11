<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function enviarEmail($destinatario, $asunto, $mensaje_html) {
    $mail = new PHPMailer(true);

    try {
        // --- CONFIGURACIÓN DEL SERVIDOR SMTP ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Cambia si usas otro
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sticketit@gmail.com'; // Tu correo emisor
        $mail->Password   = 'gthu ruyc xjsk cyrl'; // Contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- DESTINATARIOS ---
        $mail->setFrom('sticketit@gmail.com', 'HelpDesk IT Acre');
        $mail->addAddress($destinatario);

        // --- CONTENIDO ---
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje_html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// --- NUEVA FUNCIÓN PARA ENVÍO RÁPIDO A MULTIPLES TÉCNICOS ---
function enviarEmailMultiple($destinatarios_array, $asunto, $mensaje_html) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sticketit@gmail.com'; 
        $mail->Password   = 'gthu ruyc xjsk cyrl'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('sticketit@gmail.com', 'HelpDesk IT Acre');
        
        // Nos lo enviamos "a nosotros mismos" como destinatario principal
        $mail->addAddress('sticketit@gmail.com'); 

        // Y metemos a todos los técnicos en Copia Oculta (BCC)
        // Así sale UN SOLO correo hacia Google, y Google lo reparte.
        foreach($destinatarios_array as $correo) {
            $mail->addBCC($correo);
        }

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body    = $mensaje_html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>