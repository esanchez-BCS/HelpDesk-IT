<?php
// --- 🛡️ 1. BLINDAJE DE SESIÓN (SOLO SI NO HAY UNA ACTIVA) ---
// Esto previene errores cuando alguien crea un Ticket Rápido sin estar logueado.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capturamos los datos básicos
    $solicitante = mysqli_real_escape_string($conexion, $_POST['solicitante']);
    $asunto = mysqli_real_escape_string($conexion, $_POST['asunto']);
    $departamento = mysqli_real_escape_string($conexion, $_POST['departamento']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    $prioridad = isset($_POST['prioridad']) ? mysqli_real_escape_string($conexion, $_POST['prioridad']) : 'Media';
    $estado = isset($_POST['estado']) ? mysqli_real_escape_string($conexion, $_POST['estado']) : 'Abierto';
    
    // Procesamos asignación múltiple para ticket nuevo
    $asignados_array = isset($_POST['asignado_a']) ? $_POST['asignado_a'] : [];
    if (empty($asignados_array)) {
        $asignado_a = "Sin asignar";
    } else {
        $asignado_a = implode(', ', $asignados_array);
    }
    $asignado_a = mysqli_real_escape_string($conexion, $asignado_a);
    
    $asset_id = isset($_POST['asset_id']) ? intval($_POST['asset_id']) : "NULL";
  
    // Atrapamos los datos de contacto
    $correo_contacto = isset($_POST['correo_contacto']) ? mysqli_real_escape_string($conexion, $_POST['correo_contacto']) : '';
    $telefono_contacto = isset($_POST['telefono_contacto']) ? mysqli_real_escape_string($conexion, $_POST['telefono_contacto']) : '';
    
    // BACKUP ANTIFALLOS: Si el Asunto llega vacío, lo armamos a la fuerza
    if (empty(trim($asunto)) || $asunto == ' - ') {
        $error_reportado = isset($_POST['error_seleccionado']) ? mysqli_real_escape_string($conexion, $_POST['error_seleccionado']) : '';
        $error_otros = isset($_POST['error_otros']) ? mysqli_real_escape_string($conexion, $_POST['error_otros']) : '';
        
        $nombre_equipo = "Equipo Reportado";
        
        if ($asset_id != "NULL") {
            $res_asset = mysqli_query($conexion, "SELECT nombre_equipo FROM assets WHERE id = $asset_id");
            if ($row_asset = mysqli_fetch_assoc($res_asset)) {
                $nombre_equipo = $row_asset['nombre_equipo'];
            }
        }

        $falla_final = ($error_reportado === 'Otros') ? $error_otros : $error_reportado;
        
        if (!empty($falla_final)) {
            $asunto = $nombre_equipo . " - " . $falla_final;
        } else {
            $asunto = "Reporte de falla: " . $nombre_equipo;
        }
    }

    // CAMPOS DE PROGRAMACIÓN
    $tipo_trabajo = isset($_POST['tipo_trabajo']) ? mysqli_real_escape_string($conexion, $_POST['tipo_trabajo']) : 'Reactivo';
    $tipo_programacion = isset($_POST['tipo_programacion']) ? mysqli_real_escape_string($conexion, $_POST['tipo_programacion']) : 'One-time';
    $tiempo_completar = isset($_POST['tiempo_completar']) ? mysqli_real_escape_string($conexion, $_POST['tiempo_completar']) : '';
    
    // Manejo de la fecha
    $fecha_raw = $_POST['fecha_programada'] ?? '';
    if (!empty($fecha_raw)) {
        $fecha_obj = DateTime::createFromFormat('d/m/Y H:i', $fecha_raw);
        if($fecha_obj){
            $fecha_formateada = $fecha_obj->format('Y-m-d H:i:s');
            $fecha_sql = "'" . mysqli_real_escape_string($conexion, $fecha_formateada) . "'";
        } else {
            $fecha_sql = "'" . mysqli_real_escape_string($conexion, $fecha_raw) . "'";
        }
    } else {
        $fecha_sql = "NULL"; 
    }

    $sql = "INSERT INTO tickets (solicitante, asunto, departamento, descripcion, prioridad, estado, asignado_a, tipo_trabajo, fecha_programada, tipo_programacion, tiempo_completar, asset_id, correo_contacto, telefono_contacto) 
            VALUES ('$solicitante', '$asunto', '$departamento', '$descripcion', '$prioridad', '$estado', '$asignado_a', '$tipo_trabajo', $fecha_sql, '$tipo_programacion', '$tiempo_completar', $asset_id, '$correo_contacto', '$telefono_contacto')";

    if (mysqli_query($conexion, $sql)) {
        $nuevo_ticket_id = mysqli_insert_id($conexion);
        
        // --- 📋 LÓGICA DEL CHECKLIST ---
        $error_bd = "";
        $tareas_guardadas = 0;
        if (isset($_POST['checklist_items']) && is_array($_POST['checklist_items'])) {
            foreach ($_POST['checklist_items'] as $tarea) {
                $tarea_limpia = mysqli_real_escape_string($conexion, trim($tarea));
                if (!empty($tarea_limpia)) {
                    $sql_task = "INSERT INTO ticket_checklist (ticket_id, tarea) VALUES ($nuevo_ticket_id, '$tarea_limpia')";
                    if (mysqli_query($conexion, $sql_task)) {
                        $tareas_guardadas++;
                    } else {
                        $error_bd = mysqli_error($conexion);
                    }
                }
            }
        }

        // --- 📧 NOTIFICACIONES DE CREACIÓN ---
        include_once 'enviar_notificacion.php';
        
        // --- 2. NOTIFICACIÓN AL SOLICITANTE (COMO CONFIRMACIÓN) ---
        if (!empty(trim($correo_contacto))) {
            $cuerpo = "<h2>¡Hola, $solicitante!</h2>
                       <p>Hemos recibido tu reporte: <strong>$asunto</strong>.</p>
                       <p>Tu ticket ha sido registrado con el número <strong>#$nuevo_ticket_id</strong>.</p>
                       <p>Pronto uno de nuestros técnicos se pondrá en contacto contigo.</p>";
            enviarEmail($correo_contacto, "Confirmación de Reporte #$nuevo_ticket_id", $cuerpo);
        }

        // --- 3. NOTIFICACIÓN AL CORREO DE IT (CUANDO EL ESTADO ES 'REQUEST') ---
        if ($estado == 'Request') {
            // Pon aquí el correo real que acabas de configurar para IT.
            $correo_it_notificaciones = 'it@acreresort.com'; 
            if (!empty($correo_it_notificaciones)) {
                 $cuerpo_it = "<h2>¡Nuevo Request de Ticket! #$nuevo_ticket_id</h2>
                             <p>Se ha recibido una solicitud de ticket rápido.</p>
                             <p><strong>Solicitante:</strong> $solicitante</p>
                             <p><strong>Asunto:</strong> $asunto</p>
                             <p>Por favor, revisalo y asígnalo en el panel de control.</p>";
                 enviarEmail($correo_it_notificaciones, "Nuevo Request de Ticket #$nuevo_ticket_id", $cuerpo_it);
            }
        }

        // NOTIFICACIÓN AL TÉCNICO (SI FUE ASIGNADO DESDE EL FORMULARIO INTERNO)
        // (Nota: Esto no se ejecutará para tickets rápidos porque no tienen técnicos asignados al crearse).
        if ($asignado_a != 'Sin asignar' && !empty(trim($asignado_a))) {
            $tecnicos = explode(', ', $asignado_a);
            foreach($tecnicos as $t_nombre) {
                $t_nombre_limpio = mysqli_real_escape_string($conexion, trim($t_nombre));
                if (!empty($t_nombre_limpio)) {
                    $res_t = mysqli_query($conexion, "SELECT correo FROM usuarios WHERE nombre = '$t_nombre_limpio' LIMIT 1");
                    if($row_t = mysqli_fetch_assoc($res_t)) {
                        $correo_tecnico = $row_t['correo'];
                        if (!empty($correo_tecnico)) {
                             $cuerpo_t = "<h2>Nuevo Ticket Asignado #$nuevo_ticket_id</h2>
                                         <p><strong>Solicitante:</strong> $solicitante</p>
                                         <p><strong>Asunto:</strong> $asunto</p>
                                         <p>Revísalo en tu panel de control.</p>";
                             enviarEmail($correo_tecnico, "Asignación de Ticket #$nuevo_ticket_id", $cuerpo_t);
                        }
                    }
                }
            }
        }

        // --- RESULTADO ---
        if ($error_bd != "") {
            echo "<script>alert('Ticket creado, pero error en checklist: " . $error_bd . "'); window.location.href='Index.php?vista=tabla';</script>";
        } else {
            // Si el ticket se creó desde el login (Ticket Rápido), redirigimos al login
            if ($estado == 'Request') {
                 echo "<script>alert('¡Ticket enviado exitosamente! Nuestro equipo lo revisará pronto.'); window.location.href='acceso.php';</script>";
            } else {
                echo "<script>alert('¡Ticket creado exitosamente!'); window.location.href='Index.php?vista=tabla';</script>";
            }
        }
        exit();

    } else {
        echo "<div style='padding:20px; background:#ffe6e6; color:#f53939;'>Error al guardar ticket: " . mysqli_error($conexion) . "</div>";
    }
}
?>