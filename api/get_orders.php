<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    jsonResponse(false, 'No autorizado');
}

$status = $_GET['status'] ?? 'all';
$conn = getConnection();

$sql = "SELECT o.*, COUNT(oi.id) as items_count 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id";
if ($status != 'all') {
    $sql .= " WHERE o.status = '$status'";
}
$sql .= " GROUP BY o.id ORDER BY o.order_date DESC";

$result = $conn->query($sql);
$orders = $result->fetch_all(MYSQLI_ASSOC);

jsonResponse(true, 'OK', $orders);
?>