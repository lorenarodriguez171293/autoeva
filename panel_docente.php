<?php
// panel_docente.php
session_start();

/*
  IMPORTANTE:
  - En tu sistema real de autenticación, la sesión debe establecerse en el login.
  - Para pruebas locales sin login, puedes descomentar la línea siguiente para simular docente:
*/
// $_SESSION['rol'] = 'docente';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'docente') {
    echo "Acceso denegado. Solo docentes.";
    exit();
}

// Config DB
$DB_HOST = 'localhost';
$DB_USER = 'usuario';
$DB_PASS = 'password';
$DB_NAME = 'evaluaciones_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Error BD");
}
$conn->set_charset('utf8mb4');

// 1) Obtener resultados generales
$sql = "SELECT r.id, e.nombre, r.puntaje, r.fecha
        FROM resultados r
        JOIN estudiantes e ON r.estudiante_id = e.id
        ORDER BY r.fecha DESC";
$res = $conn->query($sql);

// 2) Preparar datos para gráficos
// a) Distribución de puntajes (0..15)
$dist = array_fill(0, 16, 0);

// b) Correctas por pregunta (pregunta1..pregunta15)
$correct_by_q = array_fill(1, 15, 0);
$total_by_q = array_fill(1, 15, 0);

$sql2 = "SELECT r.id as resultado_id, rp.pregunta, rp.es_correcta
         FROM respuestas rp
         JOIN resultados r ON rp.resultado_id = r.id";
$res2 = $conn->query($sql2);
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        // obtener número de pregunta (asumimos formato "Pregunta N")
        if (preg_match('/Pregunta\s*(\d+)/i', $row['pregunta'], $m)) {
            $qnum = intval($m[1]);
            if ($qnum >=1 && $qnum <= 15) {
                $total_by_q[$qnum] += 1;
                if ($row['es_correcta']) $correct_by_q[$qnum] += 1;
            }
        }
    }
    $res2->free();
}

// calcular distribución de puntajes
$sql3 = "SELECT puntaje, COUNT(*) as c FROM resultados GROUP BY puntaje";
$res3 = $conn->query($sql3);
if ($res3) {
    while ($r = $res3->fetch_assoc()) {
        $p = intval($r['puntaje']);
        if ($p >= 0 && $p <= 15) $dist[$p] = intval($r['c']);
    }
    $res3->free();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Panel docente - Resultados</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; max-width:1100px; }
        h1 { color:#1f6feb; }
        table { border-collapse: collapse; width:100%; margin-top:12px; }
        th, td { padding:8px; border:1px solid #ddd; text-align:left; }
        .controls { margin:12px 0; }
        .charts { display:flex; gap:20px; flex-wrap:wrap; }
        .chart-card { flex:1 1 480px; background:#fafafa; padding:12px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
    </style>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Panel del docente — Resultados</h1>

    <div class="controls">
        <a href="export_csv.php" target="_blank">
            <button>Descargar resultados (Excel - CSV)</button>
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Puntaje</th>
                <th>Fecha</th>
                <th>Ver</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= intval($row['puntaje']) ?></td>
                    <td><?= htmlspecialchars($row['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><a href="ver_respuestas.php?id=<?= intval($row['id']) ?>">Ver respuestas</a></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No hay resultados aún.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h2>Estadísticas</h2>
    <div class="charts">
        <div class="chart-card">
            <h3>Distribución de puntajes (0–15)</h3>
            <canvas id="chartDist"></canvas>
        </div>

        <div class="chart-card">
            <h3>% de respuestas correctas por pregunta</h3>
            <canvas id="chartCorrect"></canvas>
        </div>
    </div>

    <script>
        // Datos PHP a JS
        const dist = <?= json_encode(array_values($dist), JSON_THROW_ON_ERROR) ?>;
        // calcular labels 0..15
        const labelsDist = Array.from({length: dist.length}, (_,i) => String(i));

        // correct_by_q y total_by_q desde PHP
        const correctByQ = <?= json_encode(array_values($correct_by_q), JSON_THROW_ON_ERROR) ?>;
        const totalByQ = <?= json_encode(array_values($total_by_q), JSON_THROW_ON_ERROR) ?>;

        // convertir a porcentaje (0-100)
        const percentByQ = correctByQ.map((c, idx) => {
            const total = totalByQ[idx] || 0;
            return total ? Math.round((c / total) * 100) : 0;
        });

        // Chart 1: distribución de puntajes
        new Chart(document.getElementById('chartDist'), {
            type: 'bar',
            data: {
                labels: labelsDist,
                datasets: [{
                    label: 'Cantidad de estudiantes',
                    data: dist
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, ticks: { precision:0 } }
                }
            }
        });

        // Chart 2: % correctas por pregunta
        const qLabels = Array.from({length: percentByQ.length}, (_,i) => 'P' + (i+1));
        new Chart(document.getElementById('chartCorrect'), {
            type: 'bar',
            data: {
                labels: qLabels,
                datasets: [{
                    label: '% correctas',
                    data: percentByQ
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: {
                    x: { beginAtZero: true, max: 100 }
                }
            }
        });
    </script>

</body>
</html>
