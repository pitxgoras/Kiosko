<?php
require_once '../config/database.php';
$conn = getConnection();
$result = $conn->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.name");
$products = $result->fetch_all(MYSQLI_ASSOC);
jsonResponse(true, 'OK', $products);
?>