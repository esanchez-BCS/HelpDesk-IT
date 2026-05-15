<?php
// aprobar_edicion.php
include 'conexion.php';
include 'enviar_notificacion_solicitudes.php';

if(isset($_GET['folio'])){
    $folio = intval($_GET['folio']);
    
    // 1. Cambiamos el estado a Aprobado
    mysqli_query($conexion, "UPDATE solicitudes_pos SET permiso_edicion = 'Aprobado' WHERE id = $folio");
    
    // 2. Buscamos los datos del usuario para avisarle
    $res = mysqli_query($conexion, "SELECT s.*, p.nombre, p.correo FROM solicitudes_pos s JOIN personal_acre p ON s.personal_id = p.id WHERE s.id = $folio");
    $data = mysqli_fetch_assoc($res);

    // 3. Enviamos el correo de confirmación
    if (function_exists('enviarCorreoSolicitud')) {
        $datos_mail = [
            'folio' => $folio,
            'nombre_solicitante' => $data['nombre'],
            'correo_solicitante' => $data['correo'],
            'fecha' => date('d/m/Y')
        ];
        // Enviamos un mensaje especial de "APROBADO"
        enviarCorreoSolicitud($datos_mail, [], "EDICIÓN APROBADA - YA PUEDES MODIFICAR", []);
    }

    echo "<script>alert('Edición aprobada y correo enviado.'); window.location.href='ver_detalle_solicitud.php?folio=$folio';</script>";
}