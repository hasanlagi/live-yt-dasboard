#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * CLI: php stream.php [camera_id]
 */

date_default_timezone_set('Asia/Jakarta');
set_time_limit(0);

/* ====== KONFIGURASI ====== */
const FFMPEG_BIN     = '/usr/bin/ffmpeg';
const LOG_DIR        = __DIR__ . '/logs';
const LOCK_DIR       = '/tmp';
const OFFLINE_IMAGE  = __DIR__ . '/offline.jpg';
const MAX_BACKOFF    = 30;
const INIT_BACKOFF   = 5;
const TCP_TIMEOUT_S  = 30;
const TCP_TIMEOUT2_S = 60;

/* ====== UTIL ====== */
function log_stream(string $msg, int $id): void {
    if (!is_dir(LOG_DIR)) @mkdir(LOG_DIR, 0775, true);
    $line = date('Y-m-d H:i:s') . " [cam:$id] $msg\n";
    @file_put_contents(LOG_DIR . "/camera_{$id}.log", $line, FILE_APPEND);
    fwrite(STDOUT, $line);
}
function ensure_even(int $n, int $fallback = 720): int {
    return ($n < 2) ? $fallback : (($n % 2 === 0) ? $n : $n - 1);
}
function jitter(int $max = 3): int {
    return random_int(0, max(0, $max));
}
function parse_host_from_rtmp(string $rtmp): ?string {
    $p = parse_url($rtmp);
    return $p['host'] ?? null;
}
function tcp_check(string $host, int $port = 1935, int $timeout = 5): bool {
    $ip = @gethostbyname($host);
    if (!$ip || $ip === $host) return false;
    $conn = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($conn) { fclose($conn); return true; }
    return false;
}
function linux_pid_running(int $pid): bool {
    return ($pid > 0 && @file_exists("/proc/{$pid}"));
}
function kill_children(int $pid): void {
    @exec('pkill -TERM -P ' . (int)$pid . ' 2>/dev/null');
}
function build_lock_path(int $id): string {
    return rtrim(LOCK_DIR, '/') . "/stream_cam_{$id}.lock";
}

/* ====== ARG ====== */
$id = isset($argv[1]) ? (int)$argv[1] : 0;
if ($id <= 0) {
    fwrite(STDERR, "Usage: php stream.php [camera_id]\n");
    exit(2);
}

/* ====== SIGNAL ====== */
$running = true;
if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, fn() => $running = false);
    pcntl_signal(SIGINT, fn() => $running = false);
}

/* ====== LOCKFILE ====== */
$lockPath = build_lock_path($id);
if (file_exists($lockPath)) {
    $old = (int)trim(@file_get_contents($lockPath));
    if ($old > 0 && linux_pid_running($old)) {
        fwrite(STDERR, "Another stream.php for cam:$id is running (PID=$old)\n");
        exit(1);
    }
}
@file_put_contents($lockPath, (string)getmypid());

/* ====== CEK FFMPEG ====== */
if (!is_file(FFMPEG_BIN) || !is_executable(FFMPEG_BIN)) {
    log_stream("❌ ERROR: ffmpeg tidak ditemukan/eksekutabel di ".FFMPEG_BIN, $id);
    @unlink($lockPath);
    exit(1);
}
if (!is_file(OFFLINE_IMAGE)) {
    @mkdir(dirname(OFFLINE_IMAGE), 0775, true);
    @exec(
        escapeshellarg(FFMPEG_BIN) . ' -hide_banner -loglevel error' .
        ' -f lavfi -i "color=c=black:size=1280x720:rate=1"' .
        ' -frames:v 1 ' . escapeshellarg(OFFLINE_IMAGE)
    );
}

/* ====== DB ====== */
require __DIR__ . '/config.php';
$conn = @new mysqli($host ?? 'localhost', $user ?? 'root', $pass ?? '', $db ?? '');
if ($conn->connect_errno) {
    log_stream("❌ DB error: ".$conn->connect_error, $id);
    @unlink($lockPath);
    exit(1);
}
$conn->set_charset('utf8mb4');

/* ====== LOAD KAMERA ====== */
$stmt = $conn->prepare("SELECT id,name,rtsp,rtmp_server,stream_key,mode,resolution FROM cameras WHERE id=?");
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    log_stream("❌ DB query gagal saat ambil kamera ID=$id", $id);
    @unlink($lockPath);
    exit(1);
}
$cam = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cam) {
    log_stream("❌ Kamera ID=$id tidak ditemukan.", $id);
    @unlink($lockPath);
    exit(1);
}

$name         = (string)($cam['name'] ?? "cam-$id");
$rtsp         = trim((string)($cam['rtsp'] ?? ''));
$rtmp_server  = trim((string)($cam['rtmp_server'] ?? ''));
$stream_key   = trim((string)($cam['stream_key'] ?? ''));
$mode         = strtolower((string)($cam['mode'] ?? 'libx264'));
$res          = (string)($cam['resolution'] ?? '1280x720');

$rtmp = rtrim($rtmp_server, '/');
if ($stream_key !== '') $rtmp .= '/'.$stream_key;

$w = 1280; $h = 720;
if (preg_match('/^\s*(\d+)\s*x\s*(\d+)\s*$/i', $res, $m)) {
    $w = ensure_even((int)$m[1], 1280);
    $h = ensure_even((int)$m[2], 720);
}

/* ====== STATUS HELPER ====== */
$setStatus = function(string $status) use ($conn, $id) {
    $status = strtoupper($status) === 'ONLINE' ? 'ONLINE' : 'OFFLINE';
    $stmt = $conn->prepare("UPDATE cameras SET status=? WHERE id=?");
    if ($stmt) {
        $stmt->bind_param('si', $status, $id);
        @$stmt->execute();
        $stmt->close();
    }
};

/* ====== COMMAND BUILDER ====== */
function cmd_rtsp_to_rtmp_copy(string $rtsp, string $rtmp, int $low, int $high): string {
    $stimeout = max(min($high, 60), $low) * 1000000;
    return implode(' ', [
        escapeshellarg(FFMPEG_BIN),
        '-hide_banner -loglevel warning -fflags nobuffer',
        '-rtsp_transport tcp -rtsp_flags prefer_tcp',
        "-stimeout {$stimeout}",
        '-thread_queue_size 2048 -i ' . escapeshellarg($rtsp),
        '-thread_queue_size 256 -f lavfi -i anullsrc=channel_layout=mono:sample_rate=44100',
        '-map 0:v:0 -map 1:a:0',
        '-use_wallclock_as_timestamps 1 -vsync 1 -flush_packets 1',
        '-c:v copy -c:a aac -ar 44100 -b:a 128k',
        '-f flv ' . escapeshellarg($rtmp)
    ]);
}
function cmd_rtsp_to_rtmp_x264(string $rtsp, string $rtmp, int $w, int $h, int $low, int $high): string {
    $stimeout = max(min($high, 60), $low) * 1000000;
    $fps = 25; $gop = max(2, $fps);
    $br = ($w >= 1920 || $h >= 1080) ? 4000 : (($w >= 1280 || $h >= 720) ? 2500 : 1200);
    $buf = $br * 2;
    return implode(' ', [
        escapeshellarg(FFMPEG_BIN),
        '-hide_banner -loglevel warning -fflags nobuffer',
        '-rtsp_transport tcp -rtsp_flags prefer_tcp',
        "-stimeout {$stimeout}",
        '-thread_queue_size 2048 -i ' . escapeshellarg($rtsp),
        '-thread_queue_size 256 -f lavfi -i anullsrc=channel_layout=mono:sample_rate=44100',
        '-map 0:v:0 -map 1:a:0',
        '-use_wallclock_as_timestamps 1 -vsync 1 -flush_packets 1',
        "-vf scale={$w}:{$h}:flags=bicubic",
        "-c:v libx264 -preset veryfast -tune zerolatency -profile:v main -pix_fmt yuv420p",
        "-g {$gop} -keyint_min {$gop} -sc_threshold 0",
        "-b:v {$br}k -maxrate {$br}k -bufsize {$buf}k",
        "-c:a aac -ar 44100 -b:a 128k",
        '-f flv ' . escapeshellarg($rtmp)
    ]);
}
function cmd_offline_image_to_rtmp(string $img, string $rtmp, int $w, int $h): string {
    return implode(' ', [
        escapeshellarg(FFMPEG_BIN),
        '-hide_banner -loglevel warning -loop 1 -framerate 2 -re',
        '-i ' . escapeshellarg($img),
        '-f lavfi -i anullsrc=channel_layout=mono:sample_rate=44100',
        '-map 0:v:0 -map 1:a:0',
        "-vf scale={$w}:{$h}:flags=bicubic",
        "-c:v libx264 -preset veryfast -tune zerolatency -profile:v main -pix_fmt yuv420p",
        "-g 2 -keyint_min 2 -sc_threshold 0",
        "-b:v 800k -maxrate 800k -bufsize 1600k",
        "-c:a aac -ar 44100 -b:a 96k",
        '-f flv ' . escapeshellarg($rtmp)
    ]);
}

/* ====== HEALTH CHECK ====== */
$rtmpHost = parse_host_from_rtmp($rtmp);
if (!$rtmpHost) {
    log_stream("❌ RTMP URL tidak valid: '$rtmp'", $id);
    @unlink($lockPath);
    exit(1);
}
if (!tcp_check($rtmpHost, 1935, 5)) {
    log_stream("⚠ RTMP host '$rtmpHost:1935' belum reachable.", $id);
}

/* ====== MAIN LOOP ====== */
$backoff = INIT_BACKOFF;
log_stream("📡 Start streaming cam:$id ($name), mode=$mode, res={$w}x{$h}", $id);

while ($running) {
    $setStatus('ONLINE');
    $cmd = ($mode === 'copy')
        ? cmd_rtsp_to_rtmp_copy($rtsp, $rtmp, TCP_TIMEOUT_S, TCP_TIMEOUT2_S)
        : cmd_rtsp_to_rtmp_x264($rtsp, $rtmp, $w, $h, TCP_TIMEOUT_S, TCP_TIMEOUT2_S);

    log_stream("▶ FFmpeg start (mode=$mode)", $id);
    $start = microtime(true);
    exec($cmd, $out, $code);
    $elapsed = (int)round(microtime(true) - $start);

    if ($code !== 0 && $elapsed < 8) {
        log_stream("⚠ RTSP gagal init (code=$code, t={$elapsed}s). Switch ke OFFLINE IMAGE…", $id);
        $setStatus('ONLINE');
        exec(cmd_offline_image_to_rtmp(OFFLINE_IMAGE, $rtmp, $w, $h), $o2, $c2);
        log_stream("ℹ Offline image stop (code=$c2).", $id);
    }

    $setStatus('OFFLINE');
    if (!$running) break;

    $sleep = min(MAX_BACKOFF, $backoff) + jitter(3);
    log_stream("⏳ Retry in {$sleep}s (last code={$code}, lasted={$elapsed}s)…", $id);
    for ($i = 0; $i < $sleep && $running; $i++) sleep(1);
    $backoff = min(MAX_BACKOFF, $backoff + 5);
}

/* ====== CLEANUP ====== */
log_stream("🛑 Stop requested, cleaning up…", $id);
kill_children(getmypid());
@unlink($lockPath);
$setStatus('OFFLINE');
log_stream("✅ Clean exit.", $id);
exit(0);
