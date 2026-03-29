<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    die('No autorizado');
}

$period = $_GET['period'] ?? 'day';
$conn = getConnection();

if ($period == 'day') {
    $startDate = date('Y-m-d');
    $filename = "reporte_diario_" . date('Y-m-d');
} elseif ($period == 'week') {
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $filename = "reporte_semanal_" . date('Y-m-d');
} else {
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $filename = "reporte_mensual_" . date('Y-m-d');
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE DATE(order_date) >= ? ORDER BY order_date DESC");
$stmt->bind_param("s", $startDate);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID Pedido', 'Fecha', 'Total', 'Estado']);

foreach ($orders as $order) {
    fputcsv($output, [
        $order['order_number'],
        $order['order_date'],
        $order['total'],
        $order['status']
    ]);
}

fclose($output);
?>