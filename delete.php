<?php
include 'config.php';
$conn = new mysqli($host, $user, $pass, $db);
$id = $_GET['id'];
$conn->query("DELETE FROM cameras WHERE id=$id");
header("Location: index.php");
?>