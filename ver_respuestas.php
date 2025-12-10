<?php
session_start();

if ($_SESSION['rol'] !== 'docente') {
    echo "Acceso denegado.";
    exit();
}

$id = $_GET["id"];

$conn = new mysqli("localhost", "usuario", "password", "evaluaciones_db");

$sql = "SELECT pregunta, respuesta, es_correcta 
        FROM respuestas 
        WHERE resultado_id = $id";

$res = $conn->query($sql);
?>

<h1>Respuestas del Estudiante</h1>

<table border="1" cellpadding="10">
    <tr>
        <th>Pregunta</th>
        <th>Respuesta</th>
        <th>Correcta</th>
    </tr>

<?php while ($row = $res->fetch_assoc()) { ?>
    <tr>
        <td><?= $row["pregunta"] ?></td>
        <td><?= $row["respuesta"] ?></td>
        <td><?= $row["es_correcta"] ? "✔" : "✘" ?></td>
    </tr>
<?php } ?>
</table>

<br>
<a href="panel_docente.php">Volver al panel</a>
