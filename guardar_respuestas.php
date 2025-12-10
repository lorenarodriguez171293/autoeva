<?php
header("Content-Type: text/plain");

$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data["nombre"];
$puntaje = $data["puntaje"];
$respuestas = $data["respuestas"];

$conn = new mysqli("localhost", "usuario", "password", "evaluaciones_db");

if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}

$stmt = $conn->prepare("INSERT INTO estudiantes (nombre) VALUES (?)");
$stmt->bind_param("s", $nombre);
$stmt->execute();
$estudiante_id = $stmt->insert_id;

$stmt2 = $conn->prepare("INSERT INTO resultados (estudiante_id, puntaje, fecha) VALUES (?, ?, NOW())");
$stmt2->bind_param("ii", $estudiante_id, $puntaje);
$stmt2->execute();
$resultado_id = $stmt2->insert_id;

$stmt3 = $conn->prepare("INSERT INTO respuestas (resultado_id, pregunta, respuesta, es_correcta) VALUES (?, ?, ?, ?)");

foreach ($respuestas as $r) {
    $pregunta = $r["pregunta"];
    $respuesta = $r["respuesta"];
    $correcta = $r["correcta"] ? 1 : 0;

    $stmt3->bind_param("issi", $resultado_id, $pregunta, $respuesta, $correcta);
    $stmt3->execute();
}

echo "Respuestas enviadas correctamente.";
?>
