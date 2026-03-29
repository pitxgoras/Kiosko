<?php
require_once '../config/database.php';
$conn = getConnection();
$result = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $result->fetch_all(MYSQLI_ASSOC);
jsonResponse(true, 'OK', $categories);
?>