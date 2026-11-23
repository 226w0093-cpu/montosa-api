<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
header("Content-Type: application/json");
require_once "conexion.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto']) && isset($_POST['numero_serie'])) {
    $serie = $conn->real_escape_string($_POST['numero_serie']);
    
    // Obtener ID del equipo
    $resEquipo = $conn->query("SELECT id_equipo FROM equipos WHERE numero_serie = '$serie'");
    if ($resEquipo && $resEquipo->num_rows > 0) {
        $id_equipo = $resEquipo->fetch_assoc()['id_equipo'];
        
        $directorio = "../uploads/";
        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $nombreArchivo = time() . "_" . basename($_FILES["foto"]["name"]);
        $rutaFinal = $directorio . $nombreArchivo;

        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $rutaFinal)) {
            $rutaBD = "uploads/" . $nombreArchivo;
            $conn->query("INSERT INTO fotos_equipo (id_equipo, ruta_imagen) VALUES ($id_equipo, '$rutaBD')");
            echo json_encode(["status" => "success", "mensaje" => "Foto subida con éxito"]);
        } else {
            echo json_encode(["status" => "error", "mensaje" => "Error al guardar la imagen"]);
        }
    } else {
        echo json_encode(["status" => "error", "mensaje" => "Equipo no encontrado"]);
    }
} else {
    echo json_encode(["status" => "error", "mensaje" => "Parámetros incompletos"]);
}
?>