<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['admin_id'])) jsonResponse(false, 'No autorizado');

$data = json_decode(file_get_contents('php://input'), true);
$conn = getConnection();
$stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
$stmt->bind_param("s", $data['name']);
$stmt->execute();
jsonResponse(true, 'Categoría agregada');
?>