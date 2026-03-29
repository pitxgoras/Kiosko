<?php
session_start();
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$phone = $data['phone'] ?? '';

// Verificar si es el número del administrador
if (verifyAdmin($phone)) {
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_phone'] = $phone;
    jsonResponse(true, 'Login exitoso');
} else {
    jsonResponse(false, 'Número no autorizado');
}
?>