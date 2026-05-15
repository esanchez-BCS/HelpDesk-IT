<?php
// solicitar_edicion.php
include 'conexion.php';
include 'enviar_notificacion_solicitudes.php'; // 👈 Ahora que tiene .php, entra directo

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $folio = intval($_POST['folio']);
    
    // 1. Marcar en la base de datos que ya se solicitó
    mysqli_query($conexion, "UPDATE solicitudes_pos SET permiso_edicion = 'Solicitado' WHERE id = $folio");
    
    // 2. Traer los datos necesarios para el cuerpo del correo
    $sql = "SELECT s.*, p.nombre as nombre_solicitante, p.correo as correo_solicitante 
            FROM solicitudes_pos s 
            JOIN personal_acre p ON s.personal_id = p.id 
            WHERE s.id = $folio";
    $res = mysqli_query($conexion, $sql);
    $data = mysqli_fetch_assoc($res);

    // Traer la lista de botones para que IT vea qué quieren editar
    $botones = [];
    $res_b = mysqli_query($conexion, "SELECT b.nombre_boton as nombre, b.precio, m.nombre as major, f.nombre as family 
                                      FROM solicitudes_pos_botones b
                                      LEFT JOIN pos_major_groups m ON b.major_group_id = m.id
                                      LEFT JOIN pos_family_groups f ON b.family_group_id = f.id
                                      WHERE b.solicitud_id = $folio");
    while($row_b = mysqli_fetch_assoc($res_b)) { $botones[] = $row_b; }

    // 3. Preparar el envío del correo
    $datos_mail = [
        'folio' => $folio,
        'nombre_solicitante' => $data['nombre_solicitante'],
        'correo_solicitante' => $data['correo_solicitante'],
        'fecha' => date('d/m/Y H:i A'),
        'observaciones' => $data['observaciones']
    ];

    // Procesar las fotos para que la función las incruste
    $imagenes = !empty($data['ruta_evidencia']) ? explode(",", $data['ruta_evidencia']) : [];

    // 🔥 LLAMADA A LA FUNCIÓN MAESTRA
    if (function_exists('enviarCorreoSolicitud')) {
        enviarCorreoSolicitud($datos_mail, $botones, "SOLICITUD DE EDICIÓN", $imagenes);
    } else {
        die("Error: No se encontró la función enviarCorreoSolicitud. Verifica el contenido de enviar_notificacion_solicitudes.php");
    }
    
    echo "<script>alert('Petición enviada a IT. Te avisaremos cuando sea aprobada.'); window.location.href='portal_solicitudes.php';</script>";
}