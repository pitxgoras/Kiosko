<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    jsonResponse(false, 'No autorizado');
}

$data = json_decode(file_get_contents('php://input'), true);
$conn = getConnection();

$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $data['status'], $data['id']);
$stmt->execute();

jsonResponse(true, 'Estado actualizado');
?>