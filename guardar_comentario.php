<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ticket_id = intval($_POST['ticket_id']);
    $comentario = mysqli_real_escape_string($conexion, $_POST['comentario']);
    $usuario = mysqli_real_escape_string($conexion, $_SESSION['nombre']); 

    // Lógica para procesar la imagen
    $ruta_imagen = NULL;
    
    // Verificamos si se subió un archivo y si no hubo errores
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $directorio_destino = 'uploads/';
        
        // Si la carpeta uploads/ no existe, el sistema la crea solita
        if (!file_exists($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }
        
        // Creamos un nombre único usando la fecha/hora para no sobreescribir fotos
        $nombre_archivo = time() . '_' . basename($_FILES['imagen']['name']);
        $ruta_final = $directorio_destino . $nombre_archivo;
        
        // Movemos el archivo temporal a nuestra carpeta final
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_final)) {
            $ruta_imagen = $ruta_final;
        }
    }

    // Solo guardamos si hay un comentario de texto o si se subió una imagen
    if (!empty($comentario) || $ruta_imagen != NULL) {
        $ruta_sql = $ruta_imagen ? "'$ruta_imagen'" : "NULL";
        
        $sql = "INSERT INTO comentarios_ticket (ticket_id, usuario, comentario, imagen_url) 
                VALUES ($ticket_id, '$usuario', '$comentario', $ruta_sql)";
        mysqli_query($conexion, $sql);
    }
    
    header("Location: Index.php?vista=ver_detalle&id=" . $ticket_id);
    exit();
}
?>