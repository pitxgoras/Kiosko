<?php
require_once 'config/database.php';
$conn = getConnection();

// Obtener categorías
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
// Obtener productos
$products = $conn->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.stock > 0 ORDER BY p.name");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Kiosko - Tu Tienda Online</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            overflow-x: hidden;
        }

        .main-header {
            background: linear-gradient(135deg, #2c3e50, #1a2632);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-area i {
            font-size: 1.75rem;
            color: #f1c40f;
        }

        .logo-area h1 {
            font-size: 1.5rem;
            color: white;
        }

        .btn-outline {
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 2rem;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
        }

        .btn-outline:hover {
            background: rgba(255,255,255,0.25);
        }

        .main-content {
            display: flex;
            flex-direction: column;
            padding: 1rem;
            gap: 1rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        @media (min-width: 768px) {
            .main-content {
                flex-direction: row;
                align-items: flex-start;
            }
            .catalog-section {
                flex: 2;
            }
            .cart-section {
                flex: 1;
                position: sticky;
                top: 80px;
                max-height: calc(100vh - 100px);
                overflow-y: auto;
            }
        }

        .catalog-section, .cart-section {
            background: white;
            border-radius: 1.5rem;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .search-box {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #e0e0e0;
            border-radius: 2rem;
            font-size: 0.9rem;
        }

        .categories-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ecf0f1;
        }

        .category-tab {
            background: #f8f9fa;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: 2rem;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
        }

        .category-tab.active {
            background: #2c3e50;
            color: white;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        .product-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .product-name {
            font-weight: 700;
            font-size: 1rem;
        }

        .product-brand {
            font-size: 0.75rem;
            color: #7f8c8d;
            margin: 0.25rem 0;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #27ae60;
            margin: 0.5rem 0;
        }

        .add-to-cart-btn {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 2rem;
            width: 100%;
            cursor: pointer;
            transition: 0.2s;
        }

        .add-to-cart-btn:hover {
            background: #1e2b36;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #ecf0f1;
        }

        .btn-clear {
            background: none;
            border: none;
            color: #e74c3c;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .cart-items {
            max-height: 300px;
            overflow-y: auto;
        }

        @media (min-width: 768px) {
            .cart-items {
                max-height: 400px;
            }
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 500;
            font-size: 0.875rem;
        }

        .cart-item-price {
            font-size: 0.75rem;
            color: #7f8c8d;
        }

        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cart-item-controls button {
            width: 28px;
            height: 28px;
            border: none;
            background: #ecf0f1;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: bold;
        }

        .cart-footer {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #ecf0f1;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .btn-whatsapp {
            width: 100%;
            padding: 0.75rem;
            background: #25D366;
            color: white;
            border: none;
            border-radius: 2rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-whatsapp:hover {
            background: #20b859;
        }

        .empty-cart {
            text-align: center;
            padding: 2rem;
            color: #95a5a6;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 1.5rem;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }

        .modal-content input {
            width: 100%;
            padding: 0.75rem;
            margin: 1rem 0;
            border: 1px solid #ddd;
            border-radius: 0.75rem;
            font-size: 1rem;
        }

        .close {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .btn-primary {
            background: #27ae60;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }

        .error-msg {
            color: #e74c3c;
            font-size: 0.75rem;
            margin-top: 0.5rem;
        }

        .notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #27ae60;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            z-index: 2000;
            transition: transform 0.3s;
            white-space: nowrap;
        }

        .notification.show {
            transform: translateX(-50%) translateY(0);
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="logo-area">
            <i class="fas fa-store"></i>
            <h1>Kiosko</h1>
        </div>
        <button id="adminAccessBtn" class="btn-outline">
            <i class="fas fa-user-shield"></i> Admin
        </button>
    </header>

    <!-- Modal Login Admin - SOLO NÚMERO, sin contraseña -->
    <div id="adminLoginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <i class="fas fa-store" style="font-size: 48px; color: #2c3e50;"></i>
            <h3>Acceso Administrador</h3>
            <p>Ingresa tu número de WhatsApp registrado</p>
            <input type="tel" id="adminPhoneInput" placeholder="Número de WhatsApp" autocomplete="off">
            <button id="verifyAdminBtn" class="btn-primary">Verificar</button>
            <p id="adminLoginError" class="error-msg"></p>
            <small style="display: block; margin-top: 10px; color: #7f8c8d;">* Solo el número autorizado tiene acceso</small>
        </div>
    </div>

    <div class="main-content">
        <!-- Catálogo -->
        <div class="catalog-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Buscar productos...">
            </div>
            
            <div class="categories-tabs" id="categoriesTabs">
                <button class="category-tab active" data-id="all">Todos</button>
                <?php while($cat = $categories->fetch_assoc()): ?>
                    <button class="category-tab" data-id="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></button>
                <?php endwhile; ?>
            </div>
            
            <div class="products-grid" id="productsGrid">
                <?php while($prod = $products->fetch_assoc()): ?>
                    <div class="product-card" data-id="<?= $prod['id'] ?>" data-category="<?= $prod['category_id'] ?>">
                        <div class="product-name"><?= htmlspecialchars($prod['name']) ?></div>
                        <div class="product-brand"><?= htmlspecialchars($prod['brand'] ?? '') ?></div>
                        <div class="product-price">S/ <?= number_format($prod['price'], 2) ?></div>
                        <button class="add-to-cart-btn" data-id="<?= $prod['id'] ?>" data-name="<?= htmlspecialchars($prod['name']) ?>" data-brand="<?= htmlspecialchars($prod['brand'] ?? '') ?>" data-price="<?= $prod['price'] ?>">
                            <i class="fas fa-plus"></i> Añadir
                        </button>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Carrito -->
        <div class="cart-section">
            <div class="cart-header">
                <h3><i class="fas fa-shopping-cart"></i> Tu Pedido</h3>
                <button id="clearCartBtn" class="btn-clear">Vaciar</button>
            </div>
            <div class="cart-items" id="cartItems">
                <div class="empty-cart">No hay productos en el carrito</div>
            </div>
            <div class="cart-footer">
                <div class="total-row">
                    <span>TOTAL:</span>
                    <span class="total-amount" id="totalAmount">S/ 0.00</span>
                </div>
                <button id="shareWhatsAppBtn" class="btn-whatsapp">
                    <i class="fab fa-whatsapp"></i> Compartir Pedido
                </button>
            </div>
        </div>
    </div>

    <div id="notification" class="notification"></div>

    <script>
        let cart = [];

        function showNotification(message, type = 'success') {
            const notif = document.getElementById('notification');
            notif.textContent = message;
            notif.style.background = type === 'success' ? '#27ae60' : '#e74c3c';
            notif.classList.add('show');
            setTimeout(() => notif.classList.remove('show'), 3000);
        }

        function addToCart(id, name, brand, price) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                existing.quantity++;
            } else {
                cart.push({ id, name, brand, price: parseFloat(price), quantity: 1 });
            }
            renderCart();
            showNotification(`${name} añadido al carrito`);
        }

        function renderCart() {
            const container = document.getElementById('cartItems');
            const totalSpan = document.getElementById('totalAmount');
            
            if (cart.length === 0) {
                container.innerHTML = '<div class="empty-cart">No hay productos en el carrito</div>';
                totalSpan.textContent = 'S/ 0.00';
                return;
            }
            
            let total = 0;
            container.innerHTML = cart.map(item => {
                const subtotal = item.price * item.quantity;
                total += subtotal;
                return `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.name} ${item.brand ? `(${item.brand})` : ''}</div>
                            <div class="cart-item-price">S/ ${item.price.toFixed(2)} c/u</div>
                        </div>
                        <div class="cart-item-controls">
                            <button class="cart-decrease" data-id="${item.id}">-</button>
                            <span>${item.quantity}</span>
                            <button class="cart-increase" data-id="${item.id}">+</button>
                            <button class="cart-remove" data-id="${item.id}"><i class="fas fa-trash"></i></button>
                        </div>
                        <div>S/ ${subtotal.toFixed(2)}</div>
                    </div>
                `;
            }).join('');
            
            totalSpan.textContent = `S/ ${total.toFixed(2)}`;
            
            document.querySelectorAll('.cart-decrease').forEach(btn => {
                btn.onclick = () => updateQuantity(parseInt(btn.dataset.id), -1);
            });
            document.querySelectorAll('.cart-increase').forEach(btn => {
                btn.onclick = () => updateQuantity(parseInt(btn.dataset.id), 1);
            });
            document.querySelectorAll('.cart-remove').forEach(btn => {
                btn.onclick = () => removeFromCart(parseInt(btn.dataset.id));
            });
        }

        function updateQuantity(id, delta) {
            const item = cart.find(i => i.id === id);
            if (item) {
                item.quantity += delta;
                if (item.quantity <= 0) {
                    cart = cart.filter(i => i.id !== id);
                }
                renderCart();
            }
        }

        function removeFromCart(id) {
            cart = cart.filter(i => i.id !== id);
            renderCart();
        }

        async function sendOrder() {
            if (cart.length === 0) {
                showNotification('El carrito está vacío', 'error');
                return;
            }
            
            const now = new Date();
            const fecha = now.toLocaleDateString('es-PE');
            const hora = now.toLocaleTimeString('es-PE');
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            let orderText = `🍹 *NUEVO PEDIDO - KIOSKO* 🍹\n\n`;
            orderText += `📅 *Fecha:* ${fecha}\n`;
            orderText += `⏰ *Hora:* ${hora}\n\n`;
            orderText += `*PRODUCTOS:*\n`;
            
            cart.forEach(item => {
                orderText += `📦 ${item.quantity} x ${item.name} ${item.brand ? `(${item.brand})` : ''} - S/ ${item.price.toFixed(2)} = S/ ${(item.price * item.quantity).toFixed(2)}\n`;
            });
            
            orderText += `\n💰 *TOTAL: S/ ${total.toFixed(2)}*\n`;
            orderText += `\n📱 Pedido generado desde Kiosko App.`;
            
            try {
                const response = await fetch('api/create_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ items: cart, total, fecha, hora })
                });
                
                const result = await response.json();
                if (result.success) {
                    const whatsappUrl = `https://wa.me/51932600214?text=${encodeURIComponent(orderText)}`;
                    window.open(whatsappUrl, '_blank');
                    cart = [];
                    renderCart();
                    showNotification('Pedido enviado correctamente');
                } else {
                    showNotification('Error al guardar el pedido', 'error');
                }
            } catch (error) {
                showNotification('Error de conexión', 'error');
            }
        }

        // Event Listeners
        document.getElementById('shareWhatsAppBtn')?.addEventListener('click', sendOrder);
        document.getElementById('clearCartBtn')?.addEventListener('click', () => {
            cart = [];
            renderCart();
        });

        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                addToCart(
                    btn.dataset.id,
                    btn.dataset.name,
                    btn.dataset.brand,
                    btn.dataset.price
                );
            });
        });

        // Búsqueda
        document.getElementById('searchInput')?.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(card => {
                const name = card.querySelector('.product-name').textContent.toLowerCase();
                const brand = card.querySelector('.product-brand')?.textContent.toLowerCase() || '';
                card.style.display = name.includes(searchTerm) || brand.includes(searchTerm) ? 'block' : 'none';
            });
        });

        // Filtro por categoría
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const categoryId = tab.dataset.id;
                
                document.querySelectorAll('.product-card').forEach(card => {
                    if (categoryId === 'all') {
                        card.style.display = 'block';
                    } else {
                        card.style.display = card.dataset.category === categoryId ? 'block' : 'none';
                    }
                });
            });
        });

        // Admin login - SOLO NÚMERO
        document.getElementById('adminAccessBtn')?.addEventListener('click', () => {
            document.getElementById('adminLoginModal').style.display = 'flex';
        });

        document.getElementById('verifyAdminBtn')?.addEventListener('click', async () => {
            const phone = document.getElementById('adminPhoneInput').value.trim();
            
            const btn = document.getElementById('verifyAdminBtn');
            const originalText = btn.textContent;
            btn.textContent = 'Verificando...';
            btn.disabled = true;
            
            const response = await fetch('api/admin_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ phone })
            });
            
            const result = await response.json();
            
            btn.textContent = originalText;
            btn.disabled = false;
            
            if (result.success) {
                window.location.href = 'admin/dashboard.php';
            } else {
                document.getElementById('adminLoginError').textContent = 'Número no autorizado';
            }
        });

        document.querySelectorAll('.close').forEach(btn => {
            btn.onclick = () => {
                document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
            };
        });
    </script>
</body>
</html>