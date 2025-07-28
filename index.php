<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
$cameras = $conn->query("SELECT * FROM cameras");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Live YouTube</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="menu">
    <a href="index.php">📷 Kamera</a>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="logs.php">📜 Log</a>
</div>

<div class="container">
    <h2>📹 Daftar Kamera</h2>
    <a class="btn" href="add.php">+ Tambah Kamera</a>
    <table>
        <tr><th>Nama</th><th>Status</th><th>RTSP</th><th>RTMP</th><th>Aksi</th></tr>
        <?php while($cam = $cameras->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($cam['name']) ?></td>
            <td><span class="<?= $cam['status'] == 'ONLINE' ? 'online' : 'offline' ?>"><?= $cam['status'] ?></span></td>
            <td><a href="<?= $cam['rtsp'] ?>" target="_blank"><?= $cam['rtsp'] ?></a></td>
            <td><a href="<?= $cam['rtmp_server'] ?>/<?= $cam['stream_key'] ?>" target="_blank"><?= $cam['rtmp_server'] ?>/<?= $cam['stream_key'] ?></a></td>
            <td>
                <a class="action-btn" href="start.php?id=<?= $cam['id'] ?>">Start</a>
                <a class="action-btn" href="stop.php?id=<?= $cam['id'] ?>">Stop</a>
                <a class="action-btn edit" href="edit.php?id=<?= $cam['id'] ?>">Edit</a>
                <a class="action-btn delete" href="delete.php?id=<?= $cam['id'] ?>">Hapus</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
