# 🏪 Kiosko - Sistema de Punto de Venta

Sistema completo de punto de venta para kioskos con panel de administración, carrito de compras y gestión de pedidos.

## ✨ Características

### 👤 Para Clientes
- 🛍️ Catálogo de productos por categorías
- 🔍 Búsqueda en tiempo real
- 🛒 Carrito de compras interactivo
- 📱 Compartir pedido por WhatsApp (fecha, hora, productos, total)

### 👨‍💼 Para Administradores
- 🔐 Login SOLO con número de WhatsApp (sin contraseña)
- 📊 Dashboard con estadísticas en tiempo real (día/semana/mes)
- 📈 Gráficos de ventas y productos más vendidos
- 📦 CRUD completo de categorías y productos
- ✅ Gestión de pedidos (Pendiente / Completado / Rechazado)
- 📥 Exportar reportes a CSV (día/semana/mes)

## 🛠️ Tecnologías

| Tecnología | Uso |
|------------|-----|
| PHP 7.4+ | Backend y API REST |
| MySQL | Base de datos |
| HTML5 + CSS3 | Frontend responsive |
| JavaScript | Interactividad |
| Chart.js | Gráficos del dashboard |
| Font Awesome | Iconos |

``` bash

## 📋 Estructura
Kiosko/
├── index.php # Tienda para clientes
├── admin/
│ └── dashboard.php # Panel administrador
├── api/ # API REST
│ ├── admin_login.php
│ ├── create_order.php
│ ├── get_stats.php
│ ├── get_orders.php
│ ├── update_order_status.php
│ ├── export_report.php
│ ├── get_categories.php
│ ├── get_products.php
│ ├── add_category.php
│ ├── edit_category.php
│ ├── delete_category.php
│ ├── add_product.php
│ ├── edit_product.php
│ ├── delete_product.php
│ ├── get_product.php
│ └── logout.php
├── config/
│ └── database.php # Configuración BD
├── css/
│ └── styles.css # Estilos
├── sql/
│ └── database.sql # Esquema BD
└── README.md
```

## 🔐 Acceso Administrador

| Campo | Valor |
|-------|-------|
| 📞 Número | `+51 9-- --- ---` |

> ⚠️ El número está oculto en el código. Solo el administrador lo conoce.

## 🚀 Instalación

### Requisitos
- XAMPP / WAMP / MAMP
- PHP 7.4+
- MySQL

### Pasos

1. **Clonar repositorio**
```bash
git clone https://github.com/pitxgoras/Kiosko.git
```
2. **Mover a htdocs**
```bash
mv Kiosko C:/xampp/htdocs/
```


---

## 📁 **Archivo: `api/get_product.php`** (ya está arriba, pero confirmo)

```php
<?php
session_start();
require_once '../config/database.php';

$id = $_GET['id'] ?? 0;
$conn = getConnection();

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($product);
?>