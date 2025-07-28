<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
$result = $conn->query("SELECT logs.*, cameras.name FROM logs LEFT JOIN cameras ON cameras.id = logs.camera_id ORDER BY timestamp DESC LIMIT 100");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Log Streaming</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="menu">
    <a href="index.php">📷 Kamera</a>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="logs.php">📜 Log</a>
</div>

<div class="container">
    <h2>📜 Log Streaming Terbaru</h2>
    <table>
        <tr><th>Waktu</th><th>Kamera</th><th>Aksi</th></tr>
        <?php while($log = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $log['timestamp'] ?></td>
            <td><?= htmlspecialchars($log['name']) ?></td>
            <td><?= $log['action'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <br><a href="index.php">← Kembali</a>
</div>
</body>
</html>
