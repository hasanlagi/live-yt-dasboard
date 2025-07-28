<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
$id = $_GET['id'];
$cam = $conn->query("SELECT * FROM cameras WHERE id=$id")->fetch_assoc();
$cmd = "php stream.php {$cam['id']} > /dev/null 2>&1 &";
exec($cmd);
$conn->query("UPDATE cameras SET status='ONLINE' WHERE id=$id");
$conn->query("INSERT INTO logs (camera_id, action) VALUES ($id, 'START')");
header("Location: index.php");
?>