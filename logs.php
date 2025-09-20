<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_errno) {
    http_response_code(500);
    die("DB error: ".$conn->connect_error);
}

// Ambil 100 log terbaru dengan nama kamera
$result = $conn->query("
    SELECT logs.*, cameras.name 
    FROM logs 
    LEFT JOIN cameras ON cameras.id = logs.camera_id 
    ORDER BY created_at DESC 
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Log Streaming</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Style utama -->
  <link href="style.css" rel="stylesheet">

  <style>
    /* Tambahan khusus halaman log */
    .table-modern th,
    .table-modern td {
      text-align: center;          /* Tulisan di tengah */
      vertical-align: middle;
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark navbar-expand-lg shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🎥 Live CCTV</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navMain" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php">📷 Kamera</a></li>
        <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
        <li class="nav-item"><a class="nav-link active" href="logs.php">📜 Log</a></li>
      </ul>
      <a href="index.php" class="btn btn-light btn-sm">← Kembali</a>
    </div>
  </div>
</nav>

<!-- CONTENT -->
<main class="container py-4">
  <h2 class="fw-bold mb-3 text-center">📜 Log Streaming Terbaru</h2>
  <div class="table-responsive">
    <table class="table table-modern table-striped align-middle">
      <thead>
        <tr>
          <th style="width:40%">📷 Kamera</th>
          <th>⚡ Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while($log = $result->fetch_assoc()): ?>
          <?php
            $camName   = htmlspecialchars($log['name'] ?? '-');
            $actionRaw = trim($log['action'] ?? '');
            $actionEsc = htmlspecialchars($actionRaw);

            // Kelas badge berdasarkan kata kunci
            $badgeClass = 'badge-info';
            if (stripos($actionRaw, 'start') !== false) {
              $badgeClass = 'badge-start';
            } elseif (stripos($actionRaw, 'stop') !== false) {
              $badgeClass = 'badge-stop';
            } elseif (stripos($actionRaw, 'error') !== false || stripos($actionRaw, 'fail') !== false) {
              $badgeClass = 'badge-error';
            }

            // Jika action adalah URL, tampilkan sebagai tautan
            $isUrl = filter_var($actionRaw, FILTER_VALIDATE_URL);
          ?>
          <tr>
            <td><span class="chip-camera"><?= $camName ?></span></td>
            <td>
              <?php if ($isUrl): ?>
                <a href="<?= $actionEsc ?>" target="_blank" rel="noopener" class="text-decoration-none">
                  <span class="badge-action <?= $badgeClass ?>"><?= $actionEsc ?></span>
                </a>
              <?php else: ?>
                <span class="badge-action <?= $badgeClass ?>"><?= $actionEsc !== '' ? $actionEsc : '—' ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- FOOTER -->
<footer>
  &copy; <?= date('Y') ?> Live CCTV by HNG Group
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
