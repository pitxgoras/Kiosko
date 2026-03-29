<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kiosko_db');

// ⚠️ NÚMERO DEL ADMINISTRADOR (OCULTO PARA USUARIOS)
// Colocar aquí el número: +51932600214
define('ADMIN_PHONE', '+51932600214');

// Crear conexión
function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Error de conexión: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Error de base de datos: " . $e->getMessage());
    }
}

// Verificar si el número es el administrador
function verifyAdmin($phone) {
    return $phone === ADMIN_PHONE;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}
?>