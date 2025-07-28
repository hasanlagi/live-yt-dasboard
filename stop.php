<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
$id = $_GET['id'];
exec("pkill -f 'php stream.php $id'");
$conn->query("UPDATE cameras SET status='OFFLINE' WHERE id=$id");
$conn->query("INSERT INTO logs (camera_id, action) VALUES ($id, 'STOP')");
header("Location: index.php");
?>