<?php
include 'config.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($host, $user, $pass, $db);
    $name = $_POST['name'];
    $rtsp = $_POST['rtsp'];
    $rtmp_server = $_POST['rtmp_server'];
    $stream_key = $_POST['stream_key'];
    $resolution = $_POST['resolution'];
    $mode = 'copy';
    $check = shell_exec("ffprobe -v error -select_streams v:0 -show_entries stream=codec_name -of csv=p=0 " . escapeshellarg($rtsp));
    if (strpos($check, 'h264') === false) {
        $mode = 'transcode';
    }
    $stmt = $conn->prepare("INSERT INTO cameras (name, rtsp, rtmp_server, stream_key, status, mode, resolution) VALUES (?, ?, ?, ?, 'OFFLINE', ?, ?)");
    $stmt->bind_param("ssssss", $name, $rtsp, $rtmp_server, $stream_key, $mode, $resolution);
    $stmt->execute();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Kamera</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="menu">
    <a href="index.php">📷 Kamera</a>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="logs.php">📜 Log</a>
</div>

<div class="container">
    <h2>Tambah Kamera</h2>
    <form method="post">
        <label>Nama:</label><input name="name" required>
        <label>RTSP URL:</label><input name="rtsp" required>
        <label>RTMP Server:</label><input name="rtmp_server" value="rtmp://a.rtmp.youtube.com/live2" required>
        <label>Stream Key:</label><input name="stream_key" required>
        <label>Resolusi:</label>
        <select name="resolution">
            <option value="1920x1080">1920x1080</option>
            <option value="1280x720" selected>1280x720</option>
            <option value="640x360">640x360</option>
        </select>
        <button type="submit">Simpan</button>
    </form>
    <br><a href="index.php" style="color:#0af;">← Kembali</a>
</div>
</body>
</html>
