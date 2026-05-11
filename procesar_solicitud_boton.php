<?php
// Incluir conexión y configuración de zona horaria
include 'conexion.php';
date_default_timezone_set('America/Mazatlan'); // Ajusta a tu zona horaria

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. RECIBIR DATOS GENERALES
    $solicitante_id = intval($_POST['solicitante_id']);
    // Convertimos el arreglo de centros de consumo en texto separado por comas
    $centros_txt = isset($_POST['centros_consumo']) ? implode(", ", $_POST['centros_consumo']) : '';
    // Detectamos si es de Alimentos (1) o Bebidas (3)
    $tipo_sol = ($_POST['major_group_id_global'] == 1) ? 'Alimentos' : 'Bebidas';
    $fecha_inicio = mysqli_real_escape_string($conexion, $_POST['fecha_inicio']);
    $fecha_fin = !empty($_POST['fecha_fin']) ? "'" . mysqli_real_escape_string($conexion, $_POST['fecha_fin']) . "'" : "NULL";
    $observaciones = mysqli_real_escape_string($conexion, $_POST['observaciones_gral']);
    
    // 2. MANEJO DE LA EVIDENCIA (Imagen del usuario)
    $ruta_evidencia = "";
    if (isset($_FILES['archivo_evidencia']) && $_FILES['archivo_evidencia']['error'] == 0) {
        $directorio = "uploads/";
        if(!is_dir($directorio)){ mkdir($directorio, 0777, true); }
        // Se guarda en la carpeta uploads/
        $nombre_archivo = time() . "_" . basename(str_replace(' ', '_', $_FILES['archivo_evidencia']['name']));
        $ruta_destino = $directorio . $nombre_archivo;
        
        if (move_uploaded_file($_FILES['archivo_evidencia']['tmp_name'], $ruta_destino)) {
            $ruta_evidencia = $ruta_destino;
        }
    }

    // 3. UN SOLO INSERT PARA LA TABLA PRINCIPAL (solicitudes_pos)
    $sql_principal = "INSERT INTO solicitudes_pos 
                      (personal_id, centros_consumo, tipo_solicitud, fecha_inicio, fecha_fin, observaciones, ruta_evidencia, estado) 
                      VALUES 
                      ($solicitante_id, '$centros_txt', '$tipo_sol', '$fecha_inicio', $fecha_fin, '$observaciones', '$ruta_evidencia', 'Pendiente')";
    
    if (mysqli_query($conexion, $sql_principal)) {
        // Obtenemos el ID de la solicitud que acabamos de crear (Ej. Folio 6)
        $solicitud_id = mysqli_insert_id($conexion);

        // 4. BUCLE PARA GUARDAR LA LISTA DE BOTONES DINÁMICOS
        $nombres_botones = $_POST['nombre_boton'];
        $precios         = $_POST['precio'];
        $major_groups    = $_POST['major_group_id'];
        $family_groups   = $_POST['family_id'];
        $modifiers       = $_POST['modifier_id']; // Ya lee correctamente el modificador
        $impresoras_arrays = $_POST['printers']; 
        
        $total_botones = count($nombres_botones);

        for ($i = 0; $i < $total_botones; $i++) {
            $nombre = mysqli_real_escape_string($conexion, $nombres_botones[$i]);
            $precio = floatval($precios[$i]);
            $major = intval($major_groups[$i]);
            $family = intval($family_groups[$i]);
            $modifier = intval($modifiers[$i]);
            
            // Las impresoras de esta fila en específico
            $impresoras_texto = isset($impresoras_arrays[$i]) ? implode(", ", $impresoras_arrays[$i]) : '';
            
            // INSERT PARA LOS BOTONES (Adiós a item_class_id, hola modifier_id)
            $sql_boton = "INSERT INTO solicitudes_pos_botones 
                          (solicitud_id, nombre_boton, precio, major_group_id, family_group_id, modifier_id, impresoras) 
                          VALUES 
                          ($solicitud_id, '$nombre', $precio, $major, $family, $modifier, '$impresoras_texto')";
            
            mysqli_query($conexion, $sql_boton);
        }
        
        // 5. REDIRECCIONAR AL USUARIO CON MENSAJE DE ÉXITO
        echo "<script>
                alert('¡Solicitud enviada exitosamente a IT! Tu número de folio es el #$solicitud_id');
                window.location.href = 'portal_solicitudes.php';
              </script>";
    } else {
        echo "Error al guardar la solicitud: " . mysqli_error($conexion);
    }
} else {
    // Si intentan entrar directo, los regresa
    header('Location: portal_solicitudes.php');
}
?>