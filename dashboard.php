<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);

// Data status kamera
$status = $conn->query("SELECT status, COUNT(*) as count FROM cameras GROUP BY status");
$online = 0; $offline = 0;
while ($row = $status->fetch_assoc()) {
    if ($row['status'] == 'ONLINE') $online = $row['count'];
    else $offline = $row['count'];
}

// Data log 24 jam terakhir
$data = [];
$res = $conn->query("SELECT HOUR(timestamp) as hour, COUNT(*) as count FROM logs WHERE timestamp >= NOW() - INTERVAL 1 DAY GROUP BY HOUR(timestamp)");
while ($row = $res->fetch_assoc()) {
    $data[intval($row['hour'])] = intval($row['count']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Kamera</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="menu">
    <a href="index.php">📷 Kamera</a>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="logs.php">📜 Log</a>
</div>

<div class="container">
    <h2>📊 Dashboard Monitoring</h2>
    <canvas id="statusChart" width="400" height="200"></canvas>
    <br>
    <canvas id="logChart" width="400" height="200"></canvas>
    <br><a href="index.php">← Kembali</a>
</div>
<script>
const ctx1 = document.getElementById('statusChart').getContext('2d');
new Chart(ctx1, {
    type: 'pie',
    data: {
        labels: ['ONLINE', 'OFFLINE'],
        datasets: [{
            label: 'Status Kamera',
            data: [<?= $online ?>, <?= $offline ?>],
            backgroundColor: ['#0f0', '#f00']
        }]
    }
});

const ctx2 = document.getElementById('logChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: [...Array(24).keys()],
        datasets: [{
            label: 'Log Streaming per Jam (24 Jam)',
            data: [<?php for($i=0;$i<24;$i++) echo ($data[$i] ?? 0).","; ?>],
            backgroundColor: '#0af'
        }]
    }
});
</script>
</body>
</html>
