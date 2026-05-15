<?php
// Incluir conexión y configuración de zona horaria
include 'conexion.php';
include 'enviar_notificacion_solicitudes.php'; // 👈 NUEVO: Tu archivo de correos
date_default_timezone_set('America/Mazatlan'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. RECIBIR DATOS GENERALES
    $solicitante_id = intval($_POST['solicitante_id']);
    $centros_txt = isset($_POST['centros_consumo']) ? implode(", ", $_POST['centros_consumo']) : '';
    $tipo_sol = ($_POST['major_group_id_global'] == 1) ? 'Alimentos' : 'Bebidas';
    $fecha_inicio = mysqli_real_escape_string($conexion, $_POST['fecha_inicio']);
    $fecha_fin = !empty($_POST['fecha_fin']) ? "'" . mysqli_real_escape_string($conexion, $_POST['fecha_fin']) . "'" : "NULL";
    $observaciones = mysqli_real_escape_string($conexion, $_POST['observaciones_gral']);
    
    // 2. MANEJO DE LA EVIDENCIA (Imagen del usuario)
    $ruta_evidencia = "";
    if (isset($_FILES['archivo_evidencia']) && $_FILES['archivo_evidencia']['error'] == 0) {
        $directorio = "uploads/";
        if(!is_dir($directorio)){ mkdir($directorio, 0777, true); }
        $nombre_archivo = time() . "_" . basename(str_replace(' ', '_', $_FILES['archivo_evidencia']['name']));
        $ruta_destino = $directorio . $nombre_archivo;
        
        if (move_uploaded_file($_FILES['archivo_evidencia']['tmp_name'], $ruta_destino)) {
            $ruta_evidencia = $ruta_destino;
        }
    }

    // 3. INSERT TABLA PRINCIPAL
    $sql_principal = "INSERT INTO solicitudes_pos 
                      (personal_id, centros_consumo, tipo_solicitud, fecha_inicio, fecha_fin, observaciones, ruta_evidencia, estado) 
                      VALUES 
                      ($solicitante_id, '$centros_txt', '$tipo_sol', '$fecha_inicio', $fecha_fin, '$observaciones', '$ruta_evidencia', 'Pendiente')";
    
    if (mysqli_query($conexion, $sql_principal)) {
        $solicitud_id = mysqli_insert_id($conexion);

        // --- 🚀 NUEVO: BITÁCORA (LOG) DE CREACIÓN ---
        $res_sol = mysqli_query($conexion, "SELECT nombre, correo FROM personal_acre WHERE id = $solicitante_id");
        $data_sol = mysqli_fetch_assoc($res_sol);
        $nombre_real_solicitante = $data_sol['nombre'] ?? 'Usuario';
        $correo_solicitante = $data_sol['correo'] ?? '';

        mysqli_query($conexion, "INSERT INTO solicitudes_pos_logs (solicitud_id, usuario_nombre, accion, detalles) 
                                 VALUES ($solicitud_id, '$nombre_real_solicitante', 'Creación', 'Solicitud de botones generada desde el portal.')");

        // 4. BUCLE PARA GUARDAR BOTONES
        $nombres_botones = $_POST['nombre_boton'];
        $precios         = $_POST['precio'];
        $major_groups    = $_POST['major_group_id'];
        $family_groups   = $_POST['family_id'];
        $modifiers       = $_POST['modifier_id'];
        $impresoras_arrays = $_POST['printers']; 
        
        $total_botones = count($nombres_botones);
        $botones_para_correo = []; // 👈 NUEVO: Para la tabla del mail

        for ($i = 0; $i < $total_botones; $i++) {
            $nombre = mysqli_real_escape_string($conexion, $nombres_botones[$i]);
            $precio = floatval($precios[$i]);
            $major = intval($major_groups[$i]);
            $family = intval($family_groups[$i]);
            $modifier = intval($modifiers[$i]);
            $impresoras_texto = isset($impresoras_arrays[$i]) ? implode(", ", $impresoras_arrays[$i]) : '';
            
            $sql_boton = "INSERT INTO solicitudes_pos_botones 
                          (solicitud_id, nombre_boton, precio, major_group_id, family_group_id, modifier_id, impresoras) 
                          VALUES 
                          ($solicitud_id, '$nombre', $precio, $major, $family, $modifier, '$impresoras_texto')";
            
            mysqli_query($conexion, $sql_boton);

            // Guardamos info para el correo (traemos nombres de grupos)
            $res_m = mysqli_query($conexion, "SELECT nombre FROM pos_major_groups WHERE id = $major");
            $m_nom = mysqli_fetch_assoc($res_m)['nombre'] ?? 'N/A';
            $res_f = mysqli_query($conexion, "SELECT nombre FROM pos_family_groups WHERE id = $family");
            $f_nom = mysqli_fetch_assoc($res_f)['nombre'] ?? 'N/A';

            $botones_para_correo[] = [
                'nombre' => $nombre,
                'precio' => $precio,
                'major'  => $m_nom,
                'family' => $f_nom
            ];
        }

        // --- 🚀 NUEVO: ENVIAR NOTIFICACIÓN POR CORREO ---
        $datos_mail = [
            'folio' => $solicitud_id,
            'nombre_solicitante' => $nombre_real_solicitante,
            'correo_solicitante' => $correo_solicitante,
            'fecha' => date('d/m/Y H:i A'),
            'observaciones' => $observaciones
        ];

        // Pasamos la imagen de evidencia en un array
        $imagenes_mail = !empty($ruta_evidencia) ? [$ruta_evidencia] : [];

        // Llamamos a tu función maestra
        enviarCorreoSolicitud($datos_mail, $botones_para_correo, "NUEVA SOLICITUD", $imagenes_mail);
                
        // 5. ÉXITO
        echo "<script>
                alert('¡Solicitud enviada exitosamente! Folio: #$solicitud_id');
                window.location.href = 'portal_solicitudes.php';
              </script>";
    } else {
        echo "Error al guardar la solicitud: " . mysqli_error($conexion);
    }
} else {
    header('Location: portal_solicitudes.php');
}
?>