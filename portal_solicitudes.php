<?php 
include 'conexion.php'; 

// Forzar UTF-8 para evitar errores con acentos
mysqli_set_charset($conexion, "utf8");

function safeQuery($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    return $result ? $result : false;
}

// --- LÓGICA DE BÚSQUEDA Y PERMISOS ---
$datos_edit = null;
$botones_edit = [];
$mensaje_permiso = "";

if (isset($_GET['buscar_folio'])) {
    $folio_buscado = intval($_GET['buscar_folio']);
    $res_m = mysqli_query($conexion, "SELECT s.*, p.nombre as nombre_solicitante FROM solicitudes_pos s LEFT JOIN personal_acre p ON s.personal_id = p.id WHERE s.id = $folio_buscado");
    $datos_edit_temp = mysqli_fetch_assoc($res_m);
    
    if ($datos_edit_temp) {
        $permiso_actual = $datos_edit_temp['permiso_edicion'];

        if ($permiso_actual == 'Aprobado') {
            // ✅ TIENE PERMISO: Cargamos todo para editar
            $datos_edit = $datos_edit_temp;
            $res_b = mysqli_query($conexion, "SELECT * FROM solicitudes_pos_botones WHERE solicitud_id = $folio_buscado");
            while($b = mysqli_fetch_assoc($res_b)) { 
                $botones_edit[] = $b;
            }
        } elseif ($permiso_actual == 'Solicitado') {
            $mensaje_permiso = "⚠️ La solicitud de edición para el Folio #$folio_buscado ya fue enviada. IT está revisándola.";
        } else {
            // ❌ BLOQUEADO: Guardamos datos para mostrar el botón de pedir permiso
            $mensaje_permiso = "🔒 El Folio #$folio_buscado está bloqueado para edición. ¿Deseas solicitar permiso a IT?";
            $folio_temp = $datos_edit_temp['id'];
        }
    } else {
        echo "<script>alert('El folio #$folio_buscado no existe.');</script>";
    }
}

// 1. CARGA DE DATOS INICIALES PARA EL FORMULARIO
$query_personal = safeQuery($conexion, "SELECT id, nombre, correo FROM personal_acre ORDER BY nombre ASC");
$query_centros  = safeQuery($conexion, "SELECT id, nombre FROM pos_revenue_centers ORDER BY nombre ASC");
$query_majors   = safeQuery($conexion, "SELECT id, nombre FROM pos_major_groups ORDER BY id ASC");
$query_printers = safeQuery($conexion, "SELECT id, nombre FROM pos_printers ORDER BY nombre ASC");

// Traemos familias y modificadores para JSON (JS)
$families = [];
$res_families = safeQuery($conexion, "SELECT id, major_group_id, nombre FROM pos_family_groups");
if($res_families) { while($f = mysqli_fetch_assoc($res_families)) { $families[] = $f; } }

$modifiers = [];
$res_modifiers = safeQuery($conexion, "SELECT id, major_group_id, nombre FROM pos_modifiers");
if($res_modifiers) { while($m = mysqli_fetch_assoc($res_modifiers)) { $modifiers[] = $m; } }

// Guardar los Majors en un array para imprimir en el HTML
$majors_html = "";
if($query_majors) { 
    mysqli_data_seek($query_majors, 0);
    while($m = mysqli_fetch_assoc($query_majors)) { 
        $majors_html .= "<option value='{$m['id']}'>{$m['nombre']}</option>"; 
    } 
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Solicitudes - Acre Resort</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        :root { --primary: #555555; --bg-light: #f2f2f2; --text-dark: #2c2c2c; --white: #ffffff; --border: #dcdcdc; --success: #4a4a4a; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg-light); color: var(--text-dark); padding-bottom: 50px; }
        .portal-header { background: var(--primary); color: white; padding: 40px 20px; text-align: center; border-radius: 0 0 30px 30px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); margin-bottom: 40px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .type-selector { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .type-card { background: white; border-radius: 20px; padding: 25px; text-align: center; cursor: pointer; border: 2px solid transparent; transition: 0.3s; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .type-card.active { border-color: #4318ff; background: #fdfdfd; }
        .form-section { background: white; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); display: <?php echo $datos_edit ? 'block' : 'none'; ?>; }
        input, textarea, select { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px; box-sizing: border-box; font-size: 14px; }
        .table-responsive { overflow-x: auto; background: white; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; min-width: 1250px; }
        th { background: #666; color: white; padding: 15px; font-size: 12px; text-transform: uppercase; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .btn-enviar { background: #4318ff; color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: bold; }
        .btn-submit { background: var(--success); color: white; border: none; padding: 18px; border-radius: 12px; font-size: 16px; font-weight: 700; width: 100%; cursor: pointer; margin-top: 30px; text-transform: uppercase; }
    </style>
</head>
<body>

    <div class="portal-header">
        <h1>Centro de Solicitudes Acre</h1>
        <p>Selecciona el tipo de apoyo que necesitas del equipo de IT</p>
    </div>

    <div class="container">
        <div style="background: #fff; padding: 20px; border-radius: 15px; margin-bottom: 25px; border: 1px solid #4318ff; box-shadow: 0 4px 15px rgba(67,24,255,0.05);">
            <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <h4 style="margin:0; color: #4318ff;"><i class="fa fa-search"></i> ¿Quieres editar una solicitud?</h4>
                <input type="number" name="buscar_folio" placeholder="Escribe el Folio (Ej. 8)" required style="width: 150px;">
                <button type="submit" class="btn-enviar">Buscar y Cargar</button>
                <?php if($datos_edit || !empty($mensaje_permiso)): ?>
                    <a href="portal_solicitudes.php" style="color: red; font-size: 13px; text-decoration: none; font-weight: bold;">[ Cancelar ]</a>
                <?php endif; ?>
            </form>

            <?php if(!empty($mensaje_permiso)): ?>
                <div style="background: #fff8e6; padding: 15px; border-radius: 10px; border: 1px solid #ffeeba; margin-top:15px; text-align: center;">
                    <p style="color: #856404; font-weight: bold; margin-bottom: 10px;"><?php echo $mensaje_permiso; ?></p>
                    <?php if(strpos($mensaje_permiso, 'bloqueado') !== false): ?>
                        <form action="solicitar_edicion.php" method="POST">
                            <input type="hidden" name="folio" value="<?php echo $folio_temp; ?>">
                            <button type="submit" class="btn-enviar" style="background: #4318ff;">Enviar Petición a IT ahora</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="type-selector">
            <div class="type-card <?php echo ($datos_edit && $datos_edit['tipo_solicitud'] == 'Alimentos') ? 'active' : ''; ?>" id="btn-alimentos" onclick="seleccionarCategoria(1, this)">
                <i class="fa fa-utensils" style="font-size: 35px; color: #666; margin-bottom: 10px;"></i>
                <h3>Botones de Alimentos</h3>
            </div>
            <div class="type-card <?php echo ($datos_edit && $datos_edit['tipo_solicitud'] == 'Bebidas') ? 'active' : ''; ?>" id="btn-bebidas" onclick="seleccionarCategoria(3, this)">
                <i class="fa fa-glass-martini-alt" style="font-size: 35px; color: #666; margin-bottom: 10px;"></i>
                <h3>Botones de Bebidas</h3>
            </div>
            <div class="type-card" onclick="window.location.href='portal_tickets.php'">
                <i class="fa fa-ticket-alt" style="font-size: 35px; color: #666; margin-bottom: 10px;"></i>
                <h3>Ticket Rápido (Fallas)</h3>
            </div>
        </div>

        <div id="form-contenedor" class="form-section">
            <h2 id="titulo-seccion" style="margin-top:0;">
                <i class="fa <?php echo $datos_edit ? 'fa-edit' : 'fa-plus-circle'; ?>"></i> 
                <?php echo $datos_edit ? "Editando Folio #".$datos_edit['id'] : "Nueva Solicitud"; ?>
            </h2>
            
            <form action="<?php echo $datos_edit ? 'procesar_edicion_boton.php' : 'procesar_solicitud_boton.php'; ?>" method="POST" enctype="multipart/form-data">
                <?php if($datos_edit): ?>
                    <input type="hidden" name="es_edicion" value="1">
                    <input type="hidden" name="solicitud_id" value="<?php echo $datos_edit['id']; ?>">
                <?php endif; ?>
                
                <input type="hidden" name="major_group_id_global" id="major_group_id_global" value="<?php echo $datos_edit ? ($datos_edit['tipo_solicitud'] == 'Alimentos' ? 1 : 3) : ''; ?>">

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div>
                        <label>SOLICITANTE</label>
                        <select name="solicitante_id" class="select-search" required <?php echo $datos_edit ? 'disabled' : ''; ?>>
                            <option value="">Selecciona tu nombre...</option>
                            <?php 
                            if($query_personal) { 
                                mysqli_data_seek($query_personal, 0); 
                                while($p = mysqli_fetch_assoc($query_personal)) {
                                    $selected = ($datos_edit && $datos_edit['personal_id'] == $p['id']) ? 'selected' : '';
                                    echo "<option value='{$p['id']}' $selected>{$p['nombre']} ({$p['correo']})</option>";
                                }
                            } 
                            ?>
                        </select>
                        <?php if($datos_edit): ?>
                             <input type="hidden" name="solicitante_id" value="<?php echo $datos_edit['personal_id']; ?>">
                             <small style="color: #a3aed0;">El solicitante no se puede cambiar en edición.</small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label>CENTROS DE CONSUMO</label>
                        <?php $centros_seleccionados = $datos_edit ? explode(", ", $datos_edit['centros_consumo']) : []; ?>
                        <select name="centros_consumo[]" class="select-search" multiple="multiple" required>
                            <?php 
                            if($query_centros) { 
                                mysqli_data_seek($query_centros, 0); 
                                while($c = mysqli_fetch_assoc($query_centros)) {
                                    $selected = in_array($c['id'], $centros_seleccionados) ? 'selected' : '';
                                    echo "<option value='{$c['id']}' $selected>{$c['nombre']}</option>";
                                }
                            } 
                            ?>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <div style="flex:1">
                            <label>FECHA INICIO</label>
                            <input type="text" name="fecha_inicio" class="datepicker" required value="<?php echo $datos_edit['fecha_inicio'] ?? ''; ?>">
                        </div>
                        <div style="flex:1">
                            <label>FECHA FIN</label>
                            <input type="text" name="fecha_fin" class="datepicker" value="<?php echo $datos_edit['fecha_fin'] ?? ''; ?>">
                        </div>
                    </div>
                </div>

                <h3 class="section-title"><i class="fa fa-th-list"></i> Detalle de Ítems</h3>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th width="15%">Nombre del Botón</th>
                                <th width="9%">Precio</th>
                                <th width="15%">Major Group</th>
                                <th width="15%">Family Group</th>
                                <th width="14%">Modificador</th>
                                <th width="15%">Impresoras</th>
                                <th width="3%"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <?php if(!$datos_edit): ?>
                            <tr class="item-row" id="row-1">
                                <td><input type="text" name="nombre_boton[]" required placeholder="Ej: Ribeye"></td>
                                <td><input type="number" step="0.01" name="precio[]" required placeholder="0.00"></td>
                                <td>
                                    <select name="major_group_id[]" class="select-search major-select" required>
                                        <option value="">Selecciona...</option>
                                        <?php echo $majors_html; ?>
                                    </select>
                                </td>
                                <td><select name="family_id[]" class="select-search family-select" required><option value="">Esperando Major...</option></select></td>
                                <td><select name="modifier_id[]" class="select-search modifier-select"><option value="">Selecciona...</option></select></td>
                                <td>
                                    <select name="printers[0][]" class="select-search" multiple="multiple" required>
                                        <?php if($query_printers) { mysqli_data_seek($query_printers, 0); while($pr = mysqli_fetch_assoc($query_printers)) echo "<option value='{$pr['id']}'>{$pr['nombre']}</option>"; } ?>
                                    </select>
                                </td>
                                <td align="center"><button type="button" onclick="eliminarFila(this)" style="color:#d9534f; border:none; background:none; cursor:pointer;"><i class="fa fa-trash"></i></button></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn-add-row" onclick="agregarNuevaFila()" style="background: #eef2ff; color: #4318ff; border: 2px dashed #4318ff; padding: 15px; border-radius: 12px; width: 100%; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; margin-bottom: 20px;"><i class="fa fa-plus"></i> AGREGAR OTRO BOTÓN</button>

                <div style="margin-top:30px; display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                    <div>
                        <label>FOTO / REFERENCIA</label>
                        <input type="file" name="archivo_evidencia" style="background: #fafafa;">
                        <?php if($datos_edit && !empty($datos_edit['ruta_evidencia'])): ?>
                            <p style="font-size: 12px; color: #666; margin-top: 5px;">* Ya hay una imagen cargada. Subir una nueva la reemplazará.</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label>OBSERVACIONES ADICIONALES</label>
                        <textarea name="observaciones_gral" rows="4"><?php echo $datos_edit['observaciones'] ?? ''; ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-submit"><?php echo $datos_edit ? 'GUARDAR CAMBIOS EN FOLIO #'.$datos_edit['id'] : 'ENVIAR SOLICITUD A IT'; ?></button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    <script>
        const dataFamilies = <?php echo json_encode($families, JSON_UNESCAPED_UNICODE) ?: '[]'; ?>;
        const dataModifiers = <?php echo json_encode($modifiers, JSON_UNESCAPED_UNICODE) ?: '[]'; ?>;
        const botonesEdit = <?php echo json_encode($botones_edit, JSON_UNESCAPED_UNICODE); ?>;
        
        let globalMajorId = <?php echo $datos_edit ? ($datos_edit['tipo_solicitud'] == 'Alimentos' ? 1 : 3) : 1; ?>;
        let rowCount = 0;

        $(document).ready(function() {
            activarComponentes();

            // Si hay datos para editar, llenamos la tabla dinámicamente
            if (botonesEdit && botonesEdit.length > 0) {
                $('#items-body').empty();
                botonesEdit.forEach(function(boton) {
                    agregarNuevaFila();
                    let row = $('#row-' + rowCount);
                    row.find('input[name="nombre_boton[]"]').val(boton.nombre_boton);
                    row.find('input[name="precio[]"]').val(boton.precio);
                    
                    // Al hacer trigger change, se cargan las familias instantáneamente
                    row.find('.major-select').val(boton.major_group_id).trigger('change');
                    
                    // Como el proceso es instantáneo, asignamos el Family y Modifier directamente sin setTimeout!
                    row.find('.family-select').val(boton.family_group_id).trigger('change');
                    row.find('.modifier-select').val(boton.modifier_id).trigger('change');

                    if (boton.impresoras) {
                        let imps = boton.impresoras.split(',').map(s => s.trim());
                        row.find('select[name^="printers"]').val(imps).trigger('change');
                    }
                });
            }

            $(document).on('change', '.major-select', function() {
                const majorSeleccionado = $(this).val();
                const fila = $(this).closest('tr');
                const familySelect = fila.find('.family-select');
                familySelect.empty().append('<option value="">Selecciona...</option>');
                if(majorSeleccionado) {
                    dataFamilies.filter(f => f.major_group_id == majorSeleccionado).forEach(f => {
                        familySelect.append(`<option value="${f.id}">${f.nombre}</option>`);
                    });
                }
                familySelect.trigger('change');
            });
        });

        function activarComponentes() {
            $('.select-search').select2({ width: '100%' });
            flatpickr(".datepicker", { dateFormat: "Y-m-d", locale: "es" });
        }

        function seleccionarCategoria(id, elemento) {
            $('.type-card').removeClass('active');
            $(elemento).addClass('active');
            globalMajorId = id;
            $('#major_group_id_global').val(id);
            $('#form-contenedor').fadeIn();
            $('#titulo-seccion').html('<i class="fa fa-list"></i> ' + (id === 1 ? 'Solicitud: ALIMENTOS' : 'Solicitud: BEBIDAS'));
            actualizarModificadores();
        }

        function actualizarModificadores() {
            $('.item-row').each(function() {
                const modifierSelect = $(this).find('.modifier-select');
                modifierSelect.empty().append('<option value="">Selecciona...</option>');
                dataModifiers.filter(m => m.major_group_id == globalMajorId).forEach(m => {
                    modifierSelect.append(`<option value="${m.id}">${m.nombre}</option>`);
                });
                modifierSelect.trigger('change');
            });
        }

        function agregarNuevaFila() {
            rowCount++;
            // Usamos un template de fila limpia o clonamos la primera
            let newRowHtml = `
                <tr class="item-row" id="row-${rowCount}">
                    <td><input type="text" name="nombre_boton[]" required></td>
                    <td><input type="number" step="0.01" name="precio[]" required></td>
                    <td><select name="major_group_id[]" class="select-search major-select" required><option value="">Selecciona...</option><?php echo $majors_html; ?></select></td>
                    <td><select name="family_id[]" class="select-search family-select" required><option value="">Esperando...</option></select></td>
                    <td><select name="modifier_id[]" class="select-search modifier-select"><option value="">Selecciona...</option></select></td>
                    <td><select name="printers[${rowCount-1}][]" class="select-search" multiple="multiple" required><?php if($query_printers) { mysqli_data_seek($query_printers, 0); while($pr = mysqli_fetch_assoc($query_printers)) echo "<option value='{$pr['id']}'>{$pr['nombre']}</option>"; } ?></select></td>
                    <td align="center"><button type="button" onclick="eliminarFila(this)" style="color:#d9534f; border:none; background:none; cursor:pointer;"><i class="fa fa-trash"></i></button></td>
                </tr>`;
            $('#items-body').append(newRowHtml);
            activarComponentes();
            actualizarModificadores();
        }

        function eliminarFila(btn) {
            if($('.item-row').length > 1) { $(btn).closest('tr').remove(); }
        }
    </script>
</body>
</html>