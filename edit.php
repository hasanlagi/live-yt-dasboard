<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_errno) {
    http_response_code(500);
    die("DB error: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    die("Invalid ID");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name        = $_POST['name'] ?? '';
    $rtsp        = $_POST['rtsp'] ?? '';
    $rtmp_server = $_POST['rtmp_server'] ?? '';
    $stream_key  = $_POST['stream_key'] ?? '';
    $resolution  = $_POST['resolution'] ?? '1280x720';
    $mode        = $_POST['mode'] ?? 'copy';
    if (!in_array($mode, ['copy','transcode'], true)) {
        $mode = 'copy';
    }

    $stmt = $conn->prepare("UPDATE cameras SET name=?, rtsp=?, rtmp_server=?, stream_key=?, resolution=?, mode=? WHERE id=?");
    $stmt->bind_param("ssssssi", $name, $rtsp, $rtmp_server, $stream_key, $resolution, $mode, $id);
    $stmt->execute();

    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, name, rtsp, rtmp_server, stream_key, resolution, mode FROM cameras WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if (!$res) {
    http_response_code(404);
    die("Kamera tidak ditemukan");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Edit Kamera</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="style.css" rel="stylesheet">

</head>
<body>

<!-- NAV -->
<nav class="navbar navbar-expand-lg shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🎥 Live CCTV</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navMain" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php">📷 Kamera</a></li>
        <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="logs.php">📜 Log</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- CONTENT -->
<main class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="card card-modern p-4 p-md-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h2 class="h4 fw-bold mb-0">Edit Kamera</h2>
        </div>

        <form method="post" class="row g-3">
          <div class="col-12">
            <label for="name" class="form-label">Nama Kamera</label>
            <input type="text" class="form-control form-control-lg" id="name" name="name"
                   value="<?= htmlspecialchars($res['name']) ?>" required>
          </div>

          <div class="col-12">
            <label for="rtsp" class="form-label">RTSP URL</label>
            <input type="url" class="form-control" id="rtsp" name="rtsp"
                   value="<?= htmlspecialchars($res['rtsp']) ?>" required>
            <div class="form-text">Format: <code>rtsp://user:pass@ip:554/path</code></div>
          </div>

          <div class="col-md-7">
            <label for="rtmp_server" class="form-label">RTMP Server</label>
            <input type="url" class="form-control" id="rtmp_server" name="rtmp_server"
                   value="<?= htmlspecialchars($res['rtmp_server']) ?>" required>
            <div class="form-text">Contoh YouTube: <code>rtmp://a.rtmp.youtube.com/live2</code></div>
          </div>

          <div class="col-md-5">
            <label for="stream_key" class="form-label">Stream Key</label>
            <input type="text" class="form-control" id="stream_key" name="stream_key"
                   value="<?= htmlspecialchars($res['stream_key']) ?>" required>
          </div>

          <div class="col-md-6">
            <label for="resolution" class="form-label">Resolusi</label>
            <select class="form-select" id="resolution" name="resolution">
              <option value="1920x1080" <?= $res['resolution']==='1920x1080' ? 'selected' : '' ?>>1920 × 1080 (Full HD)</option>
              <option value="1280x720"  <?= $res['resolution']==='1280x720'  ? 'selected' : '' ?>>1280 × 720 (HD)</option>
              <option value="640x360"   <?= $res['resolution']==='640x360'   ? 'selected' : '' ?>>640 × 360 (SD)</option>
            </select>
          </div>

          <div class="col-md-6">
            <label for="mode" class="form-label">Mode Video</label>
            <select class="form-select" id="mode" name="mode">
              <option value="copy" <?= ($res['mode'] ?? 'copy') === 'copy' ? 'selected' : '' ?>>
                Copy ( -c:v copy ) — hemat CPU, butuh sumber H.264
              </option>
              <option value="transcode" <?= ($res['mode'] ?? 'copy') === 'transcode' ? 'selected' : '' ?>>
                Transcode ( -c:v libx264 ) — kompatibel & kontrol kualitas
              </option>
            </select>
            <div class="form-text">
              Pilih <strong>copy</strong> bila kamera sudah H.264; gunakan <strong>libx264</strong> jika perlu transcode.
            </div>
          </div>

          <div class="col-12 d-flex gap-2 mt-2">
            <button type="submit" class="btn-genz btn-save">💾 Update</button>
            <a href="index.php" class="btn-genz btn-back">← Kembali</a>
          </div>
        </form>

      </div>
    </div>
  </div>
</main>

<!-- FOOTER -->
<footer>
  &copy; <?= date('Y') ?> Live CCTV by HNG Group
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
