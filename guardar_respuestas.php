<?php
// guardar_respuestas.php
header('Content-Type: text/plain; charset=utf-8');

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || !isset($data['nombre'])) {
    http_response_code(400);
    echo "Datos inválidos.";
    exit();
}

$nombre = trim($data['nombre']);
$puntaje = isset($data['puntaje']) ? intval($data['puntaje']) : 0;
$respuestas = isset($data['respuestas']) ? $data['respuestas'] : [];

if ($nombre === '') {
    http_response_code(400);
    echo "Nombre vacío.";
    exit();
}

// Config DB - ajustar credenciales
$DB_HOST = 'localhost';
$DB_USER = 'usuario';
$DB_PASS = 'password';
$DB_NAME = 'evaluaciones_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo "Error de conexión a la base de datos.";
    exit();
}
$conn->set_charset('utf8mb4');

try {
    $conn->begin_transaction();

    // 1) Guardar estudiante
    $stmt = $conn->prepare("INSERT INTO estudiantes (nombre) VALUES (?)");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $estudiante_id = $stmt->insert_id;
    $stmt->close();

    // 2) Guardar resultado
    $stmt2 = $conn->prepare("INSERT INTO resultados (estudiante_id, puntaje, fecha) VALUES (?, ?, NOW())");
    $stmt2->bind_param("ii", $estudiante_id, $puntaje);
    $stmt2->execute();
    $resultado_id = $stmt2->insert_id;
    $stmt2->close();

    // 3) Guardar respuestas
    $stmt3 = $conn->prepare("INSERT INTO respuestas (resultado_id, pregunta, respuesta, es_correcta) VALUES (?, ?, ?, ?)");
    foreach ($respuestas as $r) {
        $pregunta = isset($r['pregunta']) ? $r['pregunta'] : '';
        $respuesta = isset($r['respuesta']) ? $r['respuesta'] : '';
        $es_correcta = !empty($r['correcta']) ? 1 : 0;
        $stmt3->bind_param("issi", $resultado_id, $pregunta, $respuesta, $es_correcta);
        $stmt3->execute();
    }
    $stmt3->close();

    $conn->commit();
    echo "Respuestas enviadas correctamente. Gracias.";
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo "Error al guardar: " . $e->getMessage();
} finally {
    $conn->close();
}
?>

