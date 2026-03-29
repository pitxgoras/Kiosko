<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['admin_id'])) jsonResponse(false, 'No autorizado');

$data = json_decode(file_get_contents('php://input'), true);
$conn = getConnection();
$stmt = $conn->prepare("UPDATE products SET name = ?, brand = ?, price = ?, category_id = ?, stock = ? WHERE id = ?");
$stmt->bind_param("ssdiii", $data['name'], $data['brand'], $data['price'], $data['category_id'], $data['stock'], $data['id']);
$stmt->execute();
jsonResponse(true, 'Producto actualizado');
?>