<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['admin_id'])) jsonResponse(false, 'No autorizado');

$data = json_decode(file_get_contents('php://input'), true);
$conn = getConnection();
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $data['id']);
$stmt->execute();
jsonResponse(true, 'Producto eliminado');
?>