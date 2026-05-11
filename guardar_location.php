<?php
session_start(); // ¡Súper importante para atrapar el nombre de quien lo crea!
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Capturamos los datos del formulario
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre_ubicacion']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion'] ?? '');
    
    // Si no se selecciona un padre, el valor debe ser NULL (sin comillas) en la base de datos
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : "NULL";

    // 2. MAGIA AUTOMÁTICA: Fechas y Creador
    // date() genera la fecha y hora actual del servidor.
    $fecha_actual = date('Y-m-d H:i:s'); 
    // Tomamos el nombre del usuario de la sesión. Si por algo no hay, ponemos 'Sistema'.
    $creado_por = isset($_SESSION['nombre']) ? mysqli_real_escape_string($conexion, $_SESSION['nombre']) : 'Sistema';

    // 3. Insertamos la nueva ubicación con TODAS sus columnas
    $sql = "INSERT INTO locations (nombre_ubicacion, parent_id, descripcion, created_at, updated_at, created_by) 
            VALUES ('$nombre', $parent_id, '$descripcion', '$fecha_actual', '$fecha_actual', '$creado_por')";
            
    if (mysqli_query($conexion, $sql)) {
        // Redirigimos de vuelta a la sección de locations
        header("Location: Index.php?vista=locations");
        exit();
    } else {
        // Si hay error, lo mostramos para saber qué falló
        echo "<div style='padding:20px; background:#ffe6e6; color:#f53939; text-align:center;'>
                <h3>Error al registrar la ubicación</h3>
                <p>" . mysqli_error($conexion) . "</p>
                <a href='Index.php?vista=locations'>Volver</a>
              </div>";
    }
}
?>