<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$conn = getConnection();

$orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
$total = $data['total'] ?? 0;

// Insertar pedido
$stmt = $conn->prepare("INSERT INTO orders (order_number, total, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("sd", $orderNumber, $total);
$stmt->execute();
$orderId = $conn->insert_id;

// Insertar items
if (isset($data['items']) && is_array($data['items'])) {
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_brand, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $subtotal = $item['price'] * $item['quantity'];
        $stmt->bind_param("iissidd", $orderId, $item['id'], $item['name'], $item['brand'], $item['quantity'], $item['price'], $subtotal);
        $stmt->execute();
        
        // Actualizar ventas del producto
        $conn->query("UPDATE products SET sales = sales + {$item['quantity']} WHERE id = {$item['id']}");
    }
}

jsonResponse(true, 'Pedido guardado', ['order_id' => $orderId, 'order_number' => $orderNumber]);
?>