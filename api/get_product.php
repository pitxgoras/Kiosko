<?php
session_start();
require_once '../config/database.php';

$id = $_GET['id'] ?? 0;
$conn = getConnection();

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

jsonResponse(true, 'OK', $product);
?>