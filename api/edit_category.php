<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['admin_id'])) jsonResponse(false, 'No autorizado');

$data = json_decode(file_get_contents('php://input'), true);
$conn = getConnection();
$stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
$stmt->bind_param("si", $data['name'], $data['id']);
$stmt->execute();
jsonResponse(true, 'Categoría actualizada');
?>