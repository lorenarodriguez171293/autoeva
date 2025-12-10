<?php
session_start();

// Simulación de rol docente
$_SESSION['rol'] = "docente";

if ($_SESSION['rol'] !== 'docente') {
    echo "Acceso denegado.";
    exit();
}

$conn = new mysqli("localhost", "usuario", "password", "evaluaciones_db");

$sql = "SELECT r.id, e.nombre, r.puntaje, r.fecha 
        FROM resultados r
        JOIN estudiantes e ON r.estudiante_id = e.id
        ORDER BY r.fecha DESC";

$result = $conn->query($sql);
?>

<h1>Panel del Docente – Resultados</h1>

<table border="1" cellpadding="10">
    <tr>
        <th>Estudiante</th>
        <th>Puntaje</th>
        <th>Fecha</th>
        <th>Ver Respuestas</th>
    </tr>

<?php while ($row = $result->fetch_assoc()) { ?>
    <tr>
        <td><?= $row["nombre"] ?></td>
        <td><?= $row["puntaje"] ?></td>
        <td><?= $row["fecha"] ?></td>
        <td><a href="ver_respuestas.php?id=<?= $row['id'] ?>">Ver</a></td>
    </tr>
<?php } ?>
</table>
