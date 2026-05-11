<?php
// Usamos __DIR__ para que Windows no se pierda al buscar los archivos en segundo plano
include __DIR__ . '/conexion.php';
include_once __DIR__ . '/enviar_notificacion.php';

// Configuramos la zona horaria (Asegúrate de poner la tuya)
date_default_timezone_set('America/Mazatlan'); 

// 1. Buscamos todos los tickets cuya fecha ya llegó o ya pasó, y que NO hayan sido notificados
$sql = "SELECT id, asunto, asignado_a, fecha_programada 
        FROM tickets 
        WHERE fecha_programada IS NOT NULL 
        AND fecha_programada != '0000-00-00 00:00:00' 
        AND fecha_programada <= NOW() 
        AND notificacion_enviada = 0";

$res = mysqli_query($conexion, $sql);

if(mysqli_num_rows($res) > 0) {
    while($ticket = mysqli_fetch_assoc($res)) {
        $id_ticket = $ticket['id'];
        $asunto = $ticket['asunto'];
        $asignado_a = $ticket['asignado_a'];
        
        // --- 2. ENVIAR NOTIFICACIÓN AL TÉCNICO ---
        if ($asignado_a != 'Sin asignar' && !empty(trim($asignado_a))) {
            $tecnicos = explode(', ', $asignado_a);
            foreach($tecnicos as $t_nombre) {
                $t_nombre_limpio = mysqli_real_escape_string($conexion, trim($t_nombre));
                $res_t = mysqli_query($conexion, "SELECT correo FROM usuarios WHERE nombre = '$t_nombre_limpio' LIMIT 1");
                if($row_t = mysqli_fetch_assoc($res_t)) {
                    if (!empty($row_t['correo'])) {
                         $cuerpo_t = "<h2>⏰ ¡Es hora del Mantenimiento!</h2>
                                     <p>El ticket programado <strong>#$id_ticket - $asunto</strong> ha llegado a su fecha de inicio.</p>
                                     <p>Por favor, revisa tu panel de control para comenzar a trabajarlo.</p>";
                         enviarEmail($row_t['correo'], "⏰ Recordatorio: Ticket #$id_ticket", $cuerpo_t);
                    }
                }
            }
        }
        
        // --- 3. MARCAR COMO "YA AVISADO" PARA NO SPAMEAR ---
        mysqli_query($conexion, "UPDATE tickets SET notificacion_enviada = 1 WHERE id = $id_ticket");
    }
    echo "Robot ejecutado: Correos enviados con éxito.";
} else {
    echo "Robot ejecutado: Nada pendiente por notificar.";
}
?>