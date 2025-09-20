
<!-- Css By Rangga Galih Wardani | Universitas Duta Bangsa Surakarta -->

<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_errno) {
    http_response_code(500);
    die("DB error: ".$conn->connect_error);
}
$cameras = $conn->query("SELECT * FROM cameras");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Live YouTube</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="style.css" rel="stylesheet">


</head>
<body>

<!-- NAV -->
<nav class="navbar navbar-dark navbar-expand-lg shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🎥 Live CCTV</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navMain" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link active" href="index.php">📷 Kamera</a></li>
        <li class="nav-item"><a class="nav-link" href="dashboard.php">📊 Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="logs.php">📜 Log</a></li>
      </ul>
      <a class="btn btn-genz btn-start px-4" href="add.php">+ Tambah Kamera</a>
    </div>
  </div>
</nav>

<!-- CONTENT -->
<main class="container py-4">
  <h2 class="fw-bold mb-3">📹 Daftar Kamera</h2>
  <div class="table-responsive shadow-sm rounded">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>📛 Nama</th>
          <th>📡 Status</th>
          <th>🔗 RTSP</th>
          <th>📺 RTMP</th>
          <th class="text-end">⚙️ Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($cam = $cameras->fetch_assoc()): ?>
          <?php
            $name  = htmlspecialchars($cam['name'] ?? '');
            $stat  = strtoupper($cam['status'] ?? 'OFFLINE');
            $badge = $stat === 'ONLINE' ? 'badge-online' : 'badge-offline';
            $rtsp  = htmlspecialchars($cam['rtsp'] ?? '');
            $rtmp  = htmlspecialchars(($cam['rtmp_server'] ?? '').'/'.($cam['stream_key'] ?? ''));
            $id    = (int)($cam['id'] ?? 0);
          ?>
          <tr>
            <td class="fw-semibold"><?= $name ?></td>
            <td><span class="badge <?= $badge ?> px-3 py-2"><?= $stat ?></span></td>
            <td class="text-truncate" style="max-width:220px;">
              <?= $rtsp ? "<a href='$rtsp' class='text-decoration-none text-primary' target='_blank' rel='noopener'>$rtsp</a>" : "<span class='text-muted'>-</span>" ?>
            </td>
            <td class="text-truncate" style="max-width:220px;">
              <?= trim($rtmp,'/') ? "<a href='$rtmp' class='text-decoration-none text-primary' target='_blank' rel='noopener'>$rtmp</a>" : "<span class='text-muted'>-</span>" ?>
            </td>
            <td class="text-end">
              <div class="d-flex flex-wrap gap-2 justify-content-end">
                <a class="btn btn-genz btn-start btn-sm" href="start.php?id=<?= $id ?>">▶️ Start</a>
                <a class="btn btn-genz btn-stop  btn-sm" href="stop.php?id=<?= $id ?>">⏹️ Stop</a>
                <a class="btn btn-genz btn-edit  btn-sm" href="edit.php?id=<?= $id ?>">✏️ Edit</a>
                <a class="btn btn-genz btn-delete btn-sm"
                   href="delete.php?id=<?= $id ?>"
                   onclick="return confirm('Yakin mau hapus kamera: <?= htmlspecialchars($name) ?>?')">🗑️ Hapus</a>
              </div>
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
