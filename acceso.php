<?php
include 'conexion.php';
// Obtenemos solo las ubicaciones que sí tienen equipos asignados
$res_locs = mysqli_query($conexion, "SELECT DISTINCT ubicacion FROM assets WHERE ubicacion != '' ORDER BY ubicacion ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Help Desk</title>
    <style>
        :root {
            --primary-blue: #4318ff;
            --primary-green: #01b574;
            --bg-light: #f4f7fe;
            --text-dark: #2b3674;
            --white: #ffffff;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
        }

        .split-container {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        .side {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.5s ease;
            padding: 20px;
        }

        /* Lado Izquierdo: Login Staff */
        .left-side {
            background-color: var(--white);
            color: var(--text-dark);
        }

        /* Lado Derecho: Ticket Rápido */
        .right-side {
            background-color: var(--primary-blue);
            color: var(--white);
        }

        .form-container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .left-side .form-container {
            background: var(--white);
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
        }

        h2 {
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }

        p {
            text-align: center;
            margin-bottom: 30px;
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e5f2;
            border-radius: 12px;
            box-sizing: border-box;
            font-size: 14px;
        }

        textarea { resize: none; height: 100px; }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-blue { background: var(--primary-blue); color: white; }
        .btn-green { background: var(--primary-green); color: white; }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .footer-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: inherit;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .split-container { flex-direction: column; overflow-y: auto; }
            .side { height: auto; padding: 50px 20px; }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* 🪄 MAGIA PARA IGUALAR LOS ESTILOS DEL BUSCADOR (SELECT2) CON LOS INPUTS NORMALES */
        .select2-container {
            margin-bottom: 15px !important; /* Despega la lista de la de abajo */
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single {
            height: 45px !important; /* Igualamos la altura exacta de tus otros inputs */
            border-radius: 8px !important;
            border: none !important; /* Le quitamos el borde para que combine */
            background-color: #ffffff !important;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #2b3674 !important; /* El mismo color de texto */
            padding-left: 12px !important;
            font-size: 14px;
            font-family: inherit;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 45px !important; /* Centramos la flechita */
            right: 5px !important;
        }
        
        /* Aseguramos que las listas 2 y 3 también midan exactamente 45px de alto */
        #select-equipo, #select-error {
            height: 45px !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
    </style>
</head>
<body>

<div class="split-container">
    
    <div class="side left-side">
        <div class="form-container">
            <h2>Acceso Equipo IT Acre</h2>
            <p>Panel de Gestión y Técnicos</p>
            <form action="validar_login.php" method="POST">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo" placeholder="usuario@empresa.com" required>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-blue">Ingresar al Panel</button>
                <a href="registro.php" class="footer-link">¿No tienes cuenta? Regístrate aquí</a>
            </form>
        </div>
    </div>

    <div class="side right-side">
        <div class="form-container">
            <h2>🚀 Ticket Rápido</h2>
            <p>Reporta una falla sin iniciar sesión</p>
            
            <form action="guardar_ticket.php" method="POST" id="form-ticket-rapido">
                <input type="text" name="solicitante" placeholder="Tu nombre completo" required style="width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 8px; border: none;">
                
                <select id="select-ubicacion" name="departamento" required style="width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 8px; border: 1px solid #e0e5f2; color: #2b3674;">
                    <option value="" disabled selected>1. Selecciona tu Ubicación</option>
                    <?php 
                    // Reiniciamos el puntero por si acaso
                    mysqli_data_seek($res_locs, 0);
                    while($loc = mysqli_fetch_assoc($res_locs)): 
                    ?>
                        <option value="<?php echo htmlspecialchars($loc['ubicacion']); ?>"><?php echo htmlspecialchars($loc['ubicacion']); ?></option>
                    <?php endwhile; ?>
                </select>

                <select id="select-equipo" name="asset_id" disabled required style="width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 8px; border: none; color: #2b3674;">
                    <option value="" disabled selected>2. Primero selecciona una ubicación</option>
                </select>
                
                <select id="select-error" name="error_seleccionado" disabled required style="width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 8px; border: none; color: #2b3674;">
                    <option value="" disabled selected>3. Primero selecciona un equipo</option>
                </select>

                <div id="div-otros" style="display: none; margin-bottom: 15px;">
                    <input type="text" id="input-otros" name="error_otros" placeholder="Especifica tu problema..." style="width: 100%; padding: 12px; border-radius: 8px; border: 1px dashed #ccc; color: #2b3674;">
                </div>

                <input type="hidden" name="asunto" id="asunto-real" value="">
                <input type="hidden" name="estado" value="Request">

                <div class="form-group">
                    <input type="hidden" name="prioridad" value="Media"> 
                    <textarea name="descripcion" placeholder="Explícanos brevemente el problema..." required></textarea>
                </div>

                <div style="margin-top: 5px; text-align: left; margin-bottom: 20px;">
                    <p style="color: white; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Agregar datos para contacto (Opcional):</p>
                    <div style="display: flex; gap: 10px;">
                        <input type="email" name="correo_contacto" placeholder="Tu correo..." style="flex: 1; padding: 12px; border-radius: 8px; border: none; font-family: inherit;">
                        <input type="text" name="telefono_contacto" placeholder="Tu teléfono..." style="flex: 1; padding: 12px; border-radius: 8px; border: none; font-family: inherit;">
                    </div>
                </div>

                <button type="submit" class="btn btn-green">Enviar a Soporte</button>
            </form>
        </div>
    </div>

</div>


<script>
        // Cuando cambias la Ubicación (Adaptado para Select2)
        $('#select-ubicacion').on('change', function() {
            const ubicacion = this.value;
            const selectEquipo = document.getElementById('select-equipo');
            const selectError = document.getElementById('select-error');
            
            selectEquipo.innerHTML = '<option value="" disabled selected>Cargando equipos...</option>';
            selectEquipo.disabled = true;
            selectError.innerHTML = '<option value="" disabled selected>3. Primero selecciona un equipo</option>';
            selectError.disabled = true;
            document.getElementById('div-otros').style.display = 'none';

            fetch('get_equipos.php?ubicacion=' + encodeURIComponent(ubicacion))
                .then(response => response.json())
                .then(data => {
                    selectEquipo.innerHTML = '<option value="" disabled selected>2. Selecciona el Equipo que falla</option>';
                    data.forEach(equipo => {
                        selectEquipo.innerHTML += `<option value="${equipo.id}" data-tipo="${equipo.tipo}" data-nombre="${equipo.nombre_equipo}">${equipo.nombre_equipo} (${equipo.marca})</option>`;
                    });
                    selectEquipo.disabled = false;
                });
        });

        // Cuando cambias el Equipo
        document.getElementById('select-equipo').addEventListener('change', function() {
            const opcionSeleccionada = this.options[this.selectedIndex];
            const tipoEquipo = opcionSeleccionada.getAttribute('data-tipo');
            const selectError = document.getElementById('select-error');
            
            selectError.innerHTML = '<option value="" disabled selected>Cargando fallas...</option>';
            selectError.disabled = true;
            document.getElementById('div-otros').style.display = 'none';

            fetch('get_errores.php?tipo=' + encodeURIComponent(tipoEquipo))
                .then(response => response.json())
                .then(data => {
                    selectError.innerHTML = '<option value="" disabled selected>3. Selecciona la falla principal</option>';
                    data.forEach(error => {
                        selectError.innerHTML += `<option value="${error.descripcion_error}">${error.descripcion_error}</option>`;
                    });
                    selectError.innerHTML += `<option value="Otros">Otro problema (Especificar...)</option>`;
                    selectError.disabled = false;
                });
        });

        // Cuando cambias el Error (Por si eligen "Otros")
        document.getElementById('select-error').addEventListener('change', function() {
            const divOtros = document.getElementById('div-otros');
            const inputOtros = document.getElementById('input-otros');
            if (this.value === 'Otros') {
                divOtros.style.display = 'block';
                inputOtros.required = true;
            } else {
                divOtros.style.display = 'none';
                inputOtros.required = false;
            }
        });

        // Al enviar el formulario, armamos el Asunto: "Equipo - Error"
        // Al enviar el formulario, armamos el Asunto: "Equipo - Error" de forma segura
        document.getElementById('form-ticket-rapido').addEventListener('submit', function(e) {
            const selectEq = document.getElementById('select-equipo');
            const selectErr = document.getElementById('select-error');
            const inputOtros = document.getElementById('input-otros');
            const asuntoReal = document.getElementById('asunto-real');

            // 1. Validar que el equipo no esté deshabilitado o vacío
            if (selectEq.disabled || selectEq.value === "") {
                e.preventDefault(); // Detenemos el envío si intentan hacer trampa
                alert("⚠️ Por favor, espera a que carguen los equipos y selecciona uno.");
                return;
            }

            // 2. Extraemos los textos de forma segura (.text en lugar de atributos ocultos)
            let equipoNombre = selectEq.options[selectEq.selectedIndex].text;
            let errorSeleccionado = selectErr.value;
            
            if (errorSeleccionado === 'Otros') {
                errorSeleccionado = inputOtros.value;
            }
            
            // 3. Seguro de vida final antes de enviar (CON DETECTOR)
        document.getElementById('form-ticket-rapido').addEventListener('submit', function(e) {
            actualizarAsunto(); // Forzamos a que se arme el texto
            
            const asuntoFinal = document.getElementById('asunto-real').value;
            
            // Si el asunto oculto sigue vacío, bloqueamos el envío
            if (asuntoFinal === "") {
                e.preventDefault();
                alert("⚠️ Error: El asunto está vacío. Por favor selecciona un equipo y un error.");
            } else {
                // DETECTOR: Nos mostrará una ventanita confirmando el texto antes de viajar a la BD
                alert("Enviando a la Base de Datos el siguiente Asunto: \n\n" + asuntoFinal);
            }
        });

            // 4. Inyectamos el Asunto Real
            asuntoReal.value = equipoNombre + ' - ' + errorSeleccionado;
        });

        // ==========================================
        // INICIALIZAR EL BUSCADOR DE UBICACIONES
        // ==========================================
        $(document).ready(function() {
            $('#select-ubicacion').select2({
                placeholder: "Escribe para buscar tu ubicación...",
                width: '100%' // Asegura que no se apachurre el cuadro
            });
        });
</script>

</body>
</html>