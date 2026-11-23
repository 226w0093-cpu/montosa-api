<?php
    header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
header("Content-Type: application/json; charset=UTF-8");

require_once "conexion.php";

// Leer datos enviados en formato JSON desde la App
$json = file_get_contents("php://input");
$datos = json_decode($json, true);

if (!$datos) {
    echo json_encode([
        "status" => "error",
        "message" => "No se recibieron datos o el formato JSON es incorrecto."
    ]);
    exit();
}

// Determinar el tipo de acción o sincronización
$accion = isset($datos['accion']) ? $datos['accion'] : 'equipo';

if ($accion == 'bitacora') {
    // -----------------------------------------------------------
    // REGISTRO EN BITÁCORA (Entradas / Salidas del Taller)
    // -----------------------------------------------------------
    $tipo_movimiento = isset($datos['tipo_movimiento']) ? strtoupper(trim($conn->real_escape_string($datos['tipo_movimiento']))) : 'ENTRADA';
    $numero_serie   = isset($datos['numero_serie']) ? trim($conn->real_escape_string($datos['numero_serie'])) : '';
    $modelo         = isset($datos['modelo']) ? trim($conn->real_escape_string($datos['modelo'])) : '';
    $cliente_id     = isset($datos['cliente_id']) ? trim($conn->real_escape_string($datos['cliente_id'])) : '';
    $origen_destino = isset($datos['origen_destino']) ? trim($conn->real_escape_string($datos['origen_destino'])) : '';
    $responsable    = isset($datos['responsable_traslado']) ? trim($conn->real_escape_string($datos['responsable_traslado'])) : '';
    $observaciones  = isset($datos['observaciones']) ? trim($conn->real_escape_string($datos['observaciones'])) : '';
    $id_usuario     = isset($datos['id_usuario_tecnico']) ? intval($datos['id_usuario_tecnico']) : NULL;

    if (empty($numero_serie)) {
        echo json_encode(["status" => "error", "message" => "El número de serie es obligatorio para la bitácora."]);
        exit();
    }

    $sqlBit = "INSERT INTO bitacora_movimientos 
               (tipo_movimiento, numero_serie, modelo, cliente_id, origen_destino, responsable_traslado, observaciones, id_usuario_tecnico, fecha_movimiento) 
               VALUES 
               ('$tipo_movimiento', '$numero_serie', '$modelo', '$cliente_id', '$origen_destino', '$responsable', '$observaciones', " . ($id_usuario ? $id_usuario : "NULL") . ", NOW())";

    if ($conn->query($sqlBit)) {
        echo json_encode(["status" => "success", "message" => "Movimiento guardado exitosamente en la bitácora."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al registrar en bitácora: " . $conn->error]);
    }

} else {
    // -----------------------------------------------------------
    // REGISTRO O ACTUALIZACIÓN DE EQUIPOS
    // -----------------------------------------------------------
    $numero_serie  = isset($datos['numero_serie']) ? trim($conn->real_escape_string($datos['numero_serie'])) : '';
    $modelo        = isset($datos['modelo']) ? trim($conn->real_escape_string($datos['modelo'])) : '';
    $compania      = isset($datos['compania']) ? trim($conn->real_escape_string($datos['compania'])) : '';
    $id_estatus    = isset($datos['id_estatus']) ? intval($datos['id_estatus']) : 1;
    $revisado      = isset($datos['revisado']) ? intval($datos['revisado']) : 0;
    $fecha_equipos = !empty($datos['fecha_equipos']) ? $conn->real_escape_string($datos['fecha_equipos']) : date('Y-m-d');
    $observaciones = isset($datos['observaciones_tecnicas']) ? trim($conn->real_escape_string($datos['observaciones_tecnicas'])) : '';

    if (empty($numero_serie)) {
        echo json_encode(["status" => "error", "message" => "El número de serie es obligatorio."]);
        exit();
    }

    // Verificar si el equipo ya existe en la BD
    $checkSql = "SELECT id_equipo FROM equipos WHERE numero_serie = '$numero_serie'";
    $checkRes = $conn->query($checkSql);

    if ($checkRes && $checkRes->num_rows > 0) {
        // UPDATE
        $sql = "UPDATE equipos SET 
                    modelo = '$modelo',
                    compania = '$compania',
                    id_estatus = $id_estatus,
                    revisado = $revisado,
                    fecha_equipos = '$fecha_equipos',
                    observaciones_tecnicas = '$observaciones',
                    fecha_ultima_modificacion = NOW()
                WHERE numero_serie = '$numero_serie'";

        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Equipo actualizado correctamente.", "tipo" => "UPDATE"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al actualizar: " . $conn->error]);
        }
    } else {
        // INSERT
        $sql = "INSERT INTO equipos (numero_serie, modelo, compania, id_estatus, revisado, fecha_equipos, observaciones_tecnicas, fecha_registro, fecha_ultima_modificacion) 
                VALUES ('$numero_serie', '$modelo', '$compania', $id_estatus, $revisado, '$fecha_equipos', '$observaciones', NOW(), NOW())";

        if ($conn->query($sql)) {
            echo json_encode(["status" => "success", "message" => "Equipo registrado exitosamente.", "tipo" => "INSERT", "id_equipo" => $conn->insert_id]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al insertar: " . $conn->error]);
        }
    }
}

$conn->close();
?>