<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
$id = $_GET['id'];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $rtsp = $_POST['rtsp'];
    $rtmp_server = $_POST['rtmp_server'];
    $stream_key = $_POST['stream_key'];
    $resolution = $_POST['resolution'];
    $stmt = $conn->prepare("UPDATE cameras SET name=?, rtsp=?, rtmp_server=?, stream_key=?, resolution=? WHERE id=?");
    $stmt->bind_param("sssssi", $name, $rtsp, $rtmp_server, $stream_key, $resolution, $id);
    $stmt->execute();
    header("Location: index.php");
    exit();
}
$res = $conn->query("SELECT * FROM cameras WHERE id=$id")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Kamera</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="menu">
    <a href="index.php">📷 Kamera</a>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="logs.php">📜 Log</a>
</div>

<div class="container">
    <h2>Edit Kamera</h2>
    <form method="post">
        <label>Nama:</label><input name="name" value="<?= htmlspecialchars($res['name']) ?>" required>
        <label>RTSP URL:</label><input name="rtsp" value="<?= htmlspecialchars($res['rtsp']) ?>" required>
        <label>RTMP Server:</label><input name="rtmp_server" value="<?= htmlspecialchars($res['rtmp_server']) ?>" required>
        <label>Stream Key:</label><input name="stream_key" value="<?= htmlspecialchars($res['stream_key']) ?>" required>
        <label>Resolusi:</label>
        <select name="resolution">
            <option value="1920x1080" <?= $res['resolution'] == '1920x1080' ? 'selected' : '' ?>>1920x1080</option>
            <option value="1280x720" <?= $res['resolution'] == '1280x720' ? 'selected' : '' ?>>1280x720</option>
            <option value="640x360" <?= $res['resolution'] == '640x360' ? 'selected' : '' ?>>640x360</option>
        </select>
        <button type="submit">Update</button>
    </form>
    <br><a href="index.php" style="color:#0af;">← Kembali</a>
</div>
</body>
</html>
