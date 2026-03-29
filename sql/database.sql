-- Crear base de datos
CREATE DATABASE IF NOT EXISTS kiosko_db;
USE kiosko_db;

-- Tabla de administradores (SOLO con número de teléfono, sin contraseña)
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar administrador con número de Perú
-- ⚠️ ATENCIÓN: Colocar el número +51932600214 (sin espacios)
INSERT INTO admins (phone) VALUES ('+51932600214');

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-tag',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    brand VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    category_id INT NOT NULL,
    image VARCHAR(255),
    stock INT DEFAULT 0,
    sales INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Tabla de pedidos
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT
);

-- Tabla de items de pedido
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_brand VARCHAR(100),
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Insertar datos iniciales
INSERT INTO categories (name, icon) VALUES 
('Bebidas', 'fa-wine-bottle'),
('Snacks', 'fa-cookie-bite'),
('Dulces', 'fa-candy-cane'),
('Lácteos', 'fa-cheese'),
('Panadería', 'fa-bread-slice');

INSERT INTO products (name, brand, price, category_id, stock, sales) VALUES
('Kero', '300ml', 26.00, 1, 50, 45),
('Bio Aloe', '500ml', 30.00, 1, 30, 32),
('Frugos Fresh', '500ml', 16.50, 1, 100, 78),
('Papas Fritas', '60g', 3.50, 2, 200, 150),
('Gaseosa Coca-Cola', '1.5L', 8.00, 1, 80, 95),
('Chocolate Triángulo', '40g', 2.50, 3, 300, 200),
('Yogurt Gloria', '1L', 6.00, 4, 60, 45),
('Pan Integral', '500g', 4.50, 5, 40, 30);