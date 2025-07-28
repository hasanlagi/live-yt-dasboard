<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
$id = $argv[1];
$cam = $conn->query("SELECT * FROM cameras WHERE id=$id")->fetch_assoc();
$rtmp = $cam['rtmp_server'] . '/' . $cam['stream_key'];

if ($cam['mode'] == 'copy') {
    $cmd = "ffmpeg -re -i '{$cam['rtsp']}' -c:v copy -c:a copy -f flv '$rtmp'";
} else {
    list($w, $h) = explode('x', $cam['resolution']);
    $cmd = "ffmpeg -re -i '{$cam['rtsp']}' -vf scale=$w:$h -c:v libx264 -c:a aac -f flv '$rtmp'";
}
exec($cmd);
$conn->query("UPDATE cameras SET status='OFFLINE' WHERE id=$id");
?>