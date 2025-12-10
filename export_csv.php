<?php
// export_csv.php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
    echo "Acceso denegado.";
    exit();
}

// Config DB (ajustar credenciales)
$DB_HOST = 'localhost';
$DB_USER = 'usuario';
$DB_PASS = 'password';
$DB_NAME = 'evaluaciones_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Error BD");
}
$conn->set_charset('utf8mb4');

// Recuperar todos los resultados con sus respuestas en formato ancho
$sql = "SELECT r.id, e.nombre, r.puntaje, r.fecha
        FROM resultados r
        JOIN estudiantes e ON r.estudiante_id = e.id
        ORDER BY r.fecha ASC";
$res = $conn->query($sql);

if (!$res) {
    echo "No hay datos.";
    exit();
}

// Preparar encabezado CSV
$filename = "resultados_evaluacion_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Abrir salida
$out = fopen('php://output', 'w');

// BOM para Excel en UTF-8
fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabecera: nombre, puntaje, fecha, P1...P15
$header = array_merge(['Nombre', 'Puntaje', 'Fecha'], array_map(function($i){ return 'P'.$i; }, range(1,15)));
fputcsv($out, $header, ';');

// Por cada resultado, buscar respuestas y armar fila
$stmt = $conn->prepare("SELECT pregunta, respuesta FROM respuestas WHERE resultado_id = ? ORDER BY id ASC");

while ($row = $res->fetch_assoc()) {
    $resultado_id = intval($row['id']);
    $stmt->bind_param("i", $resultado_id);
    $stmt->execute();
    $rres = $stmt->get_result();

    // inicializar array de 15 respuestas vacías
    $respArr = array_fill(1, 15, '');

    while ($rr = $rres->fetch_assoc()) {
        if (preg_match('/Pregunta\s*(\d+)/i', $rr['pregunta'], $m)) {
            $q = intval($m[1]);
            if ($q >=1 && $q <=15) {
                $respArr[$q] = $rr['respuesta'];
            }
        }
    }

    $fila = array_merge([$row['nombre'], $row['puntaje'], $row['fecha']], array_values($respArr));
    fputcsv($out, $fila, ';');
}

$stmt->close();
fclose($out);
$conn->close();
exit();
?>
