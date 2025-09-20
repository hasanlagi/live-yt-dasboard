<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_errno) {
    http_response_code(500);
    die("DB error: ".$conn->connect_error);
}

/* --- Status kamera --- */
$online = 0; $offline = 0;
$status = $conn->query("SELECT status, COUNT(*) AS count FROM cameras GROUP BY status");
while ($row = $status->fetch_assoc()) {
    if (strtoupper($row['status']) === 'ONLINE') $online = (int)$row['count'];
    else $offline = (int)$row['count'];
}
$totalCam = $online + $offline;

/* --- Log 24 jam terakhir --- */
$data = array_fill(0, 24, 0);
$res = $conn->query("
    SELECT HOUR(created_at) AS h, COUNT(*) AS c
    FROM logs
    WHERE created_at >= NOW() - INTERVAL 1 DAY
    GROUP BY HOUR(created_at)
");
while ($row = $res->fetch_assoc()) {
    $data[(int)$row['h']] = (int)$row['c'];
}

/* MENGAMBIL SEKO CSS  */
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Dashboard Kamera</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="style.css" rel="stylesheet">

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
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
        <li class="nav-item"><a class="nav-link active" href="dashboard.php">📊 Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="logs.php">📜 Log</a></li>
      </ul>
      <a href="index.php" class="btn btn-light btn-sm">← Kembali</a>
    </div>
  </div>
</nav>

<!-- CONTENT -->
<main class="container py-4">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
      <h2 class="h4 fw-bold mb-1">📊 Dashboard Monitoring</h2>
      <div class="text-muted">Ringkasan status kamera & aktivitas 24 jam terakhir</div>
    </div>
    <div class="d-flex gap-2">
      <span class="stat-pill pill-online">ONLINE: <?= $online ?></span>
      <span class="stat-pill pill-offline">OFFLINE: <?= $offline ?></span>
      <span class="stat-pill pill-total">TOTAL: <?= $totalCam ?></span>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card card-modern p-3 p-md-4 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h3 class="h6 fw-semibold m-0">Status Kamera</h3>
        </div>
        <div class="chart-wrap" style="height:320px">
          <canvas id="statusChart"></canvas>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card card-modern p-3 p-md-4 h-100">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h3 class="h6 fw-semibold m-0">Log Streaming per Jam (24 Jam)</h3>
          <small class="text-muted">Waktu server</small>
        </div>
        <div class="chart-wrap" style="height:320px">
          <canvas id="logChart"></canvas>
        </div>
      </div>
    </div>
  </div>
</main>

<footer>
  &copy; <?= date('Y') ?> Live CCTV by HNG Group
</footer>

<script>
  const online = <?= $online ?>;
  const offline = <?= $offline ?>;
  const logs24 = [<?= implode(',', $data) ?>];

  const hourLabels = Array.from({length:24}, (_,i)=> String(i).padStart(2,'0') + ":00");

  const cBlue='#5AAEFF', cGreen='#34D399', cPink='#FB7185', cGray='#e5e7eb';

  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: { labels:['ONLINE','OFFLINE'], datasets:[{ data:[online,offline], backgroundColor:[cGreen,cPink], borderColor:'#fff', borderWidth:2, hoverOffset:6 }] },
    options: { plugins:{ legend:{ position:'bottom', labels:{ boxWidth:14, usePointStyle:true } } }, cutout:'58%', maintainAspectRatio:false }
  });

  new Chart(document.getElementById('logChart'), {
    type: 'bar',
    data: { labels:hourLabels, datasets:[{ label:'Jumlah log', data:logs24, backgroundColor:cBlue, borderRadius:8, maxBarThickness:22 }] },
    options: {
      scales:{ x:{ grid:{ display:false }, ticks:{ maxRotation:0, autoSkip:true } }, y:{ beginAtZero:true, grid:{ color:cGray }, ticks:{ stepSize:1 } } },
      plugins:{ legend:{ display:false } },
      maintainAspectRatio:false
    }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
