<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_errno) {
        http_response_code(500);
        die("DB error: " . $conn->connect_error);
    }

    $name        = $_POST['name'] ?? '';
    $rtsp        = $_POST['rtsp'] ?? '';
    $rtmp_server = $_POST['rtmp_server'] ?? '';
    $stream_key  = $_POST['stream_key'] ?? '';
    $resolution  = $_POST['resolution'] ?? '1280x720';

    // Mode dari user: 'copy' atau 'transcode'
    $mode = $_POST['mode'] ?? '';

    // Validasi pilihan; jika tidak valid/ kosong -> fallback ke auto-detect
    if (!in_array($mode, ['copy', 'transcode'], true)) {
        $mode = 'copy';
        $cmd  = "ffprobe -v error -select_streams v:0 -show_entries stream=codec_name -of csv=p=0 " . escapeshellarg($rtsp);
        $check = @shell_exec($cmd);
        if (strpos((string)$check, 'h264') === false) {
            $mode = 'transcode';
        }
    }

    $stmt = $conn->prepare("INSERT INTO cameras (name, rtsp, rtmp_server, stream_key, status, mode, resolution) VALUES (?, ?, ?, ?, 'OFFLINE', ?, ?)");
    $stmt->bind_param("ssssss", $name, $rtsp, $rtmp_server, $stream_key, $mode, $resolution);
    $stmt->execute();

    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Tambah Kamera</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Styles eksternal -->
  <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- NAV -->
<nav class="navbar navbar-expand-lg shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🎥 Live CCTV</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
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
          <h2 class="h4 fw-bold mb-0">Tambah Kamera</h2>
        </div>

        <form method="post" class="row g-3">
          <div class="col-12">
            <label for="name" class="form-label">Nama Kamera</label>
            <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="Contoh: Kamera Depan Toko" required>
          </div>

          <div class="col-12">
            <label for="rtsp" class="form-label">RTSP URL</label>
            <input type="url" class="form-control" id="rtsp" name="rtsp" placeholder="rtsp://user:pass@ip:554/stream" required>
            <div class="form-text">Pastikan bisa diakses dari server ini.</div>
          </div>

          <div class="col-md-7">
            <label for="rtmp_server" class="form-label">RTMP Server</label>
            <input type="url" class="form-control" id="rtmp_server" name="rtmp_server" value="rtmp://a.rtmp.youtube.com/live2" required>
            <div class="form-text">Default YouTube Live RTMP.</div>
          </div>

          <div class="col-md-5">
            <label for="stream_key" class="form-label">Stream Key</label>
            <input type="text" class="form-control" id="stream_key" name="stream_key" placeholder="xxxx-xxxx-xxxx-xxxx" required>
          </div>

          <div class="col-md-6">
            <label for="resolution" class="form-label">Resolusi</label>
            <select class="form-select" id="resolution" name="resolution">
              <option value="1920x1080">1920 × 1080 (Full HD)</option>
              <option value="1280x720" selected>1280 × 720 (HD)</option>
              <option value="640x360">640 × 360 (SD)</option>
            </select>
          </div>

          <!-- NEW: Mode Video -->
          <div class="col-md-6">
            <label for="mode" class="form-label">Mode Video</label>
            <select class="form-select" id="mode" name="mode">
              <option value="copy" selected>Copy (passthrough H.264, hemat CPU)</option>
              <option value="transcode">Transcode (libx264, kompatibel & kontrol bitrate)</option>
            </select>
            <div class="form-text">Pilih <strong>copy</strong> jika sumber sudah H.264; gunakan <strong>transcode</strong> bila codec tidak kompatibel atau butuh kontrol kualitas/bitrate.</div>
          </div>

          <div class="col-12 d-flex gap-2 mt-2">
            <button type="submit" class="btn-genz btn-save">💾 Simpan</button>
            <a href="index.php" class="btn-genz btn-back">← Kembali</a>
          </div>
        </form>

        <!-- Info kecil tentang auto-fallback -->
        <div class="mt-3 muted" style="font-size: 0.9rem;">
          Catatan: jika pilihan mode tidak valid, sistem otomatis mendeteksi codec (H.264 = copy, selain itu = transcode).
        </div>
      </div>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
