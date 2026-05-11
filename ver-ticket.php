<?php
session_start();
include 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['nombre'])) {
    header("Location: acceso.php");
    exit();
}

$rol_actual = $_SESSION['rol'];

// 1. Obtener el ID del ticket desde la URL
if (!isset($_GET['id'])) {
    header("Location: Index.php?vista=tabla");
    exit();
}

$id_ticket = mysqli_real_escape_string($conexion, $_GET['id']);

// 2. Consultar la información del ticket
$sql = "SELECT * FROM tickets WHERE id = '$id_ticket'";
$resultado = mysqli_query($conexion, $sql);
$ticket = mysqli_fetch_assoc($resultado);

if (!$ticket) {
    echo "Ticket no encontrado.";
    exit();
}

// 3. Obtener lista de técnicos para el select (solo si es admin o técnico)
$res_usuarios = mysqli_query($conexion, "SELECT nombre FROM usuarios WHERE rol != 'usuario' ORDER BY nombre ASC");
$usuarios_lista = [];
while($u = mysqli_fetch_assoc($res_usuarios)) {
    $usuarios_lista[] = $u['nombre'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Ticket #<?php echo $id_ticket; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4318ff;
            --bg-light: #f4f7fe;
            --text-dark: #2b3674;
            --white: #ffffff;
            --border: #e0e5f2;
        }

        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg-light); display: flex; height: 100vh; color: var(--text-dark); }
        
        .sidebar { width: 260px; background: var(--white); border-right: 1px solid var(--border); padding: 20px; display: flex; flex-direction: column; }
        .nav-item { padding: 15px; text-decoration: none; color: #a3aed0; font-weight: 500; border-radius: 12px; margin-bottom: 5px; transition: 0.3s; }
        .nav-item:hover { background: var(--bg-light); color: var(--primary); }

        .main-content { flex: 1; padding: 40px; overflow-y: auto; }

        .detail-container { background: var(--white); padding: 30px; border-radius: 20px; max-width: 800px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        
        .header-ticket { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); margin-bottom: 20px; padding-bottom: 10px; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .info-item label { display: block; color: #a3aed0; font-size: 12px; text-transform: uppercase; font-weight: bold; }
        .info-item p { margin: 5px 0; font-size: 16px; font-weight: 500; }

        .form-edit { background: #fafcfe; padding: 20px; border-radius: 15px; border: 1px solid var(--border); margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        select, input { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 12px; }
        
        .btn-update { background: var(--primary); color: white; border: none; padding: 12px; border-radius: 12px; cursor: pointer; width: 100%; font-weight: bold; margin-top: 10px; }
        .btn-back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: var(--primary); font-weight: 600; }
    
        /* Estilos para la info de mantenimiento */
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
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2 style="color: var(--primary); text-align: center;">HelpDesk</h2>
        <a href="Index.php?vista=bienvenida" class="nav-item"><i class="fa fa-home"></i> Inicio</a>
        <a href="Index.php?vista=tabla" class="nav-item"><i class="fa fa-ticket"></i> Mis Tickets</a>
        <a href="logout.php" class="nav-item" style="margin-top: auto;"><i class="fa fa-sign-out"></i> Cerrar Sesión</a>
    </div>

    <div class="main-content">
        <a href="Index.php?vista=tabla" class="btn-back"><i class="fa fa-arrow-left"></i> Volver al listado</a>

        <div class="detail-container">
            <div class="header-ticket">
                <div>
                    <?php 
                    $clase_tipo = ($ticket['tipo_trabajo'] == 'Preventivo') ? 'badge-preventivo' : 'badge-reactivo';
                    ?>
                    <span class="maint-badge <?php echo $clase_tipo; ?>">
                        <i class="fa <?php echo ($ticket['tipo_trabajo'] == 'Preventivo') ? 'fa-calendar-check' : 'fa-bolt'; ?>"></i> 
                        <?php echo $ticket['tipo_trabajo'] ?: 'Reactivo'; ?>
                    </span>
                    <h2 style="margin: 0;">Detalle del Ticket #<?php echo $ticket['id']; ?></h2>
                </div>
                <span class="status-pill <?php echo ($ticket['estado'] == 'Abierto') ? 'status-open' : (($ticket['estado'] == 'En Proceso') ? 'status-process' : 'status-done'); ?>" style="padding: 10px 20px; font-size: 14px;">
                    <?php echo $ticket['estado']; ?>
                </span>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label>Asunto / Tarea</label>
                    <p><?php echo $ticket['asunto']; ?></p>
                </div>
                <div class="info-item">
                    <label>Solicitante / Departamento</label>
                    <p><?php echo $ticket['solicitante']; ?> (<?php echo $ticket['departamento']; ?>)</p>
                </div>
            </div>

<?php if(!empty($ticket['correo_contacto']) || !empty($ticket['telefono_contacto'])): ?>
            <div class="info-grid" style="grid-template-columns: 1fr; background: #eef2ff; padding: 15px; border-radius: 12px; border: 1px solid #d1d9e6; margin-bottom: 20px;">
                <div class="info-item">
                    <label style="color: var(--primary);">Datos de Contacto (Usuario Externo)</label>
                    <p>
                        <?php if(!empty($ticket['correo_contacto'])): ?>
                            <i class="fa fa-envelope"></i> <?php echo htmlspecialchars($ticket['correo_contacto']); ?> &nbsp;&nbsp;&nbsp;
                        <?php endif; ?>
                        <?php if(!empty($ticket['telefono_contacto'])): ?>
                            <i class="fa fa-phone"></i> <?php echo htmlspecialchars($ticket['telefono_contacto']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($ticket['fecha_programada'])): ?>
            <div class="schedule-box">
                <div class="info-item">
                    <label><i class="fa fa-calendar-day"></i> Fecha Programada</label>
                    <p style="color: var(--primary);">
                        <?php echo date('d/m/Y h:i A', strtotime($ticket['fecha_programada'])); ?>
                    </p>
                </div>
                <div class="info-item">
                    <label><i class="fa fa-sync"></i> Tipo de Frecuencia</label>
                    <p><?php echo $ticket['tipo_programacion'] ?: 'One-time'; ?></p>
                </div>
                <?php if(!empty($ticket['tiempo_completar'])): ?>
                <div class="info-item">
                    <label><i class="fa fa-clock"></i> Tiempo Estimado</label>
                    <p><?php echo $ticket['tiempo_completar']; ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="info-grid">
                <div class="info-item">
                    <label>Asunto</label>
                    <p><?php echo $ticket['asunto']; ?></p>
                </div>
                <div class="info-item">
                    <label>Solicitante</label>
                    <p><?php echo $ticket['solicitante']; ?></p>
                </div>
                <div class="info-item">
                    <label>Departamento</label>
                    <p><?php echo $ticket['departamento']; ?></p>
                </div>
                <div class="info-item">
                    <label>Prioridad</label>
                    <p><?php echo $ticket['prioridad']; ?></p>
                </div>
            </div>

            <div class="info-item" style="margin-bottom: 20px;">
                <label>Descripción</label>
                <p style="background: #f4f7fe; padding: 15px; border-radius: 10px;"><?php echo $ticket['descripcion']; ?></p>
            </div>

            <?php if ($rol_actual == 'admin' || $rol_actual == 'tecnico'): ?>
            <div class="form-edit">
                <h3><i class="fa fa-edit"></i> Gestión de Ticket</h3>
                <form action="actualizar_ticket.php" method="POST">
                    <input type="hidden" name="id_ticket" value="<?php echo $ticket['id']; ?>">
                    
                    
                        <div class="form-group">
                        <label>Asignar a (Múltiple):</label>
                        <?php $asignados_actuales = explode(', ', $ticket['asignado_a']); ?>
                        <select name="asignado_a[]" class="select2-multiple" multiple="multiple">
                            <?php foreach($usuarios_lista as $tecnico): ?>
                                <option value="<?php echo $tecnico; ?>" <?php echo (in_array($tecnico, $asignados_actuales)) ? 'selected' : ''; ?>>
                                    <?php echo $tecnico; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        </div>
                    

                    <div class="form-group">
                        <label>Cambiar Estado:</label>
                        <select name="nuevo_estado">
                            <option value="Abierto" <?php if($ticket['estado'] == 'Abierto') echo 'selected'; ?>>Abierto</option>
                            <option value="En Proceso" <?php if($ticket['estado'] == 'En Proceso') echo 'selected'; ?>>En Proceso</option>
                            <option value="Resuelto" <?php if($ticket['estado'] == 'Resuelto') echo 'selected'; ?>>Resuelto</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-update">Guardar Cambios</button>
                </form>
            </div>
            <?php else: ?>
                <div class="info-item">
                    <label>Asignado a</label>
                    <p><?php echo $ticket['asignado_a'] ?: 'Pendiente de asignación'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>