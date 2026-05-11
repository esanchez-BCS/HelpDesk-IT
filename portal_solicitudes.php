<?php 
include 'conexion.php'; 

// Forzar UTF-8 para evitar errores con acentos
mysqli_set_charset($conexion, "utf8");

function safeQuery($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    return $result ? $result : false;
}

// 1. CARGA DE DATOS INICIALES
$query_personal = safeQuery($conexion, "SELECT id, nombre, correo FROM personal_acre ORDER BY nombre ASC");
$query_centros  = safeQuery($conexion, "SELECT id, nombre FROM pos_revenue_centers ORDER BY nombre ASC");
$query_majors   = safeQuery($conexion, "SELECT id, nombre FROM pos_major_groups ORDER BY id ASC");
$query_classes  = safeQuery($conexion, "SELECT id, nombre FROM pos_item_classes ORDER BY nombre ASC");
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
        :root {
            --primary: #555555;       
            --bg-light: #f2f2f2;      
            --text-dark: #2c2c2c;     
            --white: #ffffff;
            --border: #dcdcdc;        
            --success: #4a4a4a;       
        }

        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg-light); color: var(--text-dark); padding-bottom: 50px; }
        
        .portal-header { background: var(--primary); color: white; padding: 40px 20px; text-align: center; border-radius: 0 0 30px 30px; box-shadow: 0 10px 20px rgba(0,0,0,0.05); margin-bottom: 40px; }
        .portal-header h1 { margin: 0; font-size: 32px; font-weight: 700; }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }

        .type-selector { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .type-card { background: white; border-radius: 20px; padding: 25px; text-align: center; cursor: pointer; border: 2px solid transparent; transition: 0.3s; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .type-card i { font-size: 35px; color: #a0a0a0; margin-bottom: 10px; }
        .type-card h3 { margin: 0; color: var(--text-dark); font-size: 16px; text-transform: uppercase; }
        .type-card:hover { transform: translateY(-3px); border-color: var(--primary); }
        .type-card.active { border-color: var(--primary); background: #fdfdfd; }
        .type-card.active i { color: var(--primary); }

        .form-section { background: white; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); display: none; }
        .section-title { font-size: 16px; color: var(--primary); border-bottom: 2px solid var(--bg-light); padding-bottom: 10px; margin-top: 25px; margin-bottom: 20px; font-weight: 700; text-transform: uppercase; }

        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 13px; color: #555; }
        input, textarea, select { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px; box-sizing: border-box; font-size: 14px; }

        .table-responsive { overflow-x: auto; background: white; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; min-width: 1250px; }
        th { background: #666; color: white; padding: 15px; font-size: 12px; text-transform: uppercase; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }

        .select2-container--default .select2-selection--single, 
        .select2-container--default .select2-selection--multiple { border: 1px solid var(--border) !important; border-radius: 10px !important; min-height: 44px !important; }

        .btn-add-row { background: none; color: var(--primary); border: 2px dashed var(--border); padding: 15px; border-radius: 12px; width: 100%; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-add-row:hover { border-color: var(--primary); background: #f9f9f9; }

        .btn-submit { background: var(--success); color: white; border: none; padding: 18px; border-radius: 12px; font-size: 16px; font-weight: 700; width: 100%; cursor: pointer; margin-top: 30px; text-transform: uppercase; letter-spacing: 1px; }
        .btn-submit:hover { background: #333; }
    </style>
</head>
<body>

    <div class="portal-header">
        <h1>Centro de Solicitudes Acre</h1>
        <p>Selecciona el tipo de apoyo que necesitas del equipo de IT</p>
    </div>

    <div class="container">
        <div class="type-selector">
            <div class="type-card" id="btn-alimentos" onclick="seleccionarCategoria(1, this)">
                <i class="fa fa-utensils"></i>
                <h3>Botones de Alimentos</h3>
            </div>
            <div class="type-card" id="btn-bebidas" onclick="seleccionarCategoria(3, this)">
                <i class="fa fa-glass-martini-alt"></i>
                <h3>Botones de Bebidas</h3>
            </div>
            <div class="type-card" onclick="window.location.href='portal_tickets.php'">
                <i class="fa fa-ticket-alt"></i>
                <h3>Ticket Rápido (Fallas)</h3>
            </div>
        </div>

        <div id="form-contenedor" class="form-section">
            <h2 id="titulo-seccion" style="margin-top:0;"><i class="fa fa-plus-circle"></i> Nueva Solicitud</h2>
            
            <form action="procesar_solicitud_boton.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="major_group_id_global" id="major_group_id_global" value="">

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div>
                        <label>SOLICITANTE (Correo)</label>
                        <select name="solicitante_id" class="select-search" required>
                            <option value="">Selecciona tu correo...</option>
                            <?php if($query_personal) { mysqli_data_seek($query_personal, 0); while($p = mysqli_fetch_assoc($query_personal)) echo "<option value='{$p['id']}'>{$p['nombre']} ({$p['correo']})</option>"; } ?>
                        </select>
                    </div>
                    <div>
                        <label>CENTROS DE CONSUMO (Selección Múltiple)</label>
                        <select name="centros_consumo[]" class="select-search" multiple="multiple" required>
                            <?php if($query_centros) { mysqli_data_seek($query_centros, 0); while($c = mysqli_fetch_assoc($query_centros)) echo "<option value='{$c['id']}'>{$c['nombre']}</option>"; } ?>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <div style="flex:1">
                            <label>FECHA INICIO</label>
                            <input type="text" name="fecha_inicio" class="datepicker" required placeholder="YYYY-MM-DD">
                        </div>
                        <div style="flex:1">
                            <label>FECHA FIN</label>
                            <input type="text" name="fecha_fin" class="datepicker" placeholder="Opcional">
                        </div>
                    </div>
                </div>

                <h3 class="section-title"><i class="fa fa-th-list"></i> Detalle de Ítems Solicitados</h3>
                
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
                            <tr class="item-row" id="row-1">
                                <td><input type="text" name="nombre_boton[]" required placeholder="Ej: Ribeye"></td>
                                <td><input type="number" step="0.01" name="precio[]" required placeholder="0.00"></td>
                                <td>
                                    <select name="major_group_id[]" class="select-search major-select" required>
                                        <option value="">Selecciona...</option>
                                        <?php echo $majors_html; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="family_id[]" class="select-search family-select" required>
                                        <option value="">Esperando Major...</option>
                                    </select>
                                </td>
                                
                                <td>
                                    <select name="modifier_id[]" class="select-search modifier-select">
                                        <option value="">Selecciona...</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="printers[0][]" class="select-search" multiple="multiple" required>
                                        <?php if($query_printers) { mysqli_data_seek($query_printers, 0); while($pr = mysqli_fetch_assoc($query_printers)) echo "<option value='{$pr['id']}'>{$pr['nombre']}</option>"; } ?>
                                    </select>
                                </td>
                                <td align="center">
                                    <button type="button" onclick="eliminarFila(this)" style="color:#d9534f; border:none; background:none; cursor:pointer; font-size:16px; margin-top:12px;"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn-add-row" onclick="agregarNuevaFila()">
                    <i class="fa fa-plus"></i> AGREGAR OTRO BOTÓN A ESTA SOLICITUD
                </button>

                <div style="margin-top:30px; display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                    <div>
                        <label>FOTO / REFERENCIA (Opcional)</label>
                        <input type="file" name="archivo_evidencia" style="border: 1px dashed #ccc; padding: 20px; background: #fafafa;">
                    </div>
                    <div>
                        <label>OBSERVACIONES ADICIONALES</label>
                        <textarea name="observaciones_gral" rows="4" placeholder="Algún detalle extra para el equipo de IT..."></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-submit">ENVIAR SOLICITUD A IT</button>
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
        
        let globalMajorId = 1;
        let rowCount = 1;

        $(document).ready(function() {
            activarComponentes();

            // 🚀 LÓGICA MÁGICA: Cuando cambien el Major Group de una fila, filtramos su Family Group
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
                familySelect.trigger('change'); // Refrescar diseño visual
            });
        });

        function activarComponentes() {
            $('.select-search').select2({ width: '100%' });
            flatpickr(".datepicker", { dateFormat: "Y-m-d", locale: "es", minDate: "today" });
        }

        // Lógica Global (Alimentos vs Bebidas)
        function seleccionarCategoria(id, elemento) {
            $('.type-card').removeClass('active');
            $(elemento).addClass('active');
            
            globalMajorId = id;
            $('#major_group_id_global').val(id);
            $('#form-contenedor').fadeIn();
            
            const txt = (id === 1) ? 'Solicitud: ALIMENTOS' : 'Solicitud: BEBIDAS';
            $('#titulo-seccion').html('<i class="fa fa-list"></i> ' + txt);

            // Actualizar SOLO los Modificadores en toda la tabla basándose en Alimentos/Bebidas
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
            let newRow = $('#row-1').clone();
            newRow.attr('id', 'row-' + rowCount);
            
            newRow.find('input').val('');
            newRow.find('.select2-container').remove();
            newRow.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id');

            // Resetear Family Group en la nueva fila a "Esperando Major..."
            newRow.find('.family-select').empty().append('<option value="">Esperando Major...</option>');

            // Actualizar índices de impresoras (como son múltiples, necesitan su corchete de fila)
            newRow.find('select[name^="printers"]').attr('name', `printers[${rowCount-1}][]`);

            $('#items-body').append(newRow);
            activarComponentes();
            
            // Forzar a la nueva fila a cargar los modificadores de la categoría actual
            const currentModifierSelect = newRow.find('.modifier-select');
            currentModifierSelect.empty().append('<option value="">Selecciona...</option>');
            dataModifiers.filter(m => m.major_group_id == globalMajorId).forEach(m => {
                currentModifierSelect.append(`<option value="${m.id}">${m.nombre}</option>`);
            });
        }

        function eliminarFila(btn) {
            if($('.item-row').length > 1) {
                $(btn).closest('tr').remove();
            }
        }
    </script>
</body>
</html>