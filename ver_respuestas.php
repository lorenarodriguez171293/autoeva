<?php
// ver_respuestas.php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
    echo "Acceso denegado.";
    exit();
}

if (!isset($_GET['id'])) {
    echo "ID inválido.";
    exit();
}

$id = intval($_GET['id']);

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

$sql = "SELECT e.nombre, r.puntaje, r.fecha
        FROM resultados r
        JOIN estudiantes e ON r.estudiante_id = e.id
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($nombre, $puntaje, $fecha);
if (!$stmt->fetch()) {
    echo "Resultado no encontrado.";
    exit();
}
$stmt->close();

$sql2 = "SELECT pregunta, respuesta, es_correcta FROM respuestas WHERE resultado_id = ? ORDER BY id ASC";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $id);
$stmt2->execute();
$res2 = $stmt2->get_result();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Respuestas del Estudiante</title>
    <style>
        body { font-family: Arial, sans-serif; margin:20px; max-width:900px;}
        h1 { color:#1f6feb; }
        table { border-collapse: collapse; width:100%; margin-top:12px; }
        th, td { padding:8px; border:1px solid #ddd; text-align:left; }
    </style>
</head>
<body>
    <h1>Respuestas de: <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></h1>
    <p><strong>Puntaje:</strong> <?= intval($puntaje) ?> / 15</p>
    <p><strong>Fecha:</strong> <?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></p>

    <table>
        <thead>
            <tr><th>Pregunta</th><th>Respuesta</th><th>Correcta</th></tr>
        </thead>
        <tbody>
            <?php while ($row = $res2->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['pregunta'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($row['respuesta'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $row['es_correcta'] ? '✔' : '✘' ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <p><a href="panel_docente.php">Volver al panel</a></p>
</body>
</html>
<?php
$stmt2->close();
$conn->close();
?>
