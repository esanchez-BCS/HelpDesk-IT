<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['nombre'])) {
    header("Location: acceso.php"); // Cambiado a acceso.php que es tu login
    exit();
}

$rol_actual = $_SESSION['rol'];
$nombre_usuario = $_SESSION['nombre'];

/// --- LÓGICA DE CONTEO FILTRADA POR ROL ---
    if ($rol_actual == 'admin' || $rol_actual == 'tecnico') {
        $res_abiertos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM tickets WHERE estado = 'Abierto'");
        $res_proceso = mysqli_query($conexion, "SELECT COUNT(*) as total FROM tickets WHERE estado = 'En Proceso'");
        $res_resueltos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM tickets WHERE estado = 'Resuelto'");
        
        // --- NUEVO: Conteo de Requests para Admin/Tecnico ---
        $res_req_count = mysqli_query($conexion, "SELECT COUNT(*) as total FROM tickets WHERE estado = 'Request'");
        // Conteo de solicitudes de botones pendientes
        $res_pendientes_pos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM solicitudes_pos WHERE estado = 'Pendiente'");
        $total_pendientes_pos = mysqli_fetch_assoc($res_pendientes_pos)['total'];
        
        // ⚠️ ¡ESTA ES LA LÍNEA QUE ME FALTÓ DARTE!
        $sql_tickets = "SELECT * FROM tickets WHERE estado != 'Request' ORDER BY id DESC"; 
        
    } else {
        $res_abiertos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM tickets WHERE estado = 'Abierto' AND solicitante = '$nombre_usuario'");
        $res_proceso = mysqli_query($conexion, "SELECT COUNT(*) as total FROM tickets WHERE estado = 'En Proceso' AND solicitante = '$nombre_usuario'");
        $res_resueltos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM tickets WHERE estado = 'Resuelto' AND solicitante = '$nombre_usuario'");
        
        // --- NUEVO: Conteo de sus propios Requests ---
        $res_req_count = mysqli_query($conexion, "SELECT COUNT(*) as total FROM tickets WHERE estado = 'Request' AND solicitante = '$nombre_usuario'");
        
        // ⚠️ ¡Y ESTA TAMBIÉN ME FALTÓ DARTE!
        $sql_tickets = "SELECT * FROM tickets WHERE solicitante = '$nombre_usuario' AND estado != 'Request' ORDER BY id DESC";
    }

    $abiertos = mysqli_fetch_assoc($res_abiertos)['total'];
    $proceso = mysqli_fetch_assoc($res_proceso)['total'];
    $resueltos = mysqli_fetch_assoc($res_resueltos)['total'];
    
    // --- NUEVAS VARIABLES PARA LOS BADGES ---
    $total_requests = mysqli_fetch_assoc($res_req_count)['total'];
    $total_activos = $abiertos + $proceso; // Suma de abiertos y en proceso


// --- OBTENER TÉCNICOS PARA EL SELECT ---
        $res_usuarios = mysqli_query($conexion, "SELECT nombre FROM usuarios WHERE rol != 'usuario' ORDER BY nombre ASC");
        $usuarios_lista = [];
        while($u = mysqli_fetch_assoc($res_usuarios)) {
            $usuarios_lista[] = $u['nombre'];
        }

        function obtenerUbicacionesJerarquicas($conexion) {
            // Esta consulta une las áreas con sus zonas padres
            $sql = "SELECT p.nombre_ubicacion AS zona, 
                        h.nombre_ubicacion AS area, 
                        h.id AS area_id
                    FROM locations h
                    INNER JOIN locations p ON h.parent_id = p.id
                    ORDER BY p.nombre_ubicacion ASC, h.nombre_ubicacion ASC";
                    
            $resultado = mysqli_query($conexion, $sql);
            $ubicaciones = [];
            
            while ($fila = mysqli_fetch_assoc($resultado)) {
                // Estructura: $ubicaciones['Planta Alta'][] = ['id' => 5, 'nombre' => 'Oficinas']
                $ubicaciones[$fila['zona']][] = [
                    'id' => $fila['area_id'],
                    'nombre' => $fila['area']
                ];
            }
            return $ubicaciones;
        }

// Llamamos a la función para tener la lista lista
$lista_agrupada = obtenerUbicacionesJerarquicas($conexion);

$seccion = isset($_GET['vista']) ? $_GET['vista'] : 'bienvenida';
?>


<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <style>
        /* Estilos para las alertas numéricas del menú (Badges) */
        .badge-menu {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 10px;
            margin-left: auto; /* Empuja el número a la derecha */
            display: inline-block;
            min-width: 10px;
            text-align: center;
        }
        .badge-red {
            background-color: #f53939;
            color: white;
            box-shadow: 0 2px 8px rgba(245, 57, 57, 0.3);
        }
        .badge-blue {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 2px 8px rgba(67, 24, 255, 0.3);
        }
        
        /* Ajuste para que el nav-item sea un flexbox y el número se alinee bien */
        .nav-item {
            display: flex;
            align-items: center;
        }

        /* Estilos para que Select2 combine con tus bordes redondeados */
        .select2-container .select2-selection--single {
            height: 42px !important;
            border: 1px solid var(--border) !important;
            border-radius: 12px !important;
            padding: 5px !important;
            font-family: inherit;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }

        /* 🪄 ESTILOS PARA EL SELECT MÚLTIPLE (Asignar Técnicos) */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid var(--border) !important;
            border-radius: 12px !important;
            min-height: 45px !important;
            padding: 2px 8px !important;
            background-color: var(--white) !important;
        }
        
        /* Los cuadritos (tags) de los técnicos seleccionados */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #eef2ff !important; /* Fondo azul clarito */
            border: 1px solid #c3d4ff !important;
            border-radius: 8px !important;
            color: var(--primary) !important; /* Texto azul fuerte */
            padding: 5px 10px !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            margin-top: 6px !important;
        }
        
        /* La crucecita roja para quitar a un técnico */
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #f53939 !important;
            border-right: none !important;
            margin-right: 5px !important;
            font-weight: bold !important;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            background-color: transparent !important;
            color: #c90000 !important;
        }
    </style>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Help Desk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
        :root {
            --primary: #4318ff;
            --bg-light: #f4f7fe;
            --text-dark: #2b3674;
            --white: #ffffff;
            --border: #e0e5f2;
            --open: #4318ff;
            --process: #ffb800;
            --done: #01b574;
        }

        
        body { 
            margin: 0; 
            font-family: 'Segoe UI', sans-serif; 
            background: var(--bg-light); 
            display: flex; 
            height: 100vh; 
            color: var(--text-dark); 
            overflow: hidden; /* 🔒 ESTO BLOQUEA EL SCROLL GLOBAL */
        }
        
        .sidebar { 
            width: 260px; 
            background: var(--white); 
            border-right: 1px solid var(--border); 
            padding: 20px; 
            display: flex; 
            flex-direction: column; 
            height: 100vh; /* Se asegura de tocar el fondo */
            box-sizing: border-box;
            overflow-y: auto; /* Por si agregas más botones al menú */
        }

        .main-content { 
            flex: 1; 
            padding: 40px; 
            height: 100vh; /* Obliga al contenido a no estirar el body */
            box-sizing: border-box;
            overflow-y: auto; /* 📜 ESTO LE DA SU PROPIO SCROLL A LA PARTE DERECHA */
        }

        /* 🪄 MAGIA PARA OCULTAR LA BARRA DE SCROLL PERO SEGUIR DESLIZANDO */
        .sidebar::-webkit-scrollbar, 
        .main-content::-webkit-scrollbar {
            display: none; /* Oculta la barra en Chrome, Safari y Edge */
        }

        .sidebar, 
        .main-content {
            -ms-overflow-style: none;  /* Oculta la barra en Internet Explorer 10+ */
            scrollbar-width: none;  /* Oculta la barra en Firefox */
        }
        .nav-item { padding: 15px; text-decoration: none; color: #a3aed0; font-weight: 500; border-radius: 12px; margin-bottom: 5px; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: var(--bg-light); color: var(--primary); }

        

        /* Stats Rediseñadas  */
            .stats-grid { 
                display: grid; 
                grid-template-columns: repeat(3, 1fr); 
                gap: 15px; 
                margin-bottom: 25px; 
            }

            .stat-card { 
                background: var(--white); 
                padding: 12px 20px; 
                border-radius: 15px; 
                border-left: 4px solid; 
                box-shadow: 0 4px 12px rgba(0,0,0,0.03); 
                display: flex; 
                align-items: center; 
                justify-content: space-between; /* Esto pone el texto a la izquierda y el número a la derecha */
            }

            .stat-card h4 { 
                margin: 0; 
                color: #a3aed0; 
                font-size: 13px; 
                text-transform: uppercase; 
                font-weight: 700;
            }

            .stat-card p { 
                margin: 0; 
                font-size: 20px; 
                font-weight: 800; 
                color: var(--text-dark);
            }
        
        .card-open { border-left-color: var(--open); }
        .card-process { border-left-color: var(--process); }
        .card-done { border-left-color: var(--done); }
        
        .stat-card h4 { margin: 0; color: #a3aed0; font-size: 14px; text-transform: uppercase; }
        .stat-card p { margin: 5px 0 0; font-size: 24px; font-weight: bold; }

        .form-container { 
            background: var(--white); 
            padding: 30px; 
            border-radius: 20px; 
            width: 100%; /* Cambiado de max-width: 600px a width: 100% */
            box-shadow: 0 10px 30px rgba(0,0,0,0.02); 
            box-sizing: border-box;
        }   
     .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 12px; box-sizing: border-box; font-family: inherit; }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 14px; border-radius: 12px; cursor: pointer; width: 100%; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; background: var(--white); border-radius: 20px; overflow: hidden; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #fafcfe; color: #a3aed0; font-weight: 600; }
        .status-pill { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-done { background: #e6f9f1; color: var(--done); }
        .status-canceled { background: #ffe6e6; color: #f53939; } /* NUEVO: ROJO PARA CANCELADOS */
        .status-open { background: #eef0ff; color: var(--open); }
        .status-process { background: #fff8e6; color: var(--process); }
        .status-done { background: #e6f9f1; color: var(--done); }
        /* NUEVOS ESTADOS */
        .status-pause { background: #ffeee6; color: #ff6b00; }
        .status-parts { background: #f0e6ff; color: #6b46c1; }
        /* Estilos adicionales para Detalle */
        .detail-info { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .info-box { background: #f8faff; padding: 10px 15px; border-radius: 10px; border: 1px solid #edf2f7; }
        .info-box small { color: #a3aed0; font-weight: bold; font-size: 11px; text-transform: uppercase; }
        .info-box p { margin: 5px 0; font-weight: 600; }
    
                    .work-type-toggle { 
                display: flex; 
                margin-bottom: 20px; 
                border-radius: 8px;
            }
            .work-type-btn { 
                flex: 1; 
                padding: 12px; 
                text-align: center; 
                cursor: pointer; 
                background: white; 
                color: #2b3674; 
                font-weight: 500; 
                border: 1px solid #d1d9e6; 
                transition: all 0.2s ease; 
            }
            .work-type-btn:first-child { border-radius: 8px 0 0 8px; border-right: none; }
            .work-type-btn:last-child { border-radius: 0 8px 8px 0; margin-left: -1px; }
            .work-type-btn.active { 
                background: #dbfff2; 
                color: #01b574; 
                border: 2px solid #01b574 !important; 
                font-weight: 600; 
                z-index: 2;
                border-radius: 8px;
            }

        /* Contenedor con scroll horizontal */
            .table-responsive-custom {
                width: 100%;
                overflow-x: auto; /* Permite el scroll horizontal */
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                background: white;
            }

            .table-custom {
                width: 100%;
                border-collapse: collapse;
                min-width: 1200px; /* Forzamos a que sea ancha para que aparezca el scroll */
            }

            /* Fijar la primera columna */
            .table-custom th:first-child, 
            .table-custom td:first-child {
                position: sticky;
                left: 0;
                background-color: #f8f9fa; /* Color de fondo para que no se vea transparente al deslizar */
                z-index: 10;
                border-right: 2px solid #e0e5f2;
            }

            .table-custom th:first-child {
                background-color: var(--primary); /* Color de tu encabezado */
                color: white;
            }  
            .work-type-btn.active {
            background-color: #00a86b;
            color: white;
            border-color: #00a86b;
             }
    /* 🪄 MAGIA CORREGIDA PARA CONGELAR 2 COLUMNAS EN LOCATIONS (CRUD) */
        .table-locations {
            border-collapse: separate !important; /* CRUCIAL: permite que el sticky funcione ocultando contenido */
            border-spacing: 0; /* Simula collapse pero permite sticky */
        }

        /* Congelar columna 1: Zona Principal */
        .table-locations th:nth-child(1),
        .table-locations td:nth-child(1) {
            position: sticky;
            left: 0;
            z-index: 12; /* Capa más alta */
            width: 170px !important; /* Ancho fijo PRECISO */
            min-width: 170px;
            background-color: #f8f9fa !important; /* Fondo SÓLIDO para ocultar lo de atrás al scrollar */
            border-right: none !important;
            box-shadow: inset -1px 0 #e0e5f2; /* Línea divisoria sutil */
        }

        /* Congelar columna 2: Área Específica */
        .table-locations th:nth-child(2),
        .table-locations td:nth-child(2) {
            position: sticky;
            left: 170px; /* Igual al ancho de la columna 1 */
            z-index: 11; /* Una capa abajito de la 1 */
            width: 250px !important; /* Ancho fijo PRECISO */
            min-width: 250px;
            background-color: #ffffff !important; /* Fondo SÓLIDO (imprescindible) */
            border-right: 2px solid #e0e5f2 !important; /* El borde divisoria final sólido */
        }

        /* Color de los dos encabezados congelados */
        .table-locations th:nth-child(1),
        .table-locations th:nth-child(2) {
            background-color: var(--primary) !important;
            color: white !important;
            z-index: 15; 
            /* Encabezados siempre encima de las filas */

        }    
        /* Estilos para Pestañas de Programación (Schedule) */
        .schedule-tabs { 
            display: flex; 
            background: #f4f7fe; 
            border-radius: 10px; 
            padding: 5px; 
            margin-bottom: 20px; 
        }
        .tab-btn { 
            flex: 1; 
            padding: 10px; 
            text-align: center; 
            cursor: pointer; 
            color: #a3aed0; 
            font-weight: 600; 
            border-radius: 8px; 
            transition: all 0.3s ease; 
        }
        .tab-btn.active { 
            background: white; 
            color: #01b574; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        }
        /* Asegurar que el calendario flotante se vea por encima de todo */
        .flatpickr-calendar {
            z-index: 9999 !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
            border: none !important;
        }
        /* Estilos para la info de mantenimiento en Detalles */
        .maint-badge { 
            padding: 4px 10px; 
            border-radius: 6px; 
            font-size: 11px; 
            font-weight: 800; 
            text-transform: uppercase; 
            display: inline-block;
            margin-bottom: 5px;
        }
        .badge-preventivo { background: #dbfff2; color: #01b574; }
        .badge-reactivo { background: #eef2ff; color: #4318ff; }
        
        .schedule-box {
            background: #fdfdfe;
            border: 1px dashed #e0e5f2;
            padding: 15px;
            border-radius: 12px;
            margin-top: 15px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
</style>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
<style>
/* Estilo para que el calendario combine con tu HelpDesk */
            #calendar {
                max-width: 100%;
                background: white;
                padding: 20px;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            }
            .fc-header-toolbar {
                margin-bottom: 20px !important;
            }
            .fc-button-primary {
                background-color: var(--primary) !important;
                border-color: var(--primary) !important;
                border-radius: 10px !important;
            }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
</head>
<body>


    <div class="sidebar">
    <div style="text-align: center; margin-bottom: 20px;">
        <h2 style="color: var(--primary); margin-bottom: 5px;">HelpDesk</h2>
        
        <div style="margin-top: 15px; padding: 10px;">
            
            <p style="margin: 0; font-weight: 700; font-size: 14px; color: var(--text-dark);"><?php echo $nombre_usuario; ?></p>
            <small style="color: #a3aed0; font-size: 11px; text-transform: capitalize;"><?php echo $rol_actual; ?></small>
        </div>
    </div>
        <a href="Index.php?vista=bienvenida" class="nav-item <?php echo $seccion == 'bienvenida' ? 'active' : ''; ?>">
        <i class="fa fa-home"></i> Inicio
    </a>
        <a href="Index.php?vista=nuevo" class="nav-item <?php echo $seccion == 'nuevo' ? 'active' : ''; ?>">
        <i class="fa fa-plus"></i> Nuevo Ticket
        </a>

        <a href="Index.php?vista=tabla" class="nav-item <?php echo $seccion == 'tabla' ? 'active' : ''; ?>">
            <div><i class="fa fa-list-alt"></i> Mis tickets</div>
            <?php if($total_activos > 0): ?>
                <span class="badge-menu badge-blue"><?php echo $total_activos; ?></span>
            <?php endif; ?>
        </a>
        
        
        
        <a href="Index.php?vista=requests" class="nav-item <?php echo $seccion == 'requests' ? 'active' : ''; ?>">
            <div><i class="fa fa-clipboard-list"></i> Requests</div>
            <?php if($total_requests > 0): ?>
                <span class="badge-menu badge-red"><?php echo $total_requests; ?></span>
            <?php endif; ?>
        </a>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 15px 0;">

            <a href="Index.php?vista=solicitudes_botones" class="nav-item <?php echo $seccion == 'solicitudes_botones' ? 'active' : ''; ?>">
            <div><i class="fa fa-th-list"></i> Solicitudes Botones</div>
            <?php if($total_pendientes_pos > 0): ?>
                <span class="badge-menu badge-red"><?php echo $total_pendientes_pos; ?></span>
            <?php endif; ?>
        </a>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 15px 0;">

        <a href="Index.php?vista=assets" class="nav-item <?php echo $seccion == 'assets' ? 'active' : ''; ?>">
        <i class="fa fa-cube"></i> Assets (Inventario)
        </a>
        <a href="Index.php?vista=consumibles" class="nav-item <?php echo $seccion == 'consumibles' ? 'active' : ''; ?>">
            <i class="fa fa-boxes"></i> Insumos y Partes
        </a>
        <a href="Index.php?vista=catalogos" class="nav-item">
           <i class="fa fa-folder-open"></i> Configurar Catálogos
        </a>
        <a href="Index.php?vista=locations" class="nav-item <?php echo $seccion == 'locations' ? 'active' : ''; ?>">
            <i class="fa fa-map-marker"></i> Locations
        </a>
        <a href="logout.php" class="nav-item" style="margin-top: auto;">
            <i class="fa fa-sign-out"></i> Cerrar Sesión
        </a>
    </div>
    

            
    <div class="main-content">
        <div class="stats-grid">
            <div class="stat-card card-open">
                <h4>Abiertos</h4>
                <p><?php echo $abiertos; ?></p>
            </div>
            <div class="stat-card card-process">
                <h4>En Proceso</h4>
                <p><?php echo $proceso; ?></p>
            </div>
            <div class="stat-card card-done">
                <h4>Resueltos</h4>
                <p><?php echo $resueltos; ?></p>
            </div>
        </div>



        <?php if ($seccion == 'bienvenida'): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 style="margin:0;">Panel de Actividades 👋</h1>
            <p style="color: #a3aed0;">Hola, <?php echo $nombre_usuario; ?>. Aquí tienes la programación de soporte.</p>
        </div>
    </div>

    <div id='calendar'></div>

    <?php
    $eventos = [];
    $sql_eventos = "SELECT id, asunto, fecha_programada, estado FROM tickets WHERE fecha_programada IS NOT NULL";
    $res_ev = mysqli_query($conexion, $sql_eventos);
    while($ev = mysqli_fetch_assoc($res_ev)){
        // Color según el estado
        $color = ($ev['estado'] == 'Abierto') ? '#4318ff' : (($ev['estado'] == 'En Proceso') ? '#ffb800' : '#01b574');
        
        $eventos[] = [
            'id'    => $ev['id'],
            'title' => "#" . $ev['id'] . " - " . $ev['asunto'],
            'start' => $ev['fecha_programada'],
            'color' => $color,
            'url'   => "Index.php?vista=ver_detalle&id=" . $ev['id']
        ];
    }
    ?>



        <?php elseif ($seccion == 'tabla'): ?>
            <h3>Listado de Tickets</h3>
            <div class="filter-tabs" style="display: flex; gap: 10px; margin-bottom: 20px; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); position: relative; z-index: 50;">
                
                <div style="position: relative;">
                    <button class="filter-btn" onclick="toggleDropdown('drop-status')" style="background: #eef2ff; border: 1px solid #e0e5f2; padding: 8px 15px; border-radius: 8px; cursor: pointer; color: #2b3674; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        Status 
                        <span id="badge-status" style="background: #01b574; color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; display: none;">0</span> 
                        <i class="fa fa-chevron-down" style="font-size: 10px; color: #a3aed0;"></i>
                    </button>
                    <div id="drop-status" class="filter-dropdown" style="display: none; position: absolute; top: 110%; left: 0; background: white; border: 1px solid #e0e5f2; border-radius: 8px; padding: 12px; width: 230px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f4f7fe; padding-bottom: 8px; margin-bottom: 10px; font-weight: bold; font-size: 13px; color: var(--text-dark);">
                            Status 
                            <span style="color: #a3aed0; cursor: pointer; font-weight: normal; font-size: 12px;" onclick="clearFilter('status')">Clear all</span>
                        </div>
                        <label style="display: block; padding: 6px; cursor: pointer; color: #4a5568;"><input type="checkbox" class="cb-filter" data-group="status" value="Abierto"> Abierto</label>
                        <label style="display: block; padding: 6px; cursor: pointer; color: #4a5568;"><input type="checkbox" class="cb-filter" data-group="status" value="En Proceso"> En Proceso</label>
                        <label style="display: block; padding: 6px; cursor: pointer; color: #4a5568;"><input type="checkbox" class="cb-filter" data-group="status" value="En pausa"> En pausa</label>
                        <label style="display: block; padding: 6px; cursor: pointer; color: #4a5568;"><input type="checkbox" class="cb-filter" data-group="status" value="Resuelto"> Resuelto</label>
                        <label style="display: block; padding: 6px; cursor: pointer; color: #4a5568;"><input type="checkbox" class="cb-filter" data-group="status" value="Esperando por partes"> Esperando por partes</label>
                    </div>
                </div>

                <div style="position: relative;">
                    <button class="filter-btn" onclick="toggleDropdown('drop-worktype')" style="background: #eef2ff; border: 1px solid #e0e5f2; padding: 8px 15px; border-radius: 8px; cursor: pointer; color: #2b3674; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        Work Type 
                        <span id="badge-worktype" style="background: #01b574; color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; display: none;">0</span> 
                        <i class="fa fa-chevron-down" style="font-size: 10px; color: #a3aed0;"></i>
                    </button>
                    <div id="drop-worktype" class="filter-dropdown" style="display: none; position: absolute; top: 110%; left: 0; background: white; border: 1px solid #e0e5f2; border-radius: 8px; padding: 12px; width: 200px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f4f7fe; padding-bottom: 8px; margin-bottom: 10px; font-weight: bold; font-size: 13px; color: var(--text-dark);">
                            Work Type 
                            <span style="color: #a3aed0; cursor: pointer; font-weight: normal; font-size: 12px;" onclick="clearFilter('worktype')">Clear all</span>
                        </div>
                        <label style="display: block; padding: 6px; cursor: pointer; color: #4a5568;"><input type="checkbox" class="cb-filter" data-group="worktype" value="Reactivo"> Reactive</label>
                        <label style="display: block; padding: 6px; cursor: pointer; color: #4a5568;"><input type="checkbox" class="cb-filter" data-group="worktype" value="Preventivo"> Preventive</label>
                    </div>
                </div>
                
                <button onclick="limpiarTodosLosFiltros()" style="margin-left: auto; background: white; border: 1px dashed #a3aed0; padding: 8px 15px; border-radius: 8px; cursor: pointer; color: #a3aed0; font-weight: 600; transition: 0.3s;">
                    <i class="fa fa-sync"></i> Limpiar Filtros
                </button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Asunto</th>
                        <th>Solicitante</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $res_tabla = mysqli_query($conexion, $sql_tickets);
                    while($row = mysqli_fetch_assoc($res_tabla)): 
                    ?>
                    <tr class="ticket-row" 
                        data-status="<?php echo htmlspecialchars($row['estado']); ?>"
                        data-worktype="<?php echo htmlspecialchars($row['tipo_trabajo'] ?: 'Reactivo'); ?>">
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo $row['asunto']; ?></td>
                        <td><?php echo $row['solicitante']; ?></td>
                        <td>
                            <?php 
                            if ($row['estado'] == 'Abierto') { $clase = 'status-open'; }
                            elseif ($row['estado'] == 'En Proceso') { $clase = 'status-process'; }
                            elseif ($row['estado'] == 'En pausa') { $clase = 'status-pause'; }
                            elseif ($row['estado'] == 'Esperando por partes') { $clase = 'status-parts'; }
                            elseif ($row['estado'] == 'Cancelado') { $clase = 'status-canceled'; } // AGREGAR ESTA
                            else { $clase = 'status-done'; }
                            ?>
                            <span class="status-pill <?php echo $clase; ?>"><?php echo htmlspecialchars($row['estado']); ?></span>
                        </td>
                        <td><a href="Index.php?vista=ver_detalle&id=<?php echo $row['id']; ?>" style="color: var(--primary);">Ver Detalle</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>


           
        <?php elseif ($seccion == 'ver_detalle' && isset($_GET['id'])): ?>
            <?php
            $id_ticket = mysqli_real_escape_string($conexion, $_GET['id']);
            $res_det = mysqli_query($conexion, "SELECT * FROM tickets WHERE id = '$id_ticket'");
            $t = mysqli_fetch_assoc($res_det);
            ?>
            <div class="form-container">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <?php 
                        $clase_tipo = (isset($t['tipo_trabajo']) && $t['tipo_trabajo'] == 'Preventivo') ? 'badge-preventivo' : 'badge-reactivo';
                        $tipo_texto = isset($t['tipo_trabajo']) ? $t['tipo_trabajo'] : 'Reactivo';
                        ?>
                        <span class="maint-badge <?php echo $clase_tipo; ?>">
                            <i class="fa <?php echo ($tipo_texto == 'Preventivo') ? 'fa-calendar-check' : 'fa-bolt'; ?>"></i> 
                            <?php echo $tipo_texto; ?>
                        </span>
                        <h3 style="margin: 5px 0 0 0;">Ticket #<?php echo $t['id']; ?></h3>
                    </div>
                    <div style="text-align: right;">
                    <a href="Index.php?vista=tabla" style="text-decoration:none; font-size:14px; color:var(--primary); display: block; margin-bottom: 10px;"><i class="fa fa-arrow-left"></i> Volver</a>
                    <?php 
                    if ($t['estado'] == 'Abierto') { $clase = 'status-open'; }
                    elseif ($t['estado'] == 'En Proceso') { $clase = 'status-process'; }
                    elseif ($t['estado'] == 'En pausa') { $clase = 'status-pause'; }
                    elseif ($t['estado'] == 'Esperando por partes') { $clase = 'status-parts'; }
                    
                    else { $clase = 'status-done'; }
                    ?>
                    <span class="status-pill <?php echo $clase; ?>">
                        <?php echo htmlspecialchars($t['estado']); ?>
                    </span>
                    </div>
                    </div>

                <div class="detail-info">
                    <div class="info-box"><small>Asunto / Tarea</small><p><?php echo htmlspecialchars($t['asunto']); ?></p></div>
                    <div class="info-box"><small>Solicitante</small><p><?php echo htmlspecialchars($t['solicitante']); ?></p></div>
                    <div class="info-box"><small>Departamento</small><p><?php echo htmlspecialchars($t['departamento']); ?></p></div>
                    <div class="info-box"><small>Prioridad</small><p><?php echo htmlspecialchars($t['prioridad']); ?></p></div>
                    
                    <?php if(!empty($t['correo_contacto']) || !empty($t['telefono_contacto'])): ?>
                    <div class="info-box" style="grid-column: span 2; background: #eef2ff; border: 1px solid #d1d9e6;">
                        <small style="color: var(--primary);">Datos de Contacto (Usuario Externo)</small>
                        <p style="margin-top: 8px;">
                            <?php if(!empty($t['correo_contacto'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($t['correo_contacto']); ?>" style="color: var(--text-dark); text-decoration: none; margin-right: 20px; font-weight: 600;">
                                    <i class="fa fa-envelope" style="color: var(--primary);"></i> <?php echo htmlspecialchars($t['correo_contacto']); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if(!empty($t['telefono_contacto'])): ?>
                                <span style="color: var(--text-dark); font-weight: 600;">
                                    <i class="fa fa-phone" style="color: var(--primary);"></i> <?php echo htmlspecialchars($t['telefono_contacto']); ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($t['fecha_programada'])): ?>
                <div class="schedule-box">
                    <div>
                        <small style="color: #a3aed0; font-weight: bold; font-size: 11px; text-transform: uppercase;"><i class="fa fa-calendar-day"></i> Fecha Programada</small>
                        <p style="color: var(--primary); font-weight: bold; margin: 5px 0 0 0; font-size: 15px;">
                            <?php echo date('d/m/Y h:i A', strtotime($t['fecha_programada'])); ?>
                        </p>
                    </div>
                    <div>
                        <small style="color: #a3aed0; font-weight: bold; font-size: 11px; text-transform: uppercase;"><i class="fa fa-sync"></i> Frecuencia</small>
                        <p style="margin: 5px 0 0 0; font-weight: 600;">
                            <?php echo htmlspecialchars($t['tipo_programacion'] ?: 'One-time'); ?>
                        </p>
                    </div>
                    <?php if(!empty($t['tiempo_completar'])): ?>
                    <div style="grid-column: span 2;">
                        <small style="color: #a3aed0; font-weight: bold; font-size: 11px; text-transform: uppercase;"><i class="fa fa-clock"></i> Tiempo Estimado</small>
                        <p style="margin: 5px 0 0 0; font-weight: 600;"><?php echo htmlspecialchars($t['tiempo_completar']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Descripción del Problema</label>
                    <div style="background:#f4f7fe; padding:15px; border-radius:12px; font-size:14px; line-height:1.5;">
                        <?php echo $t['descripcion']; ?>
                    </div>
                </div>

                <?php
                $sql_tareas = "SELECT * FROM ticket_checklist WHERE ticket_id = '$id_ticket'";
                $res_tareas = mysqli_query($conexion, $sql_tareas);
                
                if(mysqli_num_rows($res_tareas) > 0):
                ?>
                <div class="form-group" style="margin-top: 20px; padding: 20px; border: 1px solid #e0e5f2; border-radius: 12px; background: #fdfdfe;">
                    <h4 style="color: var(--primary); margin-top: 0; margin-bottom: 15px;"><i class="fa fa-tasks"></i> Tareas a Realizar</h4>
                    
                    <?php while($tarea = mysqli_fetch_assoc($res_tareas)): ?>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 12px; background: <?php echo $tarea['completada'] ? '#e6f9f1' : '#f4f7fe'; ?>; border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: background 0.3s;" id="label-tarea-<?php echo $tarea['id']; ?>">
                            
                            <input type="checkbox" class="task-checkbox" data-id="<?php echo $tarea['id']; ?>" <?php echo $tarea['completada'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer; margin: 0;">
                            
                            <span style="font-size: 15px; font-weight: 500; color: <?php echo $tarea['completada'] ? '#01b574' : 'var(--text-dark)'; ?>; text-decoration: <?php echo $tarea['completada'] ? 'line-through' : 'none'; ?>; transition: all 0.3s;" id="texto-tarea-<?php echo $tarea['id']; ?>">
                                <?php echo htmlspecialchars($tarea['tarea']); ?>
                            </span>
                            
                        </label>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>


                <form action="actualizar_ticket.php" method="POST" style="margin-top:25px; border-top: 1px solid var(--border); padding-top:20px;">
                    <input type="hidden" name="id_ticket" value="<?php echo $t['id']; ?>">
                    
                   
                        <div class="form-group">
                        <label>Asignado a (Selección Múltiple):</label>
                        <?php 
                        // Convertimos la cadena de la BD (ej: "Juan, Pedro") en un array para comparar
                        $asignados_actuales = explode(', ', $t['asignado_a']); 
                        ?>
                        <select name="asignado_a[]" class="select2-multiple" multiple="multiple" style="width: 100%;" <?php echo ($rol_actual == 'usuario') ? 'disabled' : ''; ?>>
                            <?php foreach($usuarios_lista as $tecnico): ?>
                                <option value="<?php echo $tecnico; ?>" <?php echo (in_array($tecnico, $asignados_actuales)) ? 'selected' : ''; ?>>
                                    <?php echo $tecnico; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #a3aed0;">Puedes elegir varios técnicos. Haz clic en la "x" para quitar a alguien.</small>
                    </div>
                   

                    <div class="form-group">
                        <label>Estado del Ticket:</label>
                        <select name="nuevo_estado" <?php echo ($rol_actual == 'usuario') ? 'disabled' : ''; ?>>
                            <option value="Abierto" <?php echo ($t['estado'] == 'Abierto') ? 'selected' : ''; ?>>Abierto</option>
                            <option value="En Proceso" <?php echo ($t['estado'] == 'En Proceso') ? 'selected' : ''; ?>>En Proceso</option>
                            <option value="En pausa" <?php echo ($t['estado'] == 'En pausa') ? 'selected' : ''; ?>>En pausa</option>
                            <option value="Esperando por partes" <?php echo ($t['estado'] == 'Esperando por partes') ? 'selected' : ''; ?>>Esperando por partes</option>
                            <option value="Resuelto" <?php echo ($t['estado'] == 'Resuelto') ? 'selected' : ''; ?>>Resuelto</option>
                            <option value="Cancelado" <?php echo ($t['estado'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-top: 15px; margin-bottom: 20px;">
                        <label style="color: #2b3674; font-weight: 600; display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <span><i class="fa fa-calendar-alt" style="color: var(--primary);"></i> <?php echo (isset($t['tipo_trabajo']) && $t['tipo_trabajo'] == 'Preventivo') ? 'Starting Date' : 'Due Date'; ?>:</span>
                            
                            <?php if (!empty($t['fecha_programada']) && $t['fecha_programada'] != '0000-00-00 00:00:00'): ?>
                                <span style="background: #eef2ff; color: var(--primary); font-weight: 800; padding: 4px 10px; border-radius: 8px; border: 1px dashed var(--primary); font-size: 13px;">
                                    <?php echo date('d/m/Y h:i A', strtotime($t['fecha_programada'])); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #a3aed0; font-style: italic; font-size: 13px;">(Sin fecha asignada)</span>
                            <?php endif; ?>
                        </label>
                        
                        <div style="position: relative; margin-top: 10px;">
                            <i class="fa fa-calendar-day" style="position: absolute; left: 12px; top: 14px; color: #a3aed0; z-index: 10;"></i>
                            <input type="text" name="nueva_fecha" class="datetimepicker" value="" 
                                   placeholder="Si deseas cambiarla, selecciona una nueva fecha aquí..." 
                                   style="width: 100%; padding: 12px 12px 12px 35px; border: 1px solid #d1d9e6; border-radius: 12px; cursor: pointer; background: white; font-family: inherit;">
                        </div>
                    </div>

                    <div style="margin-top: 20px; padding: 15px; background: #fff8e6; border-radius: 12px; border: 1px solid #ffeeba;">
                        <label><i class="fa fa-wrench"></i> ¿Se utilizó algún insumo?</label>
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-top: 10px;">
                            <select name="id_insumo">
                                <option value="">-- Ninguno / Solo mano de obra --</option>
                                <?php 
                                $insumos = mysqli_query($conexion, "SELECT id, nombre, stock_actual FROM consumibles WHERE stock_actual > 0");
                                while($i = mysqli_fetch_assoc($insumos)): 
                                ?>
                                    <option value="<?php echo $i['id']; ?>">
                                        <?php echo $i['nombre']; ?> (Disp: <?php echo $i['stock_actual']; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <input type="number" name="cantidad_usada" value="1" min="1" placeholder="Cant.">
                        </div>
                    </div>
                    <?php if ($rol_actual == 'admin' || $rol_actual == 'tecnico'): ?>
                        <button type="submit" class="btn-primary">Actualizar Ticket</button>
                    <?php endif; ?>
                </form>
            </div>

        <div class="form-container" style="margin-top: 25px;">
                <h4 style="color: var(--primary); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                    <i class="fa fa-comments"></i> Bitácora y Evidencia
                </h4>
                
                <div style="max-height: 400px; overflow-y: auto; margin-bottom: 20px; padding-right: 10px;">
                    <?php
                    $sql_comentarios = "SELECT * FROM comentarios_ticket WHERE ticket_id = '$id_ticket' ORDER BY fecha ASC";
                    $res_comentarios = mysqli_query($conexion, $sql_comentarios);
                    
                    if(mysqli_num_rows($res_comentarios) > 0):
                        while($com = mysqli_fetch_assoc($res_comentarios)):
                    ?>
                        <div style="background: #f8faff; padding: 15px; border-radius: 12px; margin-bottom: 15px; border-left: 4px solid var(--primary);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <strong style="color: var(--text-dark); font-size: 14px;">
                                    <i class="fa fa-user-circle" style="color: #a3aed0;"></i> <?php echo $com['usuario']; ?>
                                </strong>
                                <small style="color: #a3aed0; font-weight: 500;">
                                    <?php echo date('d/m/Y h:i A', strtotime($com['fecha'])); ?>
                                </small>
                            </div>
                            
                            <?php if(!empty($com['comentario'])): ?>
                                <p style="margin: 0; font-size: 14px; color: #4a5568; line-height: 1.5;">
                                    <?php echo nl2br(htmlspecialchars($com['comentario'])); ?>
                                </p>
                            <?php endif; ?>

                            <?php if(!empty($com['imagen_url'])): ?>
                                <div style="margin-top: 10px;">
                                    <a href="<?php echo $com['imagen_url']; ?>" target="_blank" title="Clic para ver en grande">
                                        <img src="<?php echo $com['imagen_url']; ?>" alt="Evidencia adjunta" style="max-width: 250px; max-height: 200px; border-radius: 8px; border: 1px solid #e0e5f2; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <p style="text-align: center; color: #a3aed0; font-size: 14px; font-style: italic; padding: 20px 0;">
                            Aún no hay comentarios ni actualizaciones en este ticket.
                        </p>
                    <?php endif; ?>
                </div>

                <form action="guardar_comentario.php" method="POST" enctype="multipart/form-data" style="background: #fafcfe; padding: 20px; border-radius: 12px; border: 1px solid var(--border);">
                    <input type="hidden" name="ticket_id" value="<?php echo $id_ticket; ?>">
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <textarea name="comentario" rows="2" placeholder="Escribe tu actualización o describe la imagen..." style="resize: vertical; border: 1px solid #d1d9e6;"></textarea>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 1;">
                            <label for="archivo_evidencia" style="cursor: pointer; color: var(--primary); font-size: 14px; font-weight: 600;">
                                <i class="fa fa-camera"></i> Adjuntar Evidencia (Opcional)
                            </label>
                            <input type="file" name="imagen" id="archivo_evidencia" accept="image/png, image/jpeg, image/jpg" style="display: block; margin-top: 8px; font-size: 12px;">
                        </div>
        

                        <button type="submit" class="btn-primary" style="width: auto; padding: 10px 25px;">
                            <i class="fa fa-paper-plane"></i> Enviar
                        </button>
                    </div>
                </form>
            </div>

        <?php elseif ($seccion == 'nuevo'): ?>
    <div class="form-container">
        <h3 style="margin-top: 0; margin-bottom: 25px;">Crear Nuevo Ticket / Work Order</h3>
        <form action="guardar_ticket.php" method="POST">
            
            <div class="form-group">
                <label>Work Type <i class="fa fa-info-circle" style="color:#a3aed0;" title="Reactive: Falla reportada | Preventive: Mantenimiento programado"></i></label>
                <div class="work-type-toggle">
                    <div class="work-type-btn active" id="btn-reactivo" onclick="setWorkType('Reactivo')">Reactive</div>
                    <button type="button" class="work-type-btn" id="btn-preventivo">Preventive</button>
                </div>
                <input type="hidden" name="tipo_trabajo" id="tipo_trabajo" value="Reactivo">
            </div>

            <div class="form-group">
                <label>Asunto del Problema / Tarea</label>
                <input type="text" name="asunto" placeholder="Ej: Mi computadora no enciende..." required>
            </div>

            <div id="opciones-fecha" class="form-group">
                <label id="label-fecha">Due Date (Fecha límite opcional)</label>
                <div style="position: relative;">
                    <i class="fa fa-calendar-alt" style="position: absolute; left: 12px; top: 14px; color: #a3aed0;"></i>
                    <input type="text" name="fecha_programada" class="datetimepicker" placeholder="dd/mm/yyyy HH:mm" style="padding-left: 35px;">
                </div>
            </div>

            <div id="opciones-preventivo" style="display: none; border: 1px solid #e0e5f2; padding: 25px; border-radius: 12px; background: #fdfdfe; margin-bottom: 20px;">
                <label style="font-size: 16px; margin-bottom: 15px; display: block;">Schedule (Programación)</label>
                
                <div class="schedule-tabs">
                    <div class="tab-btn active" id="tab-onetime" onclick="setScheduleTab('One-time')">One-time</div>
                    <div class="tab-btn" id="tab-persistent" onclick="setScheduleTab('Persistent')">Persistent</div>
                    <div class="tab-btn" id="tab-floating" onclick="setScheduleTab('Floating')">Floating</div>
                </div>
                <input type="hidden" name="tipo_programacion" id="tipo_programacion" value="One-time">

                <div id="repeats-container" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label>Repeats every</label>
                            <input type="number" name="repeticion_numero" value="1" min="1">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <select name="repeticion_tipo">
                                <option value="Day">Day(s)</option>
                                <option value="Week">Week(s)</option>
                                <option value="Month">Month(s)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Time to complete (Tiempo estimado)</label>
                    <input type="text" name="tiempo_completar" placeholder="Ej: 1 hour">
                </div>
                
                <p id="schedule-hint" style="font-size: 12px; color: #2b3674; margin-top: 20px; padding-top: 15px; border-top: 1px dashed #e0e5f2;">
                    Se programará una sola vez a partir de la fecha elegida.
                </p>
            </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        
                            <div class="form-group">
                            <label>Asignar a Técnico(s)</label>
                            <select name="asignado_a[]" class="select2-multiple" multiple="multiple" style="width: 100%;" <?php echo ($rol_actual == 'usuario') ? 'disabled' : ''; ?>>
                                <?php foreach($usuarios_lista as $tecnico): ?>
                                    <option value="<?php echo $tecnico; ?>"><?php echo $tecnico; ?></option>
                                <?php endforeach; ?>
                            </select>
                        
                            </div>

                       <div class="form-group">
                            <label>Estado Inicial</label>
                            <select name="estado" <?php echo ($rol_actual == 'usuario') ? 'disabled' : ''; ?>>
                                <option value="Abierto" selected>Abierto</option>
                                <option value="En Proceso">En Proceso</option>
                                <option value="En pausa">En pausa</option>
                                <option value="Esperando por partes">Esperando por partes</option>
                                <option value="Resuelto">Resuelto</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Prioridad</label>
                        <select name="prioridad">
                            <option value="Baja">Baja</option>
                            <option value="Media" selected>Media</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Descripción detallada</label>
                        <textarea name="descripcion" rows="5" placeholder="Explica brevemente lo que sucede..." required></textarea>
                    </div>

                    <input type="hidden" name="solicitante" value="<?php echo $nombre_usuario; ?>">
                    <input type="hidden" name="departamento" value="<?php echo $_SESSION['departamento'] ?? 'Sistemas'; ?>"> 
                 
                    
                                    <select name="departamento" class="form-control" required>
                                            <option value="" disabled selected>-- Selecciona la ubicación exacta --</option>
                                            <?php
                                            $sql_loc = "SELECT * FROM locations ORDER BY parent_id ASC, nombre_ubicacion ASC";
                                            $res_loc = mysqli_query($conexion, $sql_loc);
                                            
                                            $ubicaciones = [];
                                            while($row = mysqli_fetch_assoc($res_loc)) {
                                                $ubicaciones[] = $row;
                                            }

                                            foreach ($ubicaciones as $padre) {
                                                if ($padre['parent_id'] == NULL) { 
                                                    // Mostramos el padre sin intentar compararlo con $ticket
                                                    echo '<option value="'.$padre['nombre_ubicacion'].'" style="font-weight: bold; background-color: #f8f9fa;">' . $padre['nombre_ubicacion'] . '</option>';
                                                    
                                                    foreach ($ubicaciones as $hijo) {
                                                        if ($hijo['parent_id'] == $padre['id']) {
                                                            echo '<option value="'.$hijo['nombre_ubicacion'].'">&nbsp;&nbsp;&nbsp;↳ ' . $hijo['nombre_ubicacion'] . '</option>';
                                                            
                                                            foreach ($ubicaciones as $nieto) {
                                                                if ($nieto['parent_id'] == $hijo['id']) {
                                                                    echo '<option value="'.$nieto['nombre_ubicacion'].'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;↳ ' . $nieto['nombre_ubicacion'] . '</option>';
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            ?>
                                        </select>

    
                    <div class="form-group">
                        <label><i class="fa fa-box"></i> Insumo Utilizado (Opcional):</label>
                        <select name="id_insumo">
                            <option value="">-- Ninguno --</option>
                            <?php 
                            $res_insumos = mysqli_query($conexion, "SELECT * FROM consumibles WHERE stock_actual > 0");
                            while($ins = mysqli_fetch_assoc($res_insumos)): 
                            ?>
                                <option value="<?php echo $ins['id']; ?>">
                                    <?php echo $ins['nombre']; ?> (Stock: <?php echo $ins['stock_actual']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fa fa-calculator"></i> Cantidad Usada:</label>
                        <input type="number" name="cantidad_usada" value="0" min="0">
                    </div>

                    <div class="form-group" style="margin-top: 30px; padding: 20px; border: 1px solid #e0e5f2; border-radius: 12px; background: #fdfdfe;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <label style="margin-bottom: 0;"><i class="fa fa-check-square"></i> Checklist de Tareas (Opcional)</label>
                    <button type="button" onclick="agregarTareaChecklist()" style="background: white; border: 1px solid var(--primary); color: var(--primary); padding: 5px 12px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px;">
                        <i class="fa fa-plus"></i> Add Field
                    </button>
                </div>
                
                <div id="contenedor-checklist">
                    </div>
                
                <small style="color: #a3aed0; display: block; margin-top: 10px;">
                    Agrega pasos específicos o requerimientos que el técnico debe marcar como completados.
                </small>
            </div>

                    <button type="submit" class="btn-primary">Generar Ticket</button>
                </form>
            </div>
       

        <?php elseif ($seccion == 'requests'): ?>
            <div class="header-seccion" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3 style="margin:0;"><i class="fa fa-clipboard-list"></i> Gestión de Requests (Triage)</h3>
                    <p style="color: #a3aed0; margin-top:5px;">Solicitudes externas pendientes de validación y asignación.</p>
                </div>
            </div>
            
            <div class="table-responsive-custom" style="background: white; padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Falla Reportada (Asunto)</th>
                            <th>Solicitante</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Magia: Traemos SOLO los tickets con estado 'Request'
                        $sql_req = "SELECT * FROM tickets WHERE estado = 'Request' ORDER BY id DESC";
                        $res_req = mysqli_query($conexion, $sql_req);
                        if(mysqli_num_rows($res_req) > 0):
                            while($row = mysqli_fetch_assoc($res_req)): 
                        ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['asunto']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($row['solicitante']); ?></td>
                            <td><i class="fa fa-map-marker" style="color: #a3aed0;"></i> <?php echo htmlspecialchars($row['departamento']); ?></td>
                            <td>
                                <span class="status-pill" style="background: #fff0f0; color: #f53939;">
                                    <i class="fa fa-clock"></i> Pendiente
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="Index.php?vista=ver_detalle&id=<?php echo $row['id']; ?>" class="btn-primary" style="padding: 8px 15px; font-size: 12px; text-decoration: none; border-radius: 8px; background: #ffb800; color: #fff; white-space: nowrap;">
                                        <i class="fa fa-check"></i> Validar
                                    </a>
                                    <button onclick="rechazarRequest(<?php echo $row['id']; ?>)" style="padding: 8px 15px; font-size: 12px; border: none; border-radius: 8px; background: #ffe6e6; color: #f53939; cursor: pointer; font-weight: bold; transition: 0.3s; white-space: nowrap;">
                                        <i class="fa fa-times"></i> Rechazar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #a3aed0;">
                                <i class="fa fa-check-circle" style="font-size: 40px; display: block; margin-bottom: 10px; color: #01b574;"></i>
                                ¡Excelente! Bandeja limpia. No hay solicitudes por validar.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($seccion == 'work_orders'): ?>
            <div class="header-seccion">
                <h3><i class="fa fa-tasks"></i> Work Orders (Órdenes de Trabajo)</h3>
                <p>Seguimiento detallado de tareas técnicas asignadas.</p>
            </div>
            <div class="form-container" style="max-width: 100%;">
                <p style="color: #a3aed0;">Contenido de Work Orders en desarrollo...</p>
            </div>

        <?php elseif ($seccion == 'pms'): ?>
            <div class="header-seccion">
                <h3><i class="fa fa-calendar-check"></i> Preventive Maintenance (PMs)</h3>
                <p>Calendario y registro de mantenimientos preventivos.</p>
            </div>
            <div class="form-container" style="max-width: 100%;">
                <p style="color: #a3aed0;">Contenido de PMs en desarrollo...</p>
            </div>

        <?php elseif ($seccion == 'procedures'): ?>
            <div class="header-seccion">
                <h3><i class="fa fa-book"></i> Procedures & Manuals</h3>
                <p>Biblioteca de procedimientos operativos y guías técnicas.</p>
            </div>
            <div class="form-container" style="max-width: 100%;">
                <p style="color: #a3aed0;">Contenido de Procedures en desarrollo...</p>
            </div>

        <?php elseif ($seccion == 'assets'): ?>
            <div class="header-seccion" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3><i class="fa fa-cube"></i> Inventario de Activos (Assets)</h3>
                    <p>Control de hardware y equipos registrados.</p>
                </div>
                <a href="Index.php?vista=nuevo_asset" class="btn-primary" style="text-decoration:none; width: auto; padding: 10px 20px;">
                    <i class="fa fa-plus"></i> Registrar Equipo
                </a>
            </div>

            <div style="background: white; padding: 15px; border-radius: 15px; margin-bottom: 15px; display: flex; gap: 10px; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div style="flex: 0 0 200px;">
                    <select id="tipoBusquedaAssets" style="width: 100%; padding: 10px; border: 1px solid #e0e5f2; border-radius: 10px; color: var(--text-dark); font-weight: 600; cursor: pointer;">
                        <option value="todos">Buscar en todo...</option>
                        <option value="equipo">Equipo / Marca</option>
                        <option value="tipo">Tipo de Activo</option>
                        <option value="serie">S/N (Serie)</option>
                        <option value="estado">Estado</option>
                        <option value="ubicacion">Ubicación</option>
                    </select>
                </div>
                <div style="position: relative; flex: 1;">
                    <i class="fa fa-search" style="position: absolute; left: 12px; top: 12px; color: #a3aed0;"></i>
                    <input type="text" id="busquedaAssets" placeholder="Escribe tu búsqueda aquí..." style="width: 100%; padding: 10px 10px 10px 40px; border: 1px solid #e0e5f2; border-radius: 10px;">
                </div>
                <button onclick="limpiarFiltrosAssets()" class="btn-secondary" style="padding: 10px 20px; border-radius: 10px; border: 1px solid #e0e5f2; background: #f4f7fe; color: var(--text-dark); font-weight: 600; cursor: pointer;">
                    <i class="fa fa-sync"></i> Limpiar
                </button>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 0 10px; flex-wrap: wrap; gap: 15px;">
                <div style="white-space: nowrap;">
                    <label style="color: var(--text-dark); font-weight: 600; font-size: 14px; margin: 0;">
                        Mostrar 
                        <select id="limiteAssets" style="padding: 6px; border-radius: 8px; border: 1px solid #e0e5f2; margin: 0 5px; font-weight: bold; color: var(--primary); cursor: pointer;">
                            
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="todos">Todos</option>
                        </select>
                        equipos por página
                    </label>
                </div>
                <div id="paginacionAssets" style="display: flex; gap: 5px; flex-wrap: wrap;">
                    </div>
            </div>


            <div class="table-responsive-custom" style="background: var(--white); padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Equipo / Detalles</th>
                            <th>Tipo & Serie</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-assets">
                        <?php 
                        // Magia SQL para traer la info y también el nombre del equipo "Padre"
                        $sql_assets = "SELECT a.*, p.nombre_equipo AS parent_name 
                                       FROM assets a 
                                       LEFT JOIN assets p ON a.parent_id = p.id 
                                       ORDER BY a.id DESC";
                        $res_assets = mysqli_query($conexion, $sql_assets);
                        
                        if(mysqli_num_rows($res_assets) > 0):
                            while($asset = mysqli_fetch_assoc($res_assets)): 
                        ?>
                        <tr>
                            <td><span class="status-pill" style="background: #f4f7fe; color: #2b3674; font-weight: 800;">#<?php echo $asset['id']; ?></span></td>
                            <td>
                                <strong style="color: var(--primary); font-size: 15px;"><?php echo htmlspecialchars($asset['nombre_equipo']); ?></strong><br>
                                <small style="color: var(--text-dark); font-weight: 600;">
                                    <?php echo htmlspecialchars($asset['marca']); ?> 
                                    <?php if(!empty($asset['modelo'])) echo " <span style='color:#a3aed0;'>| Mod: " . htmlspecialchars($asset['modelo']) . "</span>"; ?>
                                </small>
                                <?php if(!empty($asset['descripcion'])): ?>
                                    <br><small style="color: #4a5568; font-style: italic;"><i class="fa fa-info-circle"></i> <?php echo htmlspecialchars($asset['descripcion']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: var(--text-dark);"><?php echo htmlspecialchars($asset['tipo']); ?></span><br>
                                <code style="font-size: 12px; color: var(--primary); background: #eef2ff; padding: 3px 8px; border-radius: 6px; display: inline-block; margin-top: 4px;">S/N: <?php echo htmlspecialchars($asset['serie']); ?></code>
                            </td>
                            <td>
                                <div><i class="fa fa-map-marker" style="color: #a3aed0;"></i> <strong><?php echo htmlspecialchars($asset['ubicacion']); ?></strong></div>
                                <?php if(!empty($asset['parent_name'])): ?>
                                    <div style="margin-top: 5px;">
                                        <small style="color: #d97706; background: #fef3c7; padding: 2px 8px; border-radius: 4px; font-weight: 700;">
                                            <i class="fa fa-link"></i> Conectado a: <?php echo htmlspecialchars($asset['parent_name']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $color = ($asset['estado'] == 'Operativo') ? 'var(--done)' : (($asset['estado'] == 'En Reparación') ? 'var(--process)' : '#f53939');
                                ?>
                                <span class="status-pill" style="background: <?php echo $color; ?>22; color: <?php echo $color; ?>; font-weight: bold;">
                                    <?php echo htmlspecialchars($asset['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <small style="display: block; color: #4a5568; font-weight: 600;"><i class="fa fa-user-circle" style="color: #01b574;"></i> <?php echo htmlspecialchars($asset['creado_por'] ?: 'Sistema'); ?></small>
                                <small style="color: #a3aed0;"><i class="fa fa-calendar-alt"></i> <?php echo !empty($asset['fecha_creacion']) ? date('d/m/Y', strtotime($asset['fecha_creacion'])) : '-'; ?></small>
                            </td>
                            <td>
                                <a href="Index.php?vista=editar_asset&id=<?php echo $asset['id']; ?>" style="color: #ffb800; font-size: 18px; margin-right: 15px; text-decoration: none; transition: 0.3s;" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <button onclick="eliminarAsset(<?php echo $asset['id']; ?>)" style="color: #f53939; background: none; border: none; cursor: pointer; font-size: 18px; transition: 0.3s;" title="Eliminar">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 50px; color: #a3aed0;">
                                <i class="fa fa-box-open" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                                Aún no hay activos registrados.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>



              <?php elseif ($seccion == 'solicitudes_botones'): ?>
            <?php
            mysqli_set_charset($conexion, "utf8");
            $sql_solicitudes = "SELECT s.*, p.nombre AS solicitante 
                                FROM solicitudes_pos s 
                                LEFT JOIN personal_acre p ON s.personal_id = p.id 
                                ORDER BY s.estado DESC, s.fecha_creacion DESC";
            $res_solicitudes = mysqli_query($conexion, $sql_solicitudes);
            ?>

            <div class="header-seccion">
                <h2><i class="fa fa-inbox"></i> Bandeja de Solicitudes POS</h2>
            </div>

            <div style="background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div>
                    <label style="font-size: 11px; color: #a3aed0;">FOLIO</label>
                    <input type="text" id="filter-folio" placeholder="Ej: 5" onkeyup="filtrarTablaPos()">
                </div>
                <div>
                    <label style="font-size: 11px; color: #a3aed0;">SOLICITANTE</label>
                    <input type="text" id="filter-nombre" placeholder="Buscar nombre..." onkeyup="filtrarTablaPos()">
                </div>
                <div>
                    <label style="font-size: 11px; color: #a3aed0;">FECHA</label>
                    <input type="date" id="filter-fecha" onchange="filtrarTablaPos()">
                </div>
                <div>
                    <label style="font-size: 11px; color: #a3aed0;">ESTADO</label>
                    <select id="filter-estado" onchange="filtrarTablaPos()">
                        
                        <option value="Pendiente">Pendiente</option>
                        <option value="Completado">Completado</option>
                        <option value="">Todos</option>
                    </select>
                </div>
            </div>

            <div class="contenedor-principal" style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow-x: auto;">
                <table id="tabla-solicitudes-pos" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: #555555; color: white;">
                            <th style="padding: 15px;">Folio</th>
                            <th style="padding: 15px;">Fecha Petición</th>
                            <th style="padding: 15px;">Solicitante</th>
                            <th style="padding: 15px;">Atendido Por</th>
                            <th style="padding: 15px;">Estado</th>
                            <th style="padding: 15px; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sol = mysqli_fetch_assoc($res_solicitudes)): ?>
                            <tr class="pos-row" 
                                data-folio="<?php echo $sol['id']; ?>" 
                                data-nombre="<?php echo strtolower($sol['solicitante']); ?>" 
                                data-fecha="<?php echo date('Y-m-d', strtotime($sol['fecha_creacion'])); ?>" 
                                data-estado="<?php echo $sol['estado']; ?>"
                                style="border-bottom: 1px solid #eee;">
                                <td style="padding: 15px;"><b>#<?php echo $sol['id']; ?></b></td>
                                <td style="padding: 15px;"><?php echo date('d/m/Y', strtotime($sol['fecha_creacion'])); ?></td>
                                <td style="padding: 15px;"><?php echo $sol['solicitante']; ?></td>
                                <td style="padding: 15px; color: #01b574; font-weight: 600;">
                                    <?php echo !empty($sol['tecnico_cierre']) ? $sol['tecnico_cierre'] : '---'; ?>
                                </td>
                                <td style="padding: 15px;">
                                    <span class="status-pill <?php echo ($sol['estado'] == 'Pendiente') ? 'status-process' : 'status-done'; ?>">
                                        <?php echo $sol['estado']; ?>
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center;">
                                    <a href="ver_detalle_solicitud.php?id=<?php echo $sol['id']; ?>" style="color: var(--primary); font-weight: bold; text-decoration: none;">Ver Detalle</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

          


                <?php elseif ($seccion == 'editar_asset' && isset($_GET['id'])): ?>
            <?php 
            $id_asset = intval($_GET['id']);
            $res_asset = mysqli_query($conexion, "SELECT * FROM assets WHERE id = $id_asset");
            $a = mysqli_fetch_assoc($res_asset);

            if (!$a):
                echo "<p>⚠️ Equipo no encontrado.</p>";
            else:
            ?>
            <div class="form-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="margin:0; color: #856404;"><i class="fa fa-edit"></i> Editando Equipo: <?php echo htmlspecialchars($a['nombre_equipo']); ?></h3>
                    <a href="Index.php?vista=assets" style="color: var(--primary); text-decoration: none; font-weight: 600;"><i class="fa fa-arrow-left"></i> Volver al Inventario</a>
                </div>

                <form action="actualizar_asset.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Nombre del Equipo / Identificador</label>
                            <input type="text" name="nombre_equipo" value="<?php echo htmlspecialchars($a['nombre_equipo']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>📍 Ubicación Exacta</label>
                            <select name="ubicacion" class="select2-search" style="width: 100%;" required>
                                <option value="">-- Escribe para buscar una ubicación --</option>
                                <?php foreach ($lista_agrupada as $zona => $areas): ?>
                                    <optgroup label="ZONA: <?php echo $zona; ?>">
                                        <?php foreach ($areas as $area): 
                                            // Usamos solo el nombre del área para que coincida con lo que subimos del Excel
                                            $nombre_area = $area['nombre'];
                                            $sel = ($a['ubicacion'] == $nombre_area) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo htmlspecialchars($nombre_area); ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($nombre_area); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Tipo de Activo</label>
                            <select name="tipo">
                                <option value="Equipo de computo" <?php if($a['tipo']=='Equipo de computo') echo 'selected'; ?>>Equipo de computo</option>
                                <option value="Seguridad" <?php if($a['tipo']=='Seguridad') echo 'selected'; ?>>Seguridad</option>
                                <option value="Equipo audiovisual" <?php if($a['tipo']=='Equipo audiovisual') echo 'selected'; ?>>Equipo audiovisual</option>
                                <option value="Escaneo e Impresión" <?php if($a['tipo']=='Escaneo e Impresión') echo 'selected'; ?>>Escaneo e Impresión</option>
                                <option value="Telefonía" <?php if($a['tipo']=='Telefonía') echo 'selected'; ?>>Telefonía</option>
                                <option value="Otro" <?php if($a['tipo']=='Otro') echo 'selected'; ?>>Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Marca</label>
                            <input type="text" name="marca" value="<?php echo htmlspecialchars($a['marca']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Modelo</label>
                            <input type="text" name="modelo" value="<?php echo htmlspecialchars($a['modelo']); ?>">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Número de Serie (S/N)</label>
                            <input type="text" name="serie" value="<?php echo htmlspecialchars($a['serie']); ?>" style="font-family: monospace;">
                        </div>
                        <div class="form-group">
                            <label>Estado Actual</label>
                            <select name="estado">
                                <option value="Operativo" <?php if($a['estado']=='Operativo') echo 'selected'; ?>>✅ Operativo</option>
                                <option value="En Reparación" <?php if($a['estado']=='En Reparación') echo 'selected'; ?>>🛠️ En Reparación</option>
                                <option value="Baja / Dañado" <?php if($a['estado']=='Baja / Dañado') echo 'selected'; ?>>❌ Baja / Dañado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>🔗 Pertenece al equipo:</label>
                            <select name="parent_id" class="select2-search" style="width: 100%;">
                                <option value="">(Ninguno / Es equipo principal)</option>
                                <?php 
                                $res_p = mysqli_query($conexion, "SELECT id, nombre_equipo FROM assets WHERE id != $id_asset ORDER BY nombre_equipo ASC");
                                while($p = mysqli_fetch_assoc($res_p)):
                                    $sel_p = ($a['parent_id'] == $p['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $sel_p; ?>><?php echo htmlspecialchars($p['nombre_equipo']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripción / Notas Técnicas</label>
                        <textarea name="descripcion" rows="3"><?php echo htmlspecialchars($a['descripcion']); ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8faff; padding: 15px; border-radius: 12px; border: 1px solid #eef2ff; margin-bottom: 20px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label><i class="fa fa-truck"></i> Proveedores</label>
                            <input type="text" name="proveedores" value="<?php echo htmlspecialchars($a['proveedores']); ?>" placeholder="Nombres de proveedores...">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label><i class="fa fa-link"></i> URL / Soporte</label>
                            <input type="text" name="url" value="<?php echo htmlspecialchars($a['url']); ?>" placeholder="https://...">
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 30px;">
                        <button type="submit" class="btn-primary" style="width: auto; padding: 15px 40px; background: #856404; border: none; font-size: 16px;">
                            <i class="fa fa-save"></i> Guardar Cambios en el Inventario
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>


        <?php elseif ($seccion == 'nuevo_asset'): ?>
            <div class="form-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="margin:0; color: var(--primary);"><i class="fa fa-plus-circle"></i> Registrar Nuevo Activo</h3>
                    <a href="Index.php?vista=assets" style="color: var(--primary); text-decoration: none; font-weight: 600;"><i class="fa fa-arrow-left"></i> Volver al Inventario</a>
                </div>
                
                <form action="guardar_asset.php" method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Nombre del Equipo / Identificador</label>
                            <input type="text" name="nombre_equipo" placeholder="Ej: Laptop Contabilidad 01" required>
                        </div>
                        <div class="form-group">
                            <label>📍 Ubicación Exacta</label>
                            <select name="ubicacion" class="select2-search" style="width: 100%;" required>
                                <option value="">-- Selecciona un área --</option>
                                <?php foreach ($lista_agrupada as $zona => $areas): ?>
                                    <optgroup label="ZONA: <?php echo htmlspecialchars($zona); ?>">
                                        <?php foreach ($areas as $area): ?>
                                            <option value="<?php echo htmlspecialchars($area['nombre']); ?>"><?php echo htmlspecialchars($area['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Tipo de Activo</label>
                            <select name="tipo">
                                <option value="Equipo de computo">Equipo de computo</option>
                                <option value="Seguridad">Seguridad</option>
                                <option value="Equipo audiovisual">Equipo audiovisual</option>
                                <option value="Escaneo e Impresión">Escaneo e Impresión</option>
                                <option value="Telefonía">Telefonía</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Marca</label>
                            <input type="text" name="marca" placeholder="Dell, HP, Lenovo..." required>
                        </div>
                        <div class="form-group">
                            <label>Modelo</label>
                            <input type="text" name="modelo" placeholder="Ej: Latitude 5500">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Número de Serie (S/N)</label>
                            <input type="text" name="serie" placeholder="Obligatorio" style="font-family: monospace;" required>
                        </div>
                        <div class="form-group">
                            <label>Estado Inicial</label>
                            <select name="estado">
                                <option value="Operativo" selected>✅ Operativo</option>
                                <option value="En Reparación">🛠️ En Reparación</option>
                                <option value="Baja / Dañado">❌ Baja / Dañado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>🔗 Pertenece al equipo:</label>
                            <select name="parent_id" class="select2-search" style="width: 100%;">
                                <option value="">(Ninguno / Es equipo principal)</option>
                                <?php 
                                $res_p = mysqli_query($conexion, "SELECT id, nombre_equipo FROM assets ORDER BY nombre_equipo ASC");
                                while($p = mysqli_fetch_assoc($res_p)):
                                ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre_equipo']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripción / Notas Técnicas</label>
                        <textarea name="descripcion" rows="3" placeholder="Detalles de configuración, accesorios incluidos, etc..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8faff; padding: 15px; border-radius: 12px; border: 1px solid #eef2ff; margin-bottom: 20px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label><i class="fa fa-truck"></i> Proveedor</label>
                            <input type="text" name="proveedores" placeholder="Ej: Ohm Distribution">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label><i class="fa fa-link"></i> URL / Soporte</label>
                            <input type="text" name="url" placeholder="https://soporte.marca.com/...">
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 30px;">
                        <button type="submit" class="btn-primary" style="width: auto; padding: 15px 40px; font-size: 16px;">
                            <i class="fa fa-save"></i> Guardar en Inventario
                        </button>
                    </div>
                </form>
            </div>


        <?php elseif ($seccion == 'locations'): ?>
            <?php 
            // 🔍 Buscamos los datos solo si se presionó el botón de editar
            $loc_edit = null;
            if (isset($_GET['editar_id'])) {
                $id_edit = intval($_GET['editar_id']);
                $res_edit = mysqli_query($conexion, "SELECT * FROM locations WHERE id = $id_edit");
                $loc_edit = mysqli_fetch_assoc($res_edit);
            }
            ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin:0;"><i class="fa fa-map-marker"></i> Directorio de Ubicaciones</h3>
                <button onclick="toggleForm('form-nueva-loc')" class="btn-primary" style="width: auto; padding: 10px 20px;">+ Nueva Ubicación</button>
            </div>

            <?php if ($loc_edit): ?>
            <div id="form-editar-loc" style="background: #fff9e6; padding: 25px; border-radius: 20px; margin-bottom: 25px; border: 2px solid #ffcc00; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                <h4 style="margin-top:0; color: #856404;"><i class="fa fa-edit"></i> Editando: <?php echo htmlspecialchars($loc_edit['nombre_ubicacion']); ?></h4>
                <form action="actualizar_location.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $loc_edit['id']; ?>">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label>Nombre de la Ubicación</label>
                            <input type="text" name="nombre_ubicacion" value="<?php echo htmlspecialchars($loc_edit['nombre_ubicacion']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Mover a otra Zona (Padre)</label>
                            <select name="parent_id">
                                <option value="">Es una ZONA principal</option>
                                <?php 
                                // Listamos todos los padres excepto a sí mismo
                                $res_z = mysqli_query($conexion, "SELECT id, nombre_ubicacion FROM locations WHERE parent_id IS NULL AND id != ".$loc_edit['id']." ORDER BY nombre_ubicacion ASC");
                                while($z = mysqli_fetch_assoc($res_z)) {
                                    $selected = ($z['id'] == $loc_edit['parent_id']) ? 'selected' : '';
                                    echo "<option value='".$z['id']."' $selected>Dentro de: ".$z['nombre_ubicacion']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Descripción Actualizada</label>
                        <input type="text" name="descripcion" value="<?php echo htmlspecialchars($loc_edit['descripcion']); ?>" placeholder="Detalles de la ubicación...">
                    </div>
                    <div style="text-align: right;">
                        <a href="Index.php?vista=locations" class="btn-secondary" style="text-decoration: none; padding: 12px 20px; border-radius: 12px; border: 1px solid #ccc; background: #eee; color: #333; font-weight: bold; margin-right: 10px;">Cancelar</a>
                        <button type="submit" class="btn-primary" style="width: auto; padding: 12px 25px; background: #856404; border: none;">Guardar Cambios</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div id="form-nueva-loc" style="display:none; background: var(--white); padding: 25px; border-radius: 20px; margin-bottom: 25px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <h4 style="margin-top:0; color: var(--primary);"><i class="fa fa-plus-circle"></i> Registrar Nueva Zona o Área</h4>
                <form action="guardar_location.php" method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label>Nombre de la Ubicación</label>
                            <input type="text" name="nombre_ubicacion" placeholder="Ej: Oficina 201, Alberca, etc." required>
                        </div>
                        <div class="form-group">
                            <label>Pertenece a (Zona Padre)</label>
                            <select name="parent_id">
                                <option value="">Es una ZONA principal (No tiene padre)</option>
                                <?php 
                                $res_z = mysqli_query($conexion, "SELECT id, nombre_ubicacion FROM locations WHERE parent_id IS NULL ORDER BY nombre_ubicacion ASC");
                                while($z = mysqli_fetch_assoc($res_z)) echo "<option value='".$z['id']."'>Dentro de: ".$z['nombre_ubicacion']."</option>";
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Descripción (Opcional)</label>
                        <input type="text" name="descripcion" placeholder="Detalles de la ubicación, referencias...">
                    </div>
                    <div style="text-align: right;">
                        <button type="button" onclick="toggleForm('form-nueva-loc')" class="btn-secondary" style="padding: 12px 20px; border-radius: 12px; border: 1px solid #d1d9e6; background: #f4f7fe; color: #2b3674; font-weight: bold; cursor: pointer; margin-right: 10px;">Cancelar</button>
                        <button type="submit" class="btn-primary" style="width: auto; padding: 12px 25px;">Guardar Ubicación</button>
                    </div>
                </form>
            </div>

            <div style="background: white; padding: 15px; border-radius: 15px; margin-bottom: 20px; display: flex; gap: 10px; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div style="flex: 0 0 250px;">
                    <select id="filtro-zona" style="width: 100%; padding: 10px; border: 1px solid #e0e5f2; border-radius: 10px; color: var(--text-dark); font-weight: 600; cursor: pointer;">
                        <option value="todas">Todas las Zonas (Padres)</option>
                        <?php 
                        $res_padres = mysqli_query($conexion, "SELECT nombre_ubicacion FROM locations WHERE parent_id IS NULL ORDER BY nombre_ubicacion ASC");
                        while($p = mysqli_fetch_assoc($res_padres)) echo "<option value='".htmlspecialchars($p['nombre_ubicacion'])."'>".$p['nombre_ubicacion']."</option>";
                        ?>
                    </select>
                </div>
                <div style="position: relative; flex: 1;">
                    <i class="fa fa-search" style="position: absolute; left: 12px; top: 12px; color: #a3aed0;"></i>
                    <input type="text" id="busquedaLoc" placeholder="Buscar área, descripción o creador..." style="width: 100%; padding: 10px 10px 10px 40px; border: 1px solid #e0e5f2; border-radius: 10px;">
                </div>
                <button onclick="limpiarFiltrosLoc()" class="btn-secondary" style="padding: 10px 20px; border-radius: 10px; border: 1px solid #e0e5f2; background: #f4f7fe; color: var(--text-dark); font-weight: 600; cursor: pointer;">
                    <i class="fa fa-sync"></i> Limpiar
                </button>
            </div>

            <div class="table-responsive-custom" style="background: var(--white); padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <table class="table-custom table-locations">
                    <thead>
                        <tr>
                            <th>Zona Principal</th>
                            <th>Área Específica</th>
                            <th>Creado Por</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-loc">
                        <?php 
                        $sql_loc = "SELECT h.id, p.nombre_ubicacion AS zona, h.nombre_ubicacion AS area, h.created_by, h.created_at 
                                    FROM locations h 
                                    INNER JOIN locations p ON h.parent_id = p.id 
                                    ORDER BY p.nombre_ubicacion ASC, h.nombre_ubicacion ASC";
                        $res_loc = mysqli_query($conexion, $sql_loc);
                        while($l = mysqli_fetch_assoc($res_loc)): 
                        ?>
                        <tr class="loc-row" data-zona="<?php echo htmlspecialchars($l['zona']); ?>">
                            <td><span class="status-pill" style="background: #eef2ff; color: var(--primary); font-weight: bold;">
                                <?php echo htmlspecialchars($l['zona']); ?>
                            </span></td>
                            <td><strong><?php echo htmlspecialchars($l['area']); ?></strong></td>
                            
                            <td style="font-size: 13px;">
                                <i class="fa fa-user-circle" style="color: #01b574;"></i> <?php echo htmlspecialchars($l['created_by'] ?: 'Sistema'); ?>
                            </td>
                            <td style="font-size: 13px;">
                                <?php echo !empty($l['created_at']) ? date('d/m/Y', strtotime($l['created_at'])) : '-'; ?>
                            </td>
                            <td>
                                <a href="Index.php?vista=locations&editar_id=<?php echo $l['id']; ?>" style="color: #ffb800; font-size: 16px; margin-right: 15px; text-decoration: none;" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <button onclick="eliminarLocation(<?php echo $l['id']; ?>)" style="color: #f53939; background: none; border: none; cursor: pointer; font-size: 16px;" title="Eliminar">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

             
            <?php elseif ($seccion == 'consumibles'): ?>

                    <?php if (isset($_GET['msg'])): ?>
                    <?php if ($_GET['msg'] == 'updated'): ?>
                        <div style="background: #e6f9f1; color: #01b574; padding: 15px; border-radius: 12px; border: 1px solid #c3e6cb; margin-bottom: 20px; font-weight: bold; text-align: center;">
                            <i class="fa fa-check-circle"></i> ¡Información actualizada correctamente, bbe!
                        </div>
                    <?php elseif ($_GET['msg'] == 'error'): ?>
                        <div style="background: #ffe6e6; color: #f53939; padding: 15px; border-radius: 12px; border: 1px solid #f5c6cb; margin-bottom: 20px; font-weight: bold; text-align: center;">
                            <i class="fa fa-exclamation-triangle"></i> Hubo un error al guardar los cambios.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

         <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <h2 style="color: var(--text-dark); margin: 0;"><i class="fa fa-spray-can" style="color: var(--primary);"></i> Inventario de Consumibles</h2>
                    <p style="color: #a3aed0; margin: 5px 0 0 0;">Control de stock, tóneres, tintas y papelería.</p>
                </div>
                <a href="Index.php?vista=nuevo_consumible" class="btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 10px; width: auto;">
                    <i class="fa fa-plus-circle"></i> Agregar Insumo
                </a>
            </div>

            <div style="background: white; padding: 15px; border-radius: 15px; margin-bottom: 15px; display: flex; gap: 10px; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div style="flex: 0 0 200px;">
                    <select id="tipoBusquedaConsumibles" style="width: 100%; padding: 10px; border: 1px solid #e0e5f2; border-radius: 10px; color: var(--text-dark); font-weight: 600; cursor: pointer;">
                        <option value="todos">Buscar en todo...</option>
                        <option value="nombre">Nombre / Parte</option>
                        <option value="categoria">Categoría</option>
                        <option value="almacen">Almacén</option>
                    </select>
                </div>
                <div style="position: relative; flex: 1;">
                    <i class="fa fa-search" style="position: absolute; left: 12px; top: 12px; color: #a3aed0;"></i>
                    <input type="text" id="busquedaConsumibles" placeholder="Ej: Toner Negro, Hojas, etc..." style="width: 100%; padding: 10px 10px 10px 40px; border: 1px solid #e0e5f2; border-radius: 10px;">
                </div>
                <button onclick="limpiarFiltrosConsumibles()" class="btn-secondary" style="padding: 10px 20px; border-radius: 10px; border: 1px solid #e0e5f2; background: #f4f7fe; color: var(--text-dark); font-weight: 600; cursor: pointer;">
                    <i class="fa fa-sync"></i> Limpiar
                </button>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 0 10px; flex-wrap: wrap; gap: 15px;">
                <div style="white-space: nowrap;">
                    <label style="color: var(--text-dark); font-weight: 600; font-size: 14px; margin: 0;">
                        Mostrar 
                        <select id="limiteConsumibles" style="padding: 6px; border-radius: 8px; border: 1px solid #e0e5f2; margin: 0 5px; font-weight: bold; color: var(--primary); cursor: pointer;">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="todos">Todos</option>
                        </select>
                        insumos por página
                    </label>
                </div>
                <div id="paginacionConsumibles" style="display: flex; gap: 5px; flex-wrap: wrap;"></div>
            </div>

            <div class="table-responsive-custom" style="background: var(--white); padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Insumo / Descripción</th>
                            <th>Categoría & Parte</th>
                            <th>Stock</th>
                            <th>Ubicación / Almacén</th>
                            <th>Costo Unit.</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-consumibles">
                        <?php 
                        // SQL con Joins para traer nombres en lugar de IDs
                        $sql = "SELECT c.*, s.nombre AS almacen_nombre, u.nombre AS unidad_nombre, l.nombre_ubicacion AS loc_nombre 
                                FROM consumibles c
                                LEFT JOIN storerooms s ON c.id_storeroom = s.id
                                LEFT JOIN unidades_medida u ON c.id_unidad = u.id
                                LEFT JOIN locations l ON c.id_ubicacion = l.id
                                ORDER BY c.nombre ASC";
                        $res = mysqli_query($conexion, $sql);
                        
                        while($c = mysqli_fetch_assoc($res)): 
                            // Alerta de stock bajo
                            $alerta_stock = ($c['stock_actual'] <= $c['stock_minimo']) ? 'border-left: 5px solid #f53939;' : '';
                        ?>
                        <tr style="<?php echo $alerta_stock; ?>">
                            <td>
                                <strong style="color: var(--primary);"><?php echo htmlspecialchars($c['nombre']); ?></strong><br>
                                <small style="color: #a3aed0;"><?php echo htmlspecialchars($c['descripcion']); ?></small>
                            </td>
                            <td>
                                <span class="status-pill" style="background: #eef2ff; color: var(--primary);"><?php echo htmlspecialchars($c['categoria']); ?></span><br>
                                <code style="font-size: 11px;">P/N: <?php echo htmlspecialchars($c['n_parte']); ?></code>
                            </td>
                            <td>
                                <div style="font-size: 16px; font-weight: 800; color: <?php echo ($c['stock_actual'] <= $c['stock_minimo']) ? '#f53939' : 'var(--text-dark)'; ?>;">
                                    <?php echo $c['stock_actual']; ?> <small style="font-size: 10px; color: #a3aed0;"><?php echo $c['unidad_nombre']; ?></small>
                                </div>
                                <small style="color: #a3aed0;">Min: <?php echo $c['stock_minimo']; ?></small>
                            </td>
                            <td>
                                <small style="display:block;"><strong>📦 <?php echo $c['almacen_nombre'] ?: 'Sin Almacén'; ?></strong></small>
                                <small style="color: #a3aed0;"><i class="fa fa-map-marker"></i> <?php echo $c['loc_nombre'] ?: 'No asignada'; ?></small>
                            </td>
                            <td>
                                <strong style="color: #01b574;">$<?php echo number_format($c['costo_unitario'], 2); ?></strong>
                            </td>
                            <td>
                                <a href="Index.php?vista=editar_consumible&id=<?php echo $c['id']; ?>" style="color: #ffb800; font-size: 18px; margin-right: 15px;"><i class="fa fa-edit"></i></a>
                                <button onclick="eliminarConsumible(<?php echo $c['id']; ?>)" style="color: #f53939; background: none; border: none; cursor: pointer; font-size: 18px;"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
aqui finaliza el nuevo codigo de insumos

< aqui inicia el historial de insumos>   
            <div style="margin-top: 50px;">
                <h3 style="color: var(--text-dark);"><i class="fa fa-history"></i> Historial de Salidas</h3>
                <p style="color: #a3aed0; font-size: 14px; margin-bottom: 20px;">Registro de materiales descontados en tickets.</p>
                
                <div style="background: white; padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Ticket</th>
                                <th>Insumo</th>
                                <th>Cantidad</th>
                                <th>Atendido por</th>
                            </tr>
                        </thead>
                       <tbody>

< Modificamos la consulta para unirla con la tabla 'tickets' y traer el tecnico_cierre>

                   <?php 
                        $sql_h = "SELECT h.fecha, h.ticket_id, c.nombre, h.cantidad, t.tecnico_cierre 
                                FROM historial_insumos h
                                JOIN consumibles c ON h.insumo_id = c.id
                                JOIN tickets t ON h.ticket_id = t.id
                                ORDER BY h.fecha DESC LIMIT 10";
                                
                        $res_h = mysqli_query($conexion, $sql_h);
                        
                        if(mysqli_num_rows($res_h) > 0):
                            while($reg = mysqli_fetch_assoc($res_h)): 
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($reg['fecha'])); ?></td>
                            <td><a href="Index.php?vista=ver_detalle&id=<?php echo $reg['ticket_id']; ?>" style="color:var(--primary); font-weight:bold;">#<?php echo $reg['ticket_id']; ?></a></td>
                            <td><?php echo $reg['nombre']; ?></td>
                            <td><span style="color:#ff4d4d; font-weight:bold;">-<?php echo $reg['cantidad']; ?></span></td>
                            <td style="font-size: 0.9em; color: #2b3674;">
                                <i class="fa fa-user-check" style="color: #01b574;"></i> 
                                <?php echo !empty($reg['tecnico_cierre']) ? $reg['tecnico_cierre'] : 'Sistema'; ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px;">No hay movimientos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    </table>
                </div> 
            </div> 

     
    <?php elseif ($seccion == 'nuevo_consumible'): ?>
            <div class="form-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="margin:0; color: var(--primary);"><i class="fa fa-plus-circle"></i> Registrar Nuevo Insumo / Parte</h3>
                    <a href="Index.php?vista=consumibles" style="color: var(--primary); text-decoration: none; font-weight: 600;"><i class="fa fa-arrow-left"></i> Volver al Inventario</a>
                </div>
                
                <form action="guardar_consumible.php" method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Nombre del Insumo / Parte <span style="color:red;">*</span></label>
                            <input type="text" name="nombre" placeholder="Ej: Tóner Amarillo" required>
                        </div>
                        <div class="form-group">
                            <label>Número de Parte (P/N)</label>
                            <input type="text" name="n_parte" placeholder="Ej: 305935845432" style="font-family: monospace;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Categoría</label>
                            <select name="categoria" class="select2-search" style="width: 100%;">
                                <option value="">-- Selecciona --</option>
                                <?php 
                                $cat = mysqli_query($conexion, "SELECT * FROM categorias_insumos");
                                if($cat) {
                                    while($c = mysqli_fetch_assoc($cat)) echo "<option value='{$c['nombre']}'>{$c['nombre']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Unidad de Medida <span style="color:red;">*</span></label>
                            <select name="id_unidad" class="select2-search" style="width: 100%;" required>
                                <option value="">-- Selecciona Unidad --</option>
                                <?php 
                                $uni = mysqli_query($conexion, "SELECT * FROM unidades_medida");
                                while($u = mysqli_fetch_assoc($uni)) echo "<option value='{$u['id']}'>{$u['nombre']}</option>";
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No Stock (Herramienta / Menor)</label>
                            <select name="no_stock">
                                <option value="0" selected>Lleva control de inventario</option>
                                <option value="1">Non-Stock (Solo referencia)</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Stock Actual</label>
                            <input type="number" name="stock_actual" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label>Stock Mínimo</label>
                            <input type="number" name="stock_minimo" value="1" min="0">
                        </div>
                        <div class="form-group">
                            <label>Stock Deseado</label>
                            <input type="number" name="stock_deseado" value="5" min="0">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Costo Unitario (MXN)</label>
                            <input type="number" step="0.01" name="costo_unitario" placeholder="0.00" value="0.00">
                        </div>
                        <div class="form-group">
                            <label>Lead Time (Días entrega)</label>
                            <input type="number" name="lead_time_dias" placeholder="Ej: 7" value="0" min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripción / Notas Técnicas</label>
                        <textarea name="descripcion" rows="2" placeholder="Detalles, marca compatible, color..."></textarea>
                    </div>

                    <h4 style="color: var(--primary); margin-top: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                        <i class="fa fa-link"></i> Vinculaciones (Opcional)
                    </h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Almacén (Storeroom)</label>
                            <select name="id_storeroom" class="select2-search" style="width: 100%;">
                                <option value="">-- Sin Almacén --</option>
                                <?php 
                                $store = mysqli_query($conexion, "SELECT * FROM storerooms");
                                while($s = mysqli_fetch_assoc($store)) echo "<option value='{$s['id']}'>{$s['nombre']}</option>";
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ubicación Física</label>
                            <select name="id_ubicacion" class="select2-search" style="width: 100%;">
                                <option value="">-- No Asignada --</option>
                                <?php foreach ($lista_agrupada as $zona => $areas): ?>
                                    <optgroup label="ZONA: <?php echo htmlspecialchars($zona); ?>">
                                        <?php foreach ($areas as $area): ?>
                                            <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Equipo Asociado (Asset)</label>
                            <select name="id_asset" class="select2-search" style="width: 100%;">
                                <option value="">-- Ninguno --</option>
                                <?php 
                                $res_assets_vin = mysqli_query($conexion, "SELECT id, nombre_equipo FROM assets ORDER BY nombre_equipo ASC");
                                while($av = mysqli_fetch_assoc($res_assets_vin)):
                                ?>
                                    <option value="<?php echo $av['id']; ?>"><?php echo htmlspecialchars($av['nombre_equipo']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8faff; padding: 15px; border-radius: 12px; border: 1px solid #eef2ff; margin-bottom: 20px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label><i class="fa fa-truck"></i> Proveedores</label>
                            <input type="text" name="proveedores" placeholder="Ej: Amazon, Office Depot...">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label><i class="fa fa-globe"></i> URL de Compra</label>
                            <input type="text" name="url" placeholder="https://...">
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 30px;">
                        <button type="submit" class="btn-primary" style="width: auto; padding: 15px 40px; font-size: 16px;">
                            <i class="fa fa-save"></i> Guardar Insumo
                        </button>
                    </div>
                </form>
            </div>


<?php elseif ($seccion == 'editar_consumible' && isset($_GET['id'])): ?>
            <?php 
            $id_insumo = intval($_GET['id']);
            $res_insumo = mysqli_query($conexion, "SELECT * FROM consumibles WHERE id = $id_insumo");
            $i = mysqli_fetch_assoc($res_insumo);

            if (!$i):
                echo "<div class='form-container'><p>⚠️ Insumo no encontrado en la base de datos.</p></div>";
            else:
            ?>
            <div class="form-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="margin:0; color: #856404;"><i class="fa fa-edit"></i> Editando Insumo: <?php echo htmlspecialchars($i['nombre']); ?></h3>
                    <a href="Index.php?vista=consumibles" style="color: var(--primary); text-decoration: none; font-weight: 600;"><i class="fa fa-arrow-left"></i> Volver al Inventario</a>
                </div>

                <form action="actualizar_consumible.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo $i['id']; ?>">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Nombre del Insumo / Parte</label>
                            <input type="text" name="nombre" value="<?php echo htmlspecialchars($i['nombre']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Número de Parte (P/N)</label>
                            <input type="text" name="n_parte" value="<?php echo htmlspecialchars($i['n_parte']); ?>" style="font-family: monospace;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Categoría</label>
                            <select name="categoria" class="select2-search" style="width: 100%;">
                                <option value="">-- Selecciona --</option>
                                <?php 
                                $cat_res = mysqli_query($conexion, "SELECT nombre FROM categorias_insumos ORDER BY nombre ASC");
                                while($c_cat = mysqli_fetch_assoc($cat_res)) {
                                    // Comparamos el nombre guardado con el de la lista
                                    $sel = ($i['categoria'] == $c_cat['nombre']) ? 'selected' : '';
                                    echo "<option value='".htmlspecialchars($c_cat['nombre'])."' $sel>".htmlspecialchars($c_cat['nombre'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Unidad de Medida</label>
                            <select name="id_unidad" class="select2-search" style="width: 100%;" required>
                                <option value="">-- Selecciona Unidad --</option>
                                <?php 
                                $uni_res = mysqli_query($conexion, "SELECT * FROM unidades_medida ORDER BY nombre ASC");
                                while($u = mysqli_fetch_assoc($uni_res)) {
                                    // Comparamos los IDs para dejar seleccionada la unidad actual
                                    $sel = ($i['id_unidad'] == $u['id']) ? 'selected' : '';
                                    echo "<option value='{$u['id']}' $sel>".htmlspecialchars($u['nombre'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No Stock</label>
                            <select name="no_stock">
                                <option value="0" <?php echo ($i['no_stock'] == 0) ? 'selected' : ''; ?>>Lleva control de inventario</option>
                                <option value="1" <?php echo ($i['no_stock'] == 1) ? 'selected' : ''; ?>>Non-Stock (Solo referencia)</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Stock Actual</label>
                            <input type="number" name="stock_actual" value="<?php echo $i['stock_actual']; ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label>Stock Mínimo</label>
                            <input type="number" name="stock_minimo" value="<?php echo $i['stock_minimo']; ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label>Stock Deseado</label>
                            <input type="number" name="stock_deseado" value="<?php echo $i['stock_deseado']; ?>" min="0">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Costo Unitario (MXN)</label>
                            <input type="number" step="0.01" name="costo_unitario" value="<?php echo $i['costo_unitario']; ?>">
                        </div>
                        <div class="form-group">
                            <label>Lead Time (Días entrega)</label>
                            <input type="number" name="lead_time_dias" value="<?php echo $i['lead_time_dias']; ?>" min="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Descripción / Notas Técnicas</label>
                        <textarea name="descripcion" rows="2"><?php echo htmlspecialchars($i['descripcion']); ?></textarea>
                    </div>

                    <h4 style="color: var(--primary); margin-top: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                        <i class="fa fa-link"></i> Vinculaciones Actuales
                    </h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Almacén (Storeroom)</label>
                            <select name="id_storeroom" class="select2-search" style="width: 100%;">
                                <option value="">-- Sin Almacén --</option>
                                <?php 
                                $store_res = mysqli_query($conexion, "SELECT * FROM storerooms ORDER BY nombre ASC");
                                while($s = mysqli_fetch_assoc($store_res)) {
                                    $sel = ($i['id_storeroom'] == $s['id']) ? 'selected' : '';
                                    echo "<option value='{$s['id']}' $sel>".htmlspecialchars($s['nombre'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ubicación Física</label>
                            <select name="id_ubicacion" class="select2-search" style="width: 100%;">
                                <option value="">-- No Asignada --</option>
                                <?php foreach ($lista_agrupada as $zona => $areas): ?>
                                    <optgroup label="ZONA: <?php echo htmlspecialchars($zona); ?>">
                                        <?php foreach ($areas as $area): 
                                            // Aquí comparamos por ID de ubicación
                                            $sel = ($i['id_ubicacion'] == $area['id']) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $area['id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($area['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Equipo Asociado (Asset)</label>
                            <select name="id_asset" class="select2-search" style="width: 100%;">
                                <option value="">-- Ninguno --</option>
                                <?php 
                                $asset_res = mysqli_query($conexion, "SELECT id, nombre_equipo FROM assets ORDER BY nombre_equipo ASC");
                                while($av = mysqli_fetch_assoc($asset_res)) {
                                    $sel = ($i['id_asset'] == $av['id']) ? 'selected' : '';
                                    echo "<option value='{$av['id']}' $sel>" . htmlspecialchars($av['nombre_equipo']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #f8faff; padding: 15px; border-radius: 12px; border: 1px solid #eef2ff; margin-bottom: 20px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label><i class="fa fa-truck"></i> Proveedores</label>
                            <input type="text" name="proveedores" value="<?php echo htmlspecialchars($i['proveedores']); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label><i class="fa fa-globe"></i> URL de Compra</label>
                            <input type="text" name="url" value="<?php echo htmlspecialchars($i['url']); ?>">
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 30px;">
                        <button type="submit" class="btn-primary" style="width: auto; padding: 15px 40px; background: #856404; border: none; font-size: 16px;">
                            <i class="fa fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>


                        
<?php elseif ($seccion == 'catalogos'): ?>
            <?php
            // Atrapamos qué catálogo quiere ver el usuario (por defecto: storerooms)
            $tipo_cat = isset($_GET['tipo']) ? $_GET['tipo'] : 'storerooms';
            
            // Configuramos las consultas y el ANCHO IDEAL de la tabla según la selección
            // Configuramos las consultas y el ANCHO IDEAL de la tabla según la selección
            if ($tipo_cat == 'storerooms') {
                $titulo_cat = "Storerooms (Almacenes)";
                $sql_cat = "SELECT id, nombre FROM storerooms ORDER BY nombre ASC";
                $ancho_tabla = "600px"; // Pequeñita, solo tiene 2 columnas
            } elseif ($tipo_cat == 'unidades_medida') {
                $titulo_cat = "Unidades de Medida";
                $sql_cat = "SELECT id, nombre FROM unidades_medida ORDER BY nombre ASC";
                $ancho_tabla = "600px"; // Pequeñita
            } elseif ($tipo_cat == 'errores_comunes') {
                $titulo_cat = "Errores Comunes de Reporte";
                // Ordenamos primero por Tipo de Equipo (A-Z) y luego por Descripción (A-Z)
                $sql_cat = "SELECT id, tipo_equipo, descripcion_error FROM errores_comunes ORDER BY tipo_equipo ASC, descripcion_error ASC";
                $ancho_tabla = "900px"; // Mediana, tiene descripción
            } elseif ($tipo_cat == 'usuarios') {
                $titulo_cat = "Usuarios del Sistema";
                $sql_cat = "SELECT id, nombre, correo, departamento, rol FROM usuarios ORDER BY nombre ASC";
                $ancho_tabla = "100%"; // Completa, tiene muchos datos
            }
            ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <div>
                    <h2 style="color: var(--text-dark); margin: 0;"><i class="fa fa-folder-open" style="color: var(--primary);"></i> Configuración de Catálogos</h2>
                    <p style="color: #a3aed0; margin: 5px 0 0 0;">Administra las listas desplegables y accesos del sistema.</p>
                </div>
            </div>

            <div style="margin-bottom: 20px; max-width: 350px;">
                <select onchange="window.location.href='Index.php?vista=catalogos&tipo='+this.value" style="width: 100%; padding: 12px; border: 2px solid #e0e5f2; border-radius: 10px; color: var(--text-dark); font-weight: bold; font-size: 15px; cursor: pointer; outline: none;">
                    <option value="storerooms" <?php echo $tipo_cat == 'storerooms' ? 'selected' : ''; ?>>Storerooms (Almacenes)</option>
                    <option value="unidades_medida" <?php echo $tipo_cat == 'unidades_medida' ? 'selected' : ''; ?>>Unidades de Medida</option>
                    <option value="errores_comunes" <?php echo $tipo_cat == 'errores_comunes' ? 'selected' : ''; ?>>Errores Comunes</option>
                    <option value="usuarios" <?php echo $tipo_cat == 'usuarios' ? 'selected' : ''; ?>>Usuarios</option>
                    <option value="personal_acre">Personal Acre (Solicitantes)</option>
                    <option value="pos_major_groups">POS Major Groups</option>
                    <option value="pos_family_groups">POS Family Groups</option>
                
                </select>
            </div>

            <div id="form-catalogo" style="display: none; background: #fff9e6; padding: 25px; border-radius: 20px; margin-bottom: 25px; border: 2px solid #ffcc00; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: <?php echo $ancho_tabla; ?>;">
                <h4 id="titulo-form-cat" style="margin-top:0; color: #856404;"><i class="fa fa-edit"></i> Gestionar Registro</h4>
                
                <form action="procesar_catalogo.php" method="POST">
                    <input type="hidden" name="accion" id="cat_accion" value="nuevo">
                    <input type="hidden" name="tabla" value="<?php echo $tipo_cat; ?>">
                    <input type="hidden" name="id" id="cat_id" value="">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                        
                        <?php if($tipo_cat == 'storerooms' || $tipo_cat == 'unidades_medida'): ?>
                            <div class="form-group" style="margin: 0; grid-column: span 2;">
                                <label>Nombre del Registro:</label>
                                <input type="text" name="nombre" id="cat_nombre" required>
                            </div>
                            
                        <?php elseif($tipo_cat == 'errores_comunes'): ?>
                            <div class="form-group" style="margin: 0;">
                                <label>Tipo de Equipo:</label>
                                <input type="text" name="tipo_equipo" id="cat_tipo_equipo" placeholder="Ej: Impresora" required>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label>Descripción del Error:</label>
                                <input type="text" name="descripcion_error" id="cat_descripcion_error" placeholder="Ej: Atasco de papel" required>
                            </div>
                            
                        <?php elseif($tipo_cat == 'usuarios'): ?>
                            <div class="form-group" style="margin: 0;">
                                <label>Nombre Completo:</label>
                                <input type="text" name="nombre" id="cat_nombre_usuario" required>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label>Correo Electrónico:</label>
                                <input type="email" name="correo" id="cat_correo">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label>Departamento:</label>
                                <input type="text" name="departamento" id="cat_departamento">
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label>Rol de Usuario:</label>
                                <select name="rol" id="cat_rol" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border);">
                                    <option value="admin">Administrador</option>
                                    <option value="tecnico">Técnico</option>
                                    <option value="usuario">Usuario Normal</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label>Contraseña de Acceso:</label>
                                <input type="password" name="password" id="cat_password" placeholder="******" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid var(--border);">
                                <small id="nota-password" style="color: #a3aed0; display: none; font-weight: 600; margin-top: 5px;">Déjalo en blanco si no deseas cambiar la contraseña actual.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" onclick="document.getElementById('form-catalogo').style.display='none'" class="btn-secondary" style="padding: 12px 20px; border-radius: 12px; border: 1px solid #ccc; background: #eee; color: #333; font-weight: bold; margin-right: 10px; cursor: pointer;">Cancelar</button>
                        <button type="submit" class="btn-primary" style="width: auto; padding: 12px 25px; background: #856404; border: none;">Guardar Registro</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive-custom" style="background: var(--white); padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); max-width: <?php echo $ancho_tabla; ?>;">
                
                <button onclick="abrirFormularioCat('nuevo', this)" class="btn-primary" style="width: 100%; margin-bottom: 20px; padding: 15px; font-size: 18px; border-radius: 10px;">
                    <i class="fa fa-plus"></i>
                </button>

                <table class="table-custom" style="min-width: auto;">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <?php if($tipo_cat == 'storerooms' || $tipo_cat == 'unidades_medida'): ?>
                                <th>Nombre</th>
                            <?php elseif($tipo_cat == 'errores_comunes'): ?>
                                <th>Tipo de Equipo</th>
                                <th>Descripción del Error</th>
                            <?php elseif($tipo_cat == 'usuarios'): ?>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Departamento</th>
                                <th>Rol</th>
                            <?php endif; ?>
                            <th style="text-align: right; padding-right: 20px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res_cat = mysqli_query($conexion, $sql_cat);
                        if(mysqli_num_rows($res_cat) > 0):
                            while($cat = mysqli_fetch_assoc($res_cat)): 
                        ?>
                        <tr>
                            <td><span style="color: #a3aed0;">#<?php echo $cat['id']; ?></span></td>
                            
                            <?php if($tipo_cat == 'storerooms' || $tipo_cat == 'unidades_medida'): ?>
                                <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($cat['nombre']); ?></strong></td>
                                
                            <?php elseif($tipo_cat == 'errores_comunes'): ?>
                                <td><strong style="color: var(--text-dark);"><?php echo htmlspecialchars($cat['tipo_equipo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cat['descripcion_error']); ?></td>
                                
                            <?php elseif($tipo_cat == 'usuarios'): ?>
                                <td><strong style="color: var(--primary);"><?php echo htmlspecialchars($cat['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cat['correo']); ?></td>
                                <td><?php echo htmlspecialchars($cat['departamento']); ?></td>
                                <td><span style="background: #eef2ff; color: var(--primary); padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; text-transform: uppercase;"><?php echo htmlspecialchars($cat['rol']); ?></span></td>
                            <?php endif; ?>

                            <td style="text-align: right; padding-right: 20px; white-space: nowrap;">
                                <button onclick="abrirFormularioCat('editar', this)" 
                                        data-id="<?php echo $cat['id']; ?>"
                                        <?php if(isset($cat['nombre'])) echo "data-nombre='".htmlspecialchars($cat['nombre'], ENT_QUOTES)."'"; ?>
                                        <?php if(isset($cat['tipo_equipo'])) echo "data-tipo='".htmlspecialchars($cat['tipo_equipo'], ENT_QUOTES)."'"; ?>
                                        <?php if(isset($cat['descripcion_error'])) echo "data-desc='".htmlspecialchars($cat['descripcion_error'], ENT_QUOTES)."'"; ?>
                                        <?php if(isset($cat['correo'])) echo "data-correo='".htmlspecialchars($cat['correo'], ENT_QUOTES)."'"; ?>
                                        <?php if(isset($cat['departamento'])) echo "data-depto='".htmlspecialchars($cat['departamento'], ENT_QUOTES)."'"; ?>
                                        <?php if(isset($cat['rol'])) echo "data-rol='".htmlspecialchars($cat['rol'], ENT_QUOTES)."'"; ?>
                                        style="color: var(--text-dark); background: none; border: 1px solid #e0e5f2; border-radius: 6px; padding: 5px 10px; cursor: pointer; margin-right: 5px; transition: 0.3s;" title="Editar">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button onclick="eliminarRegistroCat('<?php echo $tipo_cat; ?>', <?php echo $cat['id']; ?>)" style="color: var(--text-dark); background: none; border: 1px solid #e0e5f2; border-radius: 6px; padding: 5px 10px; cursor: pointer; transition: 0.3s;" title="Eliminar">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 30px; color: #a3aed0;">No hay registros en esta sección.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        <?php endif; ?></div>



       
  
<script>   
    // 1. Inicializar el Calendario (Flatpickr) y Botones
    document.addEventListener('DOMContentLoaded', function() {
        
        // AQUI DESPERTAMOS AL CALENDARIO FLOTANTE
        if (typeof flatpickr !== 'undefined') {
            flatpickr(".datetimepicker", {
                enableTime: true,
                dateFormat: "d/m/Y H:i", // Formato día/mes/año Hora:Minutos
                locale: "es", // En español
                time_24hr: true,
                minDate: "today" // No permite elegir fechas del pasado
            });
        }

        // Lógica de los botones que ya tenías
        const btnR = document.getElementById('btn-reactivo');
        const btnP = document.getElementById('btn-preventivo');

        if (btnR) {
            btnR.addEventListener('click', function() {
                setWorkType('Reactivo');
            });
        }

        if (btnP) {
            btnP.addEventListener('click', function() {
                setWorkType('Preventivo');
            });
        }
    });

    // 2. Controlar Botones Reactivo / Preventivo (VERSIÓN CORREGIDA Y ÚNICA)
    function setWorkType(type) {
        document.getElementById('tipo_trabajo').value = type;
        
        const btnR = document.getElementById('btn-reactivo');
        const btnP = document.getElementById('btn-preventivo');
        const labelFecha = document.getElementById('label-fecha');
        
        // ---> LÍNEA NUEVA: Traemos la caja de preventivo
        const opcionesP = document.getElementById('opciones-preventivo'); 

        if (type === 'Reactivo') {
            btnR.classList.add('active');
            btnP.classList.remove('active');
            labelFecha.innerText = "Due Date (Fecha límite opcional)";
            
            // ---> LÍNEA NUEVA: Ocultamos la caja
            if(opcionesP) opcionesP.style.display = 'none'; 
        } else {
            btnP.classList.add('active');
            btnR.classList.remove('active');
            labelFecha.innerText = "Starting Date (Fecha de inicio programada)";
            
            // ---> LÍNEA NUEVA: Mostramos la caja
            if(opcionesP) opcionesP.style.display = 'block'; 
        }
    }
    // 3. Controlar Pestañas de Schedule
    function setScheduleTab(tab) {
        document.getElementById('tipo_programacion').value = tab;
        
        document.getElementById('tab-onetime').classList.remove('active');
        document.getElementById('tab-persistent').classList.remove('active');
        document.getElementById('tab-floating').classList.remove('active');
        
        let hint = document.getElementById('schedule-hint');
        let repeats = document.getElementById('repeats-container');

        if (tab === 'One-time') {
            document.getElementById('tab-onetime').classList.add('active');
            if(repeats) repeats.style.display = 'none';
            if(hint) hint.innerHTML = "Se programará una sola vez a partir de la fecha elegida.";
        } else if (tab === 'Persistent') {
            document.getElementById('tab-persistent').classList.add('active');
            if(repeats) repeats.style.display = 'block';
            if(hint) hint.innerHTML = "Se repite basado en un horario fijo.<br>La fecha de vencimiento se calcula tras su creación.";
        } else if (tab === 'Floating') {
            document.getElementById('tab-floating').classList.add('active');
            if(repeats) repeats.style.display = 'block';
            if(hint) hint.innerHTML = "Se repite basado en la finalización de la orden anterior.<br>La fecha se calcula tras su creación.";
        }
    }

    
    // script para el calendario principal
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        /* ... (AQUÍ SIGUE TU CÓDIGO DE FULLCALENDAR QUE YA TIENES) ... */
    });    


// script para el calendario
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'es', // Para que esté en español
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listWeek'
                    },
                    buttonText: {
                        today: 'Hoy',
                        month: 'Mes',
                        week: 'Semana',
                        list: 'Agenda'
                    },
                    
                    events: <?php echo isset($eventos) ? json_encode($eventos) : '[]'; ?>,
                    eventClick: function(info) {
                        if (info.event.url) {
                            window.location.href = info.event.url;
                            info.jsEvent.preventDefault();
                        }
                    }
                });
                calendar.render();
            }
        });
    
// Opcion de eliminar del boton trash             

        function confirmarBorrado(tabla, id) {
            if (confirm("¿Estás realmente seguro de querer eliminar este registro?")) {
                window.location.href = "eliminar_catalogo.php?tabla=" + tabla + "&id=" + id;
            }
        }



// 1. Buscador Global
        const inputBusqueda = document.getElementById('busquedaGlobal');
        if (inputBusqueda) {
            inputBusqueda.addEventListener('keyup', function() {
                let filtro = this.value.toLowerCase();
                let filas = document.querySelectorAll("#tablaHistorial tbody tr");

                filas.forEach(fila => {
                    let texto = fila.textContent.toLowerCase();
                    fila.style.display = texto.includes(filtro) ? "" : "none";
                });
            });
        }

// Función para mostrar/ocultar formularios de catálogo
            function toggleForm(idForm) {
                var f = document.getElementById(idForm);
                if (f.style.display === 'none' || f.style.display === '') {
                    f.style.display = 'block';
                } else {
                    f.style.display = 'none';
                }
            }



// 3. Toggle Formulario Ubicaciones (Locations)
        var btnLoc = document.getElementById('btn-toggle');
        if (btnLoc) {
            btnLoc.onclick = function() {
                var f = document.getElementById('form-nueva-loc');
                f.style.display = (f.style.display === 'none' || f.style.display === '') ? 'block' : 'none';
            };
        }

        

// ==========================================
    // FILTROS Y PAGINACIÓN PARA ASSETS
    // ==========================================
    const inputBusquedaAssets = document.getElementById('busquedaAssets');
    const selectTipoBusqueda = document.getElementById('tipoBusquedaAssets');
    const selectLimiteAssets = document.getElementById('limiteAssets');
    const paginacionContenedor = document.getElementById('paginacionAssets');

    let paginaActualAssets = 1;
    let limiteAssets = 50; // Mostrar 20 por defecto

    if (inputBusquedaAssets && selectTipoBusqueda && selectLimiteAssets) {
        // Escuchadores de eventos (Si escriben o cambian algo, regresamos a la página 1 y filtramos)
        inputBusquedaAssets.addEventListener('keyup', () => { paginaActualAssets = 1; filtrarAssets(); });
        selectTipoBusqueda.addEventListener('change', () => { paginaActualAssets = 1; filtrarAssets(); });
        selectLimiteAssets.addEventListener('change', function() {
            limiteAssets = this.value === 'todos' ? 999999 : parseInt(this.value);
            paginaActualAssets = 1;
            filtrarAssets();
        });

        // Ejecutar por primera vez al abrir la página
        document.addEventListener('DOMContentLoaded', filtrarAssets);
    }

    function filtrarAssets() {
        let filtro = inputBusquedaAssets.value.toLowerCase();
        let tipo = selectTipoBusqueda.value;
        let filas = document.querySelectorAll("#cuerpo-tabla-assets tr");

        let filasFiltradas = [];

        filas.forEach(fila => {
            // Ignoramos la fila si es el mensaje de "Aún no hay activos"
            if(fila.cells.length === 1) return; 

            let textoFila = "";
            // Leemos la columna correcta según la selección
            if (tipo === "todos") { textoFila = fila.textContent.toLowerCase(); }
            else if (tipo === "equipo") { textoFila = fila.cells[1].textContent.toLowerCase(); }
            else if (tipo === "tipo" || tipo === "serie") { textoFila = fila.cells[2].textContent.toLowerCase(); } 
            else if (tipo === "ubicacion") { textoFila = fila.cells[3].textContent.toLowerCase(); }
            else if (tipo === "estado") { textoFila = fila.cells[4].textContent.toLowerCase(); }

            // Si coincide con la búsqueda, la preparamos para mostrar
            if (textoFila.includes(filtro)) {
                filasFiltradas.push(fila);
            } else {
                fila.style.display = "none";
            }
        });

        // === MAGIA DE PAGINACIÓN ===
        let totalFilas = filasFiltradas.length;
        let totalPaginas = Math.ceil(totalFilas / limiteAssets);
        if(totalPaginas === 0) totalPaginas = 1;
        if(paginaActualAssets > totalPaginas) paginaActualAssets = totalPaginas;

        // Calculamos qué filas mostrar según la página
        let inicio = (paginaActualAssets - 1) * limiteAssets;
        let fin = inicio + limiteAssets;

        filasFiltradas.forEach((fila, index) => {
            if (index >= inicio && index < fin) {
                fila.style.display = ""; // Mostrar
            } else {
                fila.style.display = "none"; // Ocultar
            }
        });

        dibujarPaginacionAssets(totalPaginas);
    }

    function dibujarPaginacionAssets(totalPaginas) {
        if(!paginacionContenedor) return;
        paginacionContenedor.innerHTML = '';

        // Si todo cabe en una página, no mostramos botones
        if(totalPaginas <= 1) return;

        for(let i = 1; i <= totalPaginas; i++) {
            let btn = document.createElement('button');
            btn.innerText = i;
            btn.style.padding = '5px 12px';
            btn.style.margin = '0 3px';
            btn.style.border = 'none';
            btn.style.borderRadius = '6px';
            btn.style.cursor = 'pointer';
            btn.style.fontWeight = 'bold';
            btn.style.transition = '0.3s';
            
            if(i === paginaActualAssets) {
                btn.style.backgroundColor = 'var(--primary)';
                btn.style.color = 'white';
            } else {
                btn.style.backgroundColor = '#eef2ff';
                btn.style.color = 'var(--primary)';
            }
            
            btn.onclick = function() {
                paginaActualAssets = i;
                filtrarAssets(); // Volver a calcular
            };
            paginacionContenedor.appendChild(btn);
        }
    }

    function limpiarFiltrosAssets() {
        if (inputBusquedaAssets && selectTipoBusqueda && selectLimiteAssets) {
            inputBusquedaAssets.value = "";
            selectTipoBusqueda.value = "todos";
            selectLimiteAssets.value = "20"; // Reseteamos el selector
            limiteAssets = 20; 
            paginaActualAssets = 1; // Volvemos a la primera página
            filtrarAssets();
        }
    }
        // Función para el botón de Limpiar
        function limpiarFiltrosAssets() {
            if (inputBusquedaAssets && selectTipoBusqueda) {
                inputBusquedaAssets.value = "";
                selectTipoBusqueda.value = "todos";
                let filas = document.querySelectorAll("#cuerpo-tabla-assets tr");
                filas.forEach(fila => fila.style.display = ""); // Mostramos todas
            }
        }


        // 1. Mostrar/Ocultar Menús Desplegables de Filtros
    function toggleDropdown(id) {
        // Cierra los demás menús primero
        document.querySelectorAll('.filter-dropdown').forEach(d => {
            if(d.id !== id) d.style.display = 'none';
        });
        const drop = document.getElementById(id);
        drop.style.display = drop.style.display === 'none' ? 'block' : 'none';
    }

    // Cierra el menú si das clic en cualquier otra parte de la pantalla
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.filter-tabs')) {
            document.querySelectorAll('.filter-dropdown').forEach(d => d.style.display = 'none');
        }
    });

    // 2. Lógica Principal: Leer, Guardar y Filtrar
    function aplicarFiltros() {
        // 🔒 CANDADO DE SEGURIDAD: Si no hay checkboxes en pantalla (ej. estamos en Inicio), no hacemos nada
        if (document.querySelectorAll('.cb-filter').length === 0) return;

        let filtros = {
            status: [],
            worktype: []
        };

        // Recolectar todos los checkboxes que el usuario palomeó
        document.querySelectorAll('.cb-filter:checked').forEach(cb => {
            filtros[cb.dataset.group].push(cb.value);
        });

        // GUARDAR EN MEMORIA (Persistencia)
        localStorage.setItem('ticketFiltros', JSON.stringify(filtros));

        // Actualizar las burbujitas verdes con el número de filtros activos
        Object.keys(filtros).forEach(grupo => {
            const badge = document.getElementById('badge-' + grupo);
            if(badge) {
                if(filtros[grupo].length > 0) {
                    badge.style.display = 'inline-block';
                    badge.innerText = filtros[grupo].length;
                } else {
                    badge.style.display = 'none';
                }
            }
        });

        // Ocultar o Mostrar las filas de la tabla
        document.querySelectorAll('.ticket-row').forEach(fila => {
            const statusFila = fila.dataset.status;
            const worktypeFila = fila.dataset.worktype;

            // Si el arreglo está vacío (no hay filtro), pasa. Si no, revisa si coincide.
            const matchStatus = filtros.status.length === 0 || filtros.status.includes(statusFila);
            const matchWorktype = filtros.worktype.length === 0 || filtros.worktype.includes(worktypeFila);

            if (matchStatus && matchWorktype) {
                fila.style.display = ''; // Mostrar
            } else {
                fila.style.display = 'none'; // Ocultar
            }
        });
    }
    // ¡ESTO ES LO QUE FALTABA! Escuchar cada vez que el usuario marca o desmarca un checkbox
    document.querySelectorAll('.cb-filter').forEach(cb => {
        cb.addEventListener('change', aplicarFiltros);
    });
    // 3. Botones para "Clear All"
    function clearFilter(grupo) {
        document.querySelectorAll(`.cb-filter[data-group="${grupo}"]`).forEach(cb => cb.checked = false);
        aplicarFiltros();
    }

    function limpiarTodosLosFiltros() {
        document.querySelectorAll('.cb-filter').forEach(cb => cb.checked = false);
        aplicarFiltros();
    }

    // 4. Recuperar de la memoria al cargar la página (El truco de magia)
    document.addEventListener('DOMContentLoaded', () => {
        // 🔒 CANDADO DE SEGURIDAD: Solo restauramos memoria si estamos en la vista de la tabla
        if (document.querySelectorAll('.cb-filter').length === 0) return;

        const guardados = localStorage.getItem('ticketFiltros');
        if (guardados) {
            const filtros = JSON.parse(guardados);
            document.querySelectorAll('.cb-filter').forEach(cb => {
                if (filtros[cb.dataset.group] && filtros[cb.dataset.group].includes(cb.value)) {
                    cb.checked = true; // Volvemos a palomear lo que estaba guardado
                }
            });
        }
        aplicarFiltros(); // Ejecutamos el filtro inicial
    });


            // Función para agregar una nueva tarea al checklist
    function agregarTareaChecklist() {
        const contenedor = document.getElementById('contenedor-checklist');
        const div = document.createElement('div');
        div.className = 'item-checklist';
        div.style.display = 'flex';
        div.style.gap = '10px';
        div.style.marginBottom = '10px';
        
        div.innerHTML = `
            <input type="text" name="checklist_items[]" placeholder="Escribe una tarea..." required style="flex: 1;">
            <button type="button" onclick="this.parentElement.remove()" style="background: #ffe6e6; color: #f53939; border: none; padding: 8px 12px; border-radius: 10px; cursor: pointer;">
                <i class="fa fa-trash"></i>
            </button>
        `;
        contenedor.appendChild(div);
    }

    // MAGIA AJAX PARA EL CHECKLIST DE TAREAS EN DETALLES
    document.querySelectorAll('.task-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const taskId = this.getAttribute('data-id');
            const isChecked = this.checked ? 1 : 0;
            
            // Elementos visuales
            const label = document.getElementById('label-tarea-' + taskId);
            const texto = document.getElementById('texto-tarea-' + taskId);

            // Cambiamos los colores y tachamos al instante para que se vea bonito
            if (isChecked) {
                label.style.background = '#e6f9f1';
                texto.style.color = '#01b574';
                texto.style.textDecoration = 'line-through';
            } else {
                label.style.background = '#f4f7fe';
                texto.style.color = 'var(--text-dark)';
                texto.style.textDecoration = 'none';
            }

            // Enviamos la orden secreta al PHP sin recargar la página
            fetch('actualizar_tarea.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + taskId + '&completada=' + isChecked
            })
            .then(response => response.text())
            .then(data => {
                console.log("Tarea actualizada:", data); // Por si queremos ver si funcionó en la consola
            });
        });
    });

    // Función para el Botón de Rechazar Request
    function rechazarRequest(id) {
        if (confirm("⚠️ ¿Estás seguro de que deseas rechazar y eliminar este reporte? Esta acción no se puede deshacer.")) {
            window.location.href = "eliminar_request.php?id=" + id;
        }
    }

        // ==========================================
    // FILTROS Y ACCIONES PARA LOCATIONS
    // ==========================================
    const selectZona = document.getElementById('filtro-zona');
    const inputLoc = document.getElementById('busquedaLoc');

    if (selectZona && inputLoc) {
        selectZona.addEventListener('change', filtrarLocs);
        inputLoc.addEventListener('keyup', filtrarLocs);
    }

    function filtrarLocs() {
        let filtroTexto = inputLoc.value.toLowerCase();
        let filtroZona = selectZona.value;
        let filas = document.querySelectorAll(".loc-row");

        filas.forEach(fila => {
            let textoFila = fila.textContent.toLowerCase();
            let zonaFila = fila.getAttribute('data-zona');

            // Verifica si coincide el texto y la zona
            let matchTexto = textoFila.includes(filtroTexto);
            let matchZona = (filtroZona === "todas" || zonaFila === filtroZona);

            if (matchTexto && matchZona) {
                fila.style.display = "";
            } else {
                fila.style.display = "none";
            }
        });
    }

    function limpiarFiltrosLoc() {
        if(selectZona && inputLoc) {
            selectZona.value = "todas";
            inputLoc.value = "";
            filtrarLocs(); // Vuelve a mostrar todo
        }
    }

    function eliminarLocation(id) {
        if(confirm("⚠️ ¿Estás seguro de eliminar esta ubicación? Esta acción no se puede deshacer.")){
            window.location.href = "eliminar_location.php?id=" + id;
        }
    }

    // Función para eliminar un Activo (Asset)
    function eliminarAsset(id) {
        if(confirm("⚠️ ¿Estás seguro de eliminar este equipo del inventario? Esta acción no se puede deshacer.")){
            window.location.href = "eliminar_asset.php?id=" + id;
        }
    }

    // INICIALIZAR LAS LISTAS CON BUSCADOR (SELECT2)
    $(document).ready(function() {
            $('.select2-multiple').select2({
            placeholder: "Selecciona uno o varios técnicos...",
            allowClear: true,
            tokenSeparators: [',']
        });
    });

    // ==========================================
    // FILTROS Y PAGINACIÓN PARA CONSUMIBLES
    // ==========================================
    const inputBusquedaCons = document.getElementById('busquedaConsumibles');
    const selectTipoCons = document.getElementById('tipoBusquedaConsumibles');
    const selectLimiteCons = document.getElementById('limiteConsumibles');
    const paginacionContenedorCons = document.getElementById('paginacionConsumibles');

    let paginaActualCons = 1;
    let limiteCons = 20;

    if (inputBusquedaCons) {
        inputBusquedaCons.addEventListener('keyup', () => { paginaActualCons = 1; filtrarConsumibles(); });
        selectTipoCons.addEventListener('change', () => { paginaActualCons = 1; filtrarConsumibles(); });
        selectLimiteCons.addEventListener('change', function() {
            limiteCons = this.value === 'todos' ? 999999 : parseInt(this.value);
            paginaActualCons = 1;
            filtrarConsumibles();
        });
        // Iniciar
        setTimeout(filtrarConsumibles, 100);
    }

    function filtrarConsumibles() {
        let filtro = inputBusquedaCons.value.toLowerCase();
        let tipo = selectTipoCons.value;
        let filas = document.querySelectorAll("#cuerpo-tabla-consumibles tr");
        let filasFiltradas = [];

        filas.forEach(fila => {
            let textoFila = (tipo === "todos") ? fila.textContent.toLowerCase() : fila.cells[tipo === "nombre" ? 0 : (tipo === "categoria" ? 1 : 3)].textContent.toLowerCase();
            if (textoFila.includes(filtro)) { filasFiltradas.push(fila); } else { fila.style.display = "none"; }
        });

        let totalPaginas = Math.ceil(filasFiltradas.length / limiteCons) || 1;
        let inicio = (paginaActualCons - 1) * limiteCons;
        let fin = inicio + limiteCons;

        filasFiltradas.forEach((fila, index) => {
            fila.style.display = (index >= inicio && index < fin) ? "" : "none";
        });

        dibujarPaginacionCons(totalPaginas);
    }

    function dibujarPaginacionCons(totalPaginas) {
        if(!paginacionContenedorCons) return;
        paginacionContenedorCons.innerHTML = '';
        if(totalPaginas <= 1) return;
        for(let i = 1; i <= totalPaginas; i++) {
            let btn = document.createElement('button');
            btn.innerText = i;
            btn.className = (i === paginaActualCons) ? 'btn-pag-act' : 'btn-pag';
            // Estilos rápidos
            btn.style.cssText = "padding: 5px 12px; margin: 0 3px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;";
            btn.style.backgroundColor = (i === paginaActualCons) ? 'var(--primary)' : '#eef2ff';
            btn.style.color = (i === paginaActualCons) ? 'white' : 'var(--primary)';
            btn.onclick = () => { paginaActualCons = i; filtrarConsumibles(); };
            paginacionContenedorCons.appendChild(btn);
        }
    }

    // ==========================================
    // FUNCIONES PARA CATÁLOGOS (NUEVO, EDITAR, ELIMINAR)
    // ==========================================
    function abrirFormularioCat(accion, btn) {
        // 1. Mostramos el cuadro amarillo del formulario
        document.getElementById('form-catalogo').style.display = 'block';
        document.getElementById('cat_accion').value = accion;
        
        if(accion === 'editar') {
            // Cambiamos el título
            document.getElementById('titulo-form-cat').innerHTML = '<i class="fa fa-edit"></i> Editando Registro';
            document.getElementById('cat_id').value = btn.getAttribute('data-id');
            
            // Llenamos los campos inteligentemente si es que existen en pantalla
            if(document.getElementById('cat_nombre')) document.getElementById('cat_nombre').value = btn.getAttribute('data-nombre');
            if(document.getElementById('cat_tipo_equipo')) document.getElementById('cat_tipo_equipo').value = btn.getAttribute('data-tipo');
            if(document.getElementById('cat_descripcion_error')) document.getElementById('cat_descripcion_error').value = btn.getAttribute('data-desc');
            if(document.getElementById('cat_nombre_usuario')) document.getElementById('cat_nombre_usuario').value = btn.getAttribute('data-nombre');
            if(document.getElementById('cat_correo')) document.getElementById('cat_correo').value = btn.getAttribute('data-correo');
            if(document.getElementById('cat_departamento')) document.getElementById('cat_departamento').value = btn.getAttribute('data-depto');
            if(document.getElementById('cat_rol')) document.getElementById('cat_rol').value = btn.getAttribute('data-rol');
            
            // Ocultar y vaciar la contraseña por seguridad en editar
            if(document.getElementById('cat_password')) {
                document.getElementById('cat_password').value = '';
                document.getElementById('cat_password').removeAttribute('required'); // No es obligatoria al editar
            }
            if(document.getElementById('nota-password')) document.getElementById('nota-password').style.display = 'block';
            
            // Hacemos que la pantalla suba suavecito para que veas el formulario
            window.scrollTo({ top: 0, behavior: 'smooth' });

        } else {
            // Si es "Nuevo Registro", limpiamos todo
            document.getElementById('titulo-form-cat').innerHTML = '<i class="fa fa-plus-circle"></i> Nuevo Registro';
            document.getElementById('cat_id').value = '';
            
            // Vaciamos todas las cajitas de texto (incluyendo contraseñas)
            document.querySelectorAll('#form-catalogo input[type="text"], #form-catalogo input[type="email"], #form-catalogo input[type="password"]').forEach(input => input.value = '');
            
            // Hacer obligatoria la contraseña en registros nuevos
            if(document.getElementById('cat_password')) {
                document.getElementById('cat_password').setAttribute('required', 'required'); // Sí es obligatoria al crear
            }
            if(document.getElementById('nota-password')) document.getElementById('nota-password').style.display = 'none';

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function eliminarRegistroCat(tabla, id) {
        if(confirm("⚠️ ¿Estás seguro de que deseas eliminar este registro? Esta acción no se puede deshacer.")) {
            window.location.href = "procesar_catalogo.php?eliminar=1&tabla=" + tabla + "&id=" + id;
        }
    }

        function filtrarTablaPos() {
                const folio = document.getElementById('filter-folio').value.toLowerCase();
                const nombre = document.getElementById('filter-nombre').value.toLowerCase();
                const fecha = document.getElementById('filter-fecha').value;
                const estado = document.getElementById('filter-estado').value;
                
                document.querySelectorAll('.pos-row').forEach(row => {
                    const matchFolio = row.dataset.folio.includes(folio);
                    const matchNombre = row.dataset.nombre.includes(nombre);
                    const matchFecha = fecha === "" || row.dataset.fecha === fecha;
                    const matchEstado = estado === "" || row.dataset.estado === estado;

                    if(matchFolio && matchNombre && matchFecha && matchEstado) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            }
</script>     
</body>
</html>