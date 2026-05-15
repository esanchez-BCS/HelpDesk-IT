<?php
session_start(); 
include 'conexion.php';
mysqli_set_charset($conexion, "utf8");

$id_solicitud = intval($_GET['id']);

// 1. Lógica para MARCAR COMO COMPLETADO
if(isset($_POST['marcar_completado'])) {
    $evidencia_it_array = [];
    $comentarios_it = mysqli_real_escape_string($conexion, $_POST['comentarios_it']);
    $tecnico = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Equipo de IT';

    if (isset($_FILES['archivo_it']) && !empty($_FILES['archivo_it']['name'][0])) {
        $directorio = "uploads/";
        if(!is_dir($directorio)){ mkdir($directorio, 0777, true); }
        $total_archivos = count($_FILES['archivo_it']['name']);
        for($i = 0; $i < $total_archivos; $i++) {
            if($_FILES['archivo_it']['error'][$i] == 0) {
                $nombre_archivo = time() . "_" . $i . "_IT_" . basename(str_replace(' ', '_', $_FILES['archivo_it']['name'][$i]));
                $ruta_destino = $directorio . $nombre_archivo;
                if (move_uploaded_file($_FILES['archivo_it']['tmp_name'][$i], $ruta_destino)) {
                    $evidencia_it_array[] = $ruta_destino;
                }
            }
        }
    }

    $sql_old = "SELECT evidencia_completado FROM solicitudes_pos WHERE id = $id_solicitud";
    $res_old = mysqli_query($conexion, $sql_old);
    $row_old = mysqli_fetch_assoc($res_old);
    $evidencia_previa = $row_old['evidencia_completado'] ?? '';

    if(!empty($evidencia_previa) && !empty($evidencia_it_array)) {
         $rutas_finales = $evidencia_previa . "," . implode(",", $evidencia_it_array);
    } elseif(!empty($evidencia_it_array)) {
         $rutas_finales = implode(",", $evidencia_it_array);
    } else {
         $rutas_finales = $evidencia_previa;
    }
    
    // GUARDAR TODO: Usando 'tecnico_cierre' para que coincida con tu tabla
    $update_sql = "UPDATE solicitudes_pos SET 
                   estado = 'Completado', 
                   tecnico_cierre = '$tecnico', 
                   comentarios_it = '$comentarios_it', 
                   evidencia_completado = '$rutas_finales' 
                   WHERE id = $id_solicitud";
    
    if(mysqli_query($conexion, $update_sql)) {
        header("Location: ver_detalle_solicitud.php?id=$id_solicitud");
    } else {
        echo "Error al actualizar: " . mysqli_error($conexion);
    }
    exit();
}

// 2. Lógica para REABRIR SOLICITUD
if(isset($_POST['reabrir_solicitud'])) {
    mysqli_query($conexion, "UPDATE solicitudes_pos SET estado = 'Pendiente' WHERE id = $id_solicitud");
    header("Location: ver_detalle_solicitud.php?id=$id_solicitud");
    exit();
}

// 3. Traer datos de la solicitud
$sql_madre = "SELECT s.*, p.nombre AS solicitante, p.correo 
              FROM solicitudes_pos s 
              LEFT JOIN personal_acre p ON s.personal_id = p.id 
              WHERE s.id = $id_solicitud";
$res_madre = mysqli_query($conexion, $sql_madre);
$solicitud = mysqli_fetch_assoc($res_madre);

// Traducción de Centros de Consumo
$nombres_centros = "N/A";
$ids_centros = $solicitud['centros_consumo'] ?? '';
if (!empty($ids_centros) && preg_match('/^[0-9, ]+$/', $ids_centros)) { 
    $sql_c = "SELECT nombre FROM pos_revenue_centers WHERE id IN ($ids_centros)";
    $res_c = mysqli_query($conexion, $sql_c);
    if ($res_c) {
        $arr_c = [];
        while($row_c = mysqli_fetch_assoc($res_c)) { $arr_c[] = $row_c['nombre']; }
        $nombres_centros = implode(", ", $arr_c);
    }
}

// 4. Traer la lista de botones
$sql_botones = "SELECT b.*, m.nombre AS major, f.nombre AS family, modif.nombre AS modificador
                FROM solicitudes_pos_botones b
                LEFT JOIN pos_major_groups m ON b.major_group_id = m.id
                LEFT JOIN pos_family_groups f ON b.family_group_id = f.id
                LEFT JOIN pos_modifiers modif ON b.modifier_id = modif.id
                WHERE b.solicitud_id = $id_solicitud";
$res_botones = mysqli_query($conexion, $sql_botones);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Folio #<?php echo $id_solicitud; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #555555; --bg-light: #f2f2f2; --border: #dcdcdc; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg-light); color: #333; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--bg-light); padding-bottom: 20px; margin-bottom: 25px; }
        .btn-back { background: var(--primary); color: white; padding: 12px 20px; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 14px; transition: 0.3s; }
        .btn-success { background: #5cb85c; color: white; border: none; padding: 15px 30px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 16px; transition: 0.3s; }
        .btn-warning { background: #f0ad4e; color: white; border: none; padding: 12px 20px; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-box { background: #fafafa; border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
        .info-box h4 { margin: 0 0 15px 0; color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 10px; font-size: 15px; text-transform: uppercase; }
        .info-row { margin-bottom: 10px; font-size: 14px; }
        .info-row strong { color: #555; display: inline-block; width: 140px; }
        .table-responsive { overflow-x: auto; background: white; border: 1px solid var(--border); border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; min-width: 1000px; }
        th { background: var(--primary); color: white; padding: 15px; text-align: left; font-size: 13px; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f0f0f0; font-size: 14px; vertical-align: top; }
        .thumb-container { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 15px; }
        .img-thumb { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid var(--border); cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <a href="Index.php?vista=solicitudes_botones" class="btn-back"><i class="fa fa-arrow-left"></i> Volver a Bandeja</a>
        <h2 style="margin: 0; color: var(--primary);">Folio de Configuración #<?php echo $id_solicitud; ?></h2>
        
        <?php if($solicitud['estado'] == 'Completado'): ?>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span style="background: #5cb85c; color: white; padding: 12px 20px; border-radius: 10px; font-weight: bold;"><i class="fa fa-check-circle"></i> FINALIZADA</span>
                <form method="POST" style="margin: 0;">
                    <button type="submit" name="reabrir_solicitud" class="btn-warning"><i class="fa fa-undo"></i> Reabrir</button>
                </form>
            </div>
        <?php else: ?>
            <span style="background: #f0ad4e; color: white; padding: 12px 20px; border-radius: 10px; font-weight: bold;"><i class="fa fa-clock"></i> PENDIENTE</span>
        <?php endif; ?>
    </div>

    <?php if($solicitud['estado'] == 'Pendiente'): ?>
        <div style="background: #fff; border: 2px solid #5cb85c; border-radius: 15px; padding: 25px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(92,184,92,0.1);">
            <h3 style="margin-top: 0; color: #5cb85c;"><i class="fa fa-clipboard-check"></i> Finalizar Configuración</h3>
            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="font-weight: bold; font-size: 14px; color: #555; display:block; margin-bottom:8px;">Comentarios sobre el trabajo realizado (Requerido):</label>
                        <textarea name="comentarios_it" rows="3" required style="width: 100%; border-radius: 10px; padding: 15px; border: 1px solid #ddd; font-family: inherit;"><?php echo htmlspecialchars($solicitud['comentarios_it'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label style="font-weight: bold; font-size: 14px; color: #555; display:block; margin-bottom:8px;">Evidencia POS:</label>
                        <div style="border: 2px dashed #ccc; padding: 15px; border-radius: 10px; text-align: center; background: #fafafa;">
                            <input type="file" name="archivo_it[]" accept="image/*" multiple style="font-size: 13px;">
                        </div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <button type="submit" name="marcar_completado" class="btn-success"><i class="fa fa-paper-plane"></i> GUARDAR Y NOTIFICAR SOLICITANTE</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="info-grid">
        <div class="info-box">
            <h4><i class="fa fa-info-circle"></i> Datos de la Solicitud</h4>
            <div class="info-row"><strong>Tipo de Menú:</strong> <?php echo htmlspecialchars($solicitud['tipo_solicitud'] ?? 'N/A'); ?></div>
            <div class="info-row"><strong>Solicitante:</strong> <?php echo htmlspecialchars($solicitud['solicitante'] ?? 'N/A'); ?></div>
            <div class="info-row"><strong>Correo:</strong> <?php echo htmlspecialchars($solicitud['correo'] ?? 'N/A'); ?></div>
            <div class="info-row"><strong>Centros Consumo:</strong> <span style="background: #eef2ff; color: #4318ff; padding: 3px 8px; border-radius: 5px; font-weight: 600;"><?php echo htmlspecialchars($nombres_centros); ?></span></div>
            <div class="info-row"><strong>Fecha Creación:</strong> <?php echo date('d/m/Y h:i A', strtotime($solicitud['fecha_creacion'])); ?></div>
            
            <div class="info-row"><strong>Fecha Inicio Venta:</strong> <span style="font-weight: bold; color: #d9534f;"><?php echo !empty($solicitud['fecha_inicio']) ? date('d/m/Y', strtotime($solicitud['fecha_inicio'])) : 'N/A'; ?></span></div>
            <div class="info-row"><strong>Fecha Fin (Tentativa):</strong> <?php echo !empty($solicitud['fecha_fin']) ? date('d/m/Y', strtotime($solicitud['fecha_fin'])) : 'No aplica'; ?></div>
            
            <?php if($solicitud['estado'] == 'Completado'): ?>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--border);">
                    <div class="info-row"><strong>Atendido por:</strong> <span style="color: #01b574; font-weight: bold;"><i class="fa fa-user-check"></i> <?php echo htmlspecialchars($solicitud['tecnico_cierre'] ?? 'Equipo de IT'); ?></span></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="info-box">
            <h4><i class="fa fa-comment-dots"></i> Observaciones y Evidencia</h4>
            <p style="font-size: 14px; color: #444;"><?php echo nl2br(htmlspecialchars($solicitud['observaciones'] ?? '')) ?: 'Sin observaciones.'; ?></p>
            
            <?php if(!empty($solicitud['ruta_evidencia'])): ?>
                <h5 style="margin: 15px 0 5px 0;">Referencia del Solicitante:</h5>
                <div class="thumb-container">
                    <?php foreach(explode(",", $solicitud['ruta_evidencia']) as $ruta): if(!empty(trim($ruta))): ?>
                        <img src="<?php echo htmlspecialchars(trim($ruta)); ?>" class="img-thumb" onclick="window.open(this.src, '_blank')">
                    <?php endif; endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($solicitud['comentarios_it'])): ?>
                <div style="margin-top: 15px; padding: 15px; background: #f0fdf4; border-radius: 10px; border-left: 4px solid #01b574;">
                    <h5 style="margin: 0 0 5px 0; color: #01b574;">Nota de IT:</h5>
                    <p style="margin: 0; font-size: 14px; color: #155724;"><?php echo nl2br(htmlspecialchars($solicitud['comentarios_it'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if(!empty($solicitud['evidencia_completado'])): ?>
                <h5 style="margin: 15px 0 5px 0;">Evidencia de IT:</h5>
                <div class="thumb-container">
                    <?php foreach(explode(",", $solicitud['evidencia_completado']) as $ruta): if(!empty(trim($ruta))): ?>
                        <img src="<?php echo htmlspecialchars(trim($ruta)); ?>" class="img-thumb" onclick="window.open(this.src, '_blank')">
                    <?php endif; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <h3 style="color: var(--primary); margin-bottom: 15px;"><i class="fa fa-list"></i> Lista de Botones</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Botón</th><th>Precio</th><th>Major Group</th><th>Family Group</th><th>Modificador</th><th>Impresoras</th>
                </tr>
            </thead>
            <tbody>
                <?php while($boton = mysqli_fetch_assoc($res_botones)): ?>
                    <?php 
                    $nombres_imp = "N/A";
                    $ids_imp = $boton['impresoras'];
                    if (!empty($ids_imp) && preg_match('/^[0-9, ]+$/', $ids_imp)) {
                        $res_i = mysqli_query($conexion, "SELECT nombre FROM pos_printers WHERE id IN ($ids_imp)");
                        if ($res_i) {
                            $arr_i = [];
                            while($row_i = mysqli_fetch_assoc($res_i)) { $arr_i[] = $row_i['nombre']; }
                            $nombres_imp = implode(", ", $arr_i);
                        }
                    }
                    ?>
                    <tr>
                        <td><b><?php echo htmlspecialchars($boton['nombre_boton']); ?></b></td>
                        <td><strong style="color: #01b574;">$<?php echo number_format($boton['precio'], 2); ?></strong></td>
                        <td><?php echo htmlspecialchars($boton['major'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($boton['family'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($boton['modificador'] ?? 'Ninguno'); ?></td>
                        <td style="font-size: 12px; font-weight: 600; color: #555;"><?php echo htmlspecialchars($nombres_imp); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="info-box" style="margin-top: 30px; border-left: 5px solid #4318ff;">
    <h4><i class="fa fa-history"></i> Historial de Movimientos (Bitácora)</h4>
    <div style="max-height: 300px; overflow-y: auto;">
        <?php
        $res_logs = mysqli_query($conexion, "SELECT * FROM solicitudes_pos_logs WHERE solicitud_id = $id_solicitud ORDER BY fecha_movimiento DESC");
        if (mysqli_num_rows($res_logs) > 0): ?>
            <table style="width: 100%; font-size: 13px;">
                <tr style="color: #888; border-bottom: 1px solid #eee;">
                    <th style="padding: 10px;">Fecha/Hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Detalles</th>
                </tr>
                <?php while($l = mysqli_fetch_assoc($res_logs)): ?>
                    <tr style="border-bottom: 1px solid #f9f9f9;">
                        <td style="padding: 10px;"><?php echo date('d/m/Y h:i A', strtotime($l['fecha_movimiento'])); ?></td>
                        <td><b><?php echo $l['usuario_nombre']; ?></b></td>
                        <td><span class="status-pill status-process"><?php echo $l['accion']; ?></span></td>
                        <td><?php echo $l['detalles']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p style="color: #999; text-align: center; padding: 20px;">No hay movimientos registrados para esta solicitud aún.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>