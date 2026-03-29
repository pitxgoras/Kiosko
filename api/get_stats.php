<?php
session_start();
require_once '../config/database.php';

$filter = $_GET['filter'] ?? 'day';
$conn = getConnection();

$dates = [];
$sales = [];

if ($filter == 'day') {
    for($i = 0; $i < 24; $i++) {
        $dates[] = "$i:00";
        $sales[] = 0;
    }
    $result = $conn->query("SELECT HOUR(order_date) as hour, SUM(total) as total FROM orders WHERE DATE(order_date) = CURDATE() AND status = 'completed' GROUP BY HOUR(order_date)");
    while($row = $result->fetch_assoc()) {
        $sales[$row['hour']] = $row['total'];
    }
} elseif ($filter == 'week') {
    for($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dates[] = date('d/m', strtotime($date));
        $result = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE DATE(order_date) = '$date' AND status = 'completed'");
        $sales[] = $result->fetch_assoc()['total'];
    }
} else {
    for($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dates[] = date('d/m', strtotime($date));
        $result = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE DATE(order_date) = '$date' AND status = 'completed'");
        $sales[] = $result->fetch_assoc()['total'];
    }
}

$topProducts = $conn->query("SELECT p.name, SUM(oi.quantity) as total_sold FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY p.id ORDER BY total_sold DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

jsonResponse(true, 'OK', ['labels' => $dates, 'sales' => $sales, 'topProducts' => $topProducts]);
?>