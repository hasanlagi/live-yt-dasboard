<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
$res = $conn->query("SELECT * FROM cameras WHERE status='OFFLINE'");
while ($row = $res->fetch_assoc()) {
    $id = $row['id'];
    $cmd = "php /var/www/html/live/stream.php $id > /dev/null 2>&1 &";
    exec($cmd);
    $conn->query("UPDATE cameras SET status='ONLINE' WHERE id=$id");
}
?>