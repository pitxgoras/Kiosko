<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['admin_id'])) jsonResponse(false, 'No autorizado');

$data = json_decode(file_get_contents('php://input'), true);
$conn = getConnection();
$stmt = $conn->prepare("INSERT INTO products (name, brand, price, category_id, stock) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssdii", $data['name'], $data['brand'], $data['price'], $data['category_id'], $data['stock']);
$stmt->execute();
jsonResponse(true, 'Producto agregado');
?>