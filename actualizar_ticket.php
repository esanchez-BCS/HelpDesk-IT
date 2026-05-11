<?php
session_start();
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  
    $id_ticket = mysqli_real_escape_string($conexion, $_POST['id_ticket']);
    $nuevo_estado = mysqli_real_escape_string($conexion, $_POST['nuevo_estado']);
    
    // 🛠️ ¡SOLUCIÓN AL FATAL ERROR! Procesamos la asignación múltiple correctamente
    $asignados_array = isset($_POST['asignado_a']) ? $_POST['asignado_a'] : [];
    if (empty($asignados_array)) {
        $asignado_a = "Sin asignar";
    } else {
        // Juntamos los nombres en una sola cadena separada por comas
        $asignado_a = implode(', ', $asignados_array);
    }
    $asignado_a = mysqli_real_escape_string($conexion, $asignado_a);
    
    // --- LÓGICA DE FECHA (PROTEGIDA) ---
    $fecha_raw = $_POST['nueva_fecha'] ?? '';
    $fecha_sql_part = ""; 

    if (!empty($fecha_raw)) {
        $fecha_obj = DateTime::createFromFormat('d/m/Y H:i', $fecha_raw);
        if($fecha_obj){
            $fecha_formateada = $fecha_obj->format('Y-m-d H:i:s');
            $fecha_sql_part = ", fecha_programada = '$fecha_formateada'";
        } else {
            $fecha_sql_part = ", fecha_programada = '" . mysqli_real_escape_string($conexion, $fecha_raw) . "'";
        }
    }

    $id_insumo = isset($_POST['id_insumo']) ? $_POST['id_insumo'] : ''; 
    $cantidad = isset($_POST['cantidad_usada']) ? intval($_POST['cantidad_usada']) : 0;

    // --- 🛠️ ¡SOLUCIÓN A LOS WARNINGS! Actualizamos solo lo que manda el formulario ---
    if ($nuevo_estado == 'Resuelto') {
        $usuario_cierre = $_SESSION['nombre'];
        $sql_ticket = "UPDATE tickets SET 
                        estado = '$nuevo_estado', 
                        asignado_a = '$asignado_a', 
                        tecnico_cierre = '$usuario_cierre' 
                        $fecha_sql_part
                       WHERE id = '$id_ticket'";
    } else {
        $sql_ticket = "UPDATE tickets SET 
                        estado = '$nuevo_estado', 
                        asignado_a = '$asignado_a' 
                        $fecha_sql_part 
                       WHERE id = '$id_ticket'";
    }

    // Ejecutamos la consulta una sola vez
    if (mysqli_query($conexion, $sql_ticket)) {
        
        // --- LÓGICA DE INVENTARIO ---
        if ($nuevo_estado == 'Resuelto' && !empty($id_insumo) && $cantidad > 0) {
            $res_stock = mysqli_query($conexion, "SELECT stock_actual FROM consumibles WHERE id = '$id_insumo'");
            $fila_stock = mysqli_fetch_assoc($res_stock);

            if ($fila_stock && $fila_stock['stock_actual'] >= $cantidad) {
                mysqli_query($conexion, "UPDATE consumibles SET stock_actual = stock_actual - $cantidad WHERE id = '$id_insumo'");
                mysqli_query($conexion, "INSERT INTO historial_insumos (ticket_id, insumo_id, cantidad) VALUES ('$id_ticket', '$id_insumo', '$cantidad')");
            }
        }

        // --- 📧 NOTIFICACIONES DE ACTUALIZACIÓN ---
        include_once 'enviar_notificacion.php';
        
        // Obtenemos los datos del ticket para poder armar el correo
        $res_info = mysqli_query($conexion, "SELECT solicitante, correo_contacto, asunto, estado FROM tickets WHERE id = '$id_ticket'");
        $info = mysqli_fetch_assoc($res_info);
        
        // 1. Notificar al Solicitante (si dejó correo)
        if (!empty($info['correo_contacto'])) {
            $cuerpo_act = "<h2>Actualización en tu Ticket #$id_ticket</h2>
                           <p>Hola, <strong>{$info['solicitante']}</strong>.</p>
                           <p>Tu reporte (<strong>{$info['asunto']}</strong>) ha cambiado su estado a: <strong>$nuevo_estado</strong>.</p>
                           <p>Puedes ver más detalles ingresando al portal (si tienes cuenta).</p>";
            enviarEmail($info['correo_contacto'], "Actualización de Ticket #$id_ticket", $cuerpo_act);
        }

       // 2. Notificar a los Técnicos Asignados (⚡ VERSIÓN TURBO)
        if ($asignado_a != 'Sin asignar' && !empty(trim($asignado_a))) {
            $tecnicos = explode(', ', $asignado_a);
            $lista_correos_tecnicos = []; // Aquí meteremos todos los correos
            
            // Primero, solo buscamos los correos en la base de datos (Súper rápido)
            foreach($tecnicos as $t_nombre) {
                $t_nombre_limpio = mysqli_real_escape_string($conexion, trim($t_nombre));
                if (!empty($t_nombre_limpio)) {
                    $res_t = mysqli_query($conexion, "SELECT correo FROM usuarios WHERE nombre = '$t_nombre_limpio' LIMIT 1");
                    if($row_t = mysqli_fetch_assoc($res_t)) {
                        if (!empty($row_t['correo'])) {
                             $lista_correos_tecnicos[] = $row_t['correo']; // Lo guardamos a la lista
                        }
                    }
                }
            }
            
            // Si encontramos correos, conectamos a Google UNA SOLA VEZ y enviamos a todos
            if (count($lista_correos_tecnicos) > 0) {
                 $cuerpo_t = "<h2>Actualización de Ticket #$id_ticket</h2>
                             <p>El ticket <strong>{$info['asunto']}</strong> en el que estás asignado ha sido actualizado.</p>
                             <p><strong>Nuevo Estado:</strong> $nuevo_estado</p>
                             <p>Revísalo en tu panel de control.</p>";
                 enviarEmailMultiple($lista_correos_tecnicos, "Ticket Asignado/Actualizado #$id_ticket", $cuerpo_t);
            }
        }

        // Finalmente, mostramos la alerta de éxito y redirigimos
        echo "<script>alert('¡Ticket actualizado y notificado correctamente!'); window.location.href='Index.php?vista=tabla';</script>";
        
    } else {
        echo "Error: " . mysqli_error($conexion);
    }
}
?>