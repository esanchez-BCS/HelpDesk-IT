<?php
include 'conexion.php';
include 'enviar_notificacion_solicitudes.php'; // Incluimos las funciones de correo
mysqli_set_charset($conexion, "utf8");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['es_edicion'])) {
    $id_solicitud = intval($_POST['solicitud_id']);
    
    // 1. OBTENER DATOS VIEJOS Y EL SOLICITANTE ORIGINAL
    $res_viejo = mysqli_query($conexion, "SELECT s.*, p.nombre as nombre_solicitante, p.correo as correo_solicitante 
                                          FROM solicitudes_pos s 
                                          LEFT JOIN personal_acre p ON s.personal_id = p.id 
                                          WHERE s.id = $id_solicitud");
    $old = mysqli_fetch_assoc($res_viejo);
    $nombre_real_solicitante = $old['nombre_solicitante'] ?? 'Solicitante';
    $correo_solicitante = $old['correo_solicitante'] ?? '';

    // Obtener los botones VIEJOS para la comparación
    $botones_viejos = [];
    $res_b_viejos = mysqli_query($conexion, "SELECT b.nombre_boton as nombre, b.precio, m.nombre as major, f.nombre as family 
                                      FROM solicitudes_pos_botones b
                                      LEFT JOIN pos_major_groups m ON b.major_group_id = m.id
                                      LEFT JOIN pos_family_groups f ON b.family_group_id = f.id
                                      WHERE b.solicitud_id = $id_solicitud");
    while($row_b = mysqli_fetch_assoc($res_b_viejos)) { $botones_viejos[] = $row_b; }

    // 2. ACTUALIZAR CABECERA DE LA SOLICITUD
    $centros = isset($_POST['centros_consumo']) ? implode(", ", $_POST['centros_consumo']) : '';
    $obs = mysqli_real_escape_string($conexion, $_POST['observaciones_gral']);
    $inicio = $_POST['fecha_inicio'];
    $fin = !empty($_POST['fecha_fin']) ? "'" . mysqli_real_escape_string($conexion, $_POST['fecha_fin']) . "'" : "NULL";

    $sql_upd = "UPDATE solicitudes_pos SET 
                centros_consumo = '$centros', 
                fecha_inicio = '$inicio', 
                fecha_fin = $fin,
                observaciones = '$obs', 
                estado = 'Editado PS',
                permiso_edicion = 'Bloqueado' 
                WHERE id = $id_solicitud";
    mysqli_query($conexion, $sql_upd);

    // 3. ACTUALIZAR BOTONES (Borrando e insertando)
    mysqli_query($conexion, "DELETE FROM solicitudes_pos_botones WHERE solicitud_id = $id_solicitud");
    
    $nombres = $_POST['nombre_boton'];
    $precios = $_POST['precio'];
    $total = count($nombres);
    $botones_nuevos = []; // Para el correo y bitácora

    for ($i = 0; $i < $total; $i++) {
        $n = mysqli_real_escape_string($conexion, $nombres[$i]);
        $p = floatval($precios[$i]);
        $m = intval($_POST['major_group_id'][$i]);
        $f = intval($_POST['family_id'][$i]);
        $mod = intval($_POST['modifier_id'][$i]);
        $imp = isset($_POST['printers'][$i]) ? implode(", ", $_POST['printers'][$i]) : '';

        mysqli_query($conexion, "INSERT INTO solicitudes_pos_botones 
            (solicitud_id, nombre_boton, precio, major_group_id, family_group_id, modifier_id, impresoras) 
            VALUES ($id_solicitud, '$n', $p, $m, $f, $mod, '$imp')");

        // Info extraída para mostrar nombres bonitos en bitácora/correo
        $res_m = mysqli_query($conexion, "SELECT nombre FROM pos_major_groups WHERE id = $m");
        $m_nom = mysqli_fetch_assoc($res_m)['nombre'] ?? 'N/A';
        $res_f = mysqli_query($conexion, "SELECT nombre FROM pos_family_groups WHERE id = $f");
        $f_nom = mysqli_fetch_assoc($res_f)['nombre'] ?? 'N/A';

        $botones_nuevos[] = ['nombre' => $n, 'precio' => $p, 'major' => $m_nom, 'family' => $f_nom];
    }

    // 4. CREAR TABLA COMPARATIVA HTML PARA LA BITÁCORA
    $tabla_cambios = "<div style='margin-top:10px; font-size:12px;'>
        <div style='color:#d9534f; margin-bottom:5px;'><b><i class='fa fa-times-circle'></i> VERSIÓN ANTERIOR:</b></div>
        <table style='width:100%; border-collapse:collapse; margin-bottom:15px; border:1px solid #ddd;'>
            <tr style='background:#f4f4f4;'><th>Botón</th><th>Precio</th><th>Major</th><th>Family</th></tr>";
    foreach($botones_viejos as $bv) {
        $tabla_cambios .= "<tr><td style='border:1px solid #ddd; padding:4px;'>{$bv['nombre']}</td><td style='border:1px solid #ddd; padding:4px;'>$ {$bv['precio']}</td><td style='border:1px solid #ddd; padding:4px;'>{$bv['major']}</td><td style='border:1px solid #ddd; padding:4px;'>{$bv['family']}</td></tr>";
    }
    $tabla_cambios .= "</table>
        <div style='color:#01b574; margin-bottom:5px;'><b><i class='fa fa-check-circle'></i> VERSIÓN NUEVA:</b></div>
        <table style='width:100%; border-collapse:collapse; border:1px solid #ddd;'>
            <tr style='background:#f4f4f4;'><th>Botón</th><th>Precio</th><th>Major</th><th>Family</th></tr>";
    foreach($botones_nuevos as $bn) {
        $tabla_cambios .= "<tr><td style='border:1px solid #ddd; padding:4px;'>{$bn['nombre']}</td><td style='border:1px solid #ddd; padding:4px;'>$ {$bn['precio']}</td><td style='border:1px solid #ddd; padding:4px;'>{$bn['major']}</td><td style='border:1px solid #ddd; padding:4px;'>{$bn['family']}</td></tr>";
    }
    $tabla_cambios .= "</table></div>";

    // 5. GUARDAR EN LA BITÁCORA (LOG)
    // El nombre del usuario ahora será el original ($nombre_real_solicitante)
    $detalle_log = mysqli_real_escape_string($conexion, "El solicitante editó la configuración de botones.<br>" . $tabla_cambios);
    mysqli_query($conexion, "INSERT INTO solicitudes_pos_logs (solicitud_id, usuario_nombre, accion, detalles) 
                             VALUES ($id_solicitud, '$nombre_real_solicitante', 'Editado PS', '$detalle_log')");

    // 6. ENVIAR CORREO DE EDICIÓN
    $datos_mail = [
        'folio' => $id_solicitud,
        'nombre_solicitante' => $nombre_real_solicitante,
        'correo_solicitante' => $correo_solicitante,
        'fecha' => date('d/m/Y H:i A'),
        'observaciones' => $obs
    ];

    // Llamamos a la nueva función que haremos en el Paso 2
    if (function_exists('enviarCorreoEdicion')) {
        enviarCorreoEdicion($datos_mail, $botones_viejos, $botones_nuevos);
    }

    echo "<script>alert('Solicitud #$id_solicitud actualizada exitosamente.'); window.location.href='portal_solicitudes.php';</script>";
}
?>