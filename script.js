// ==================== DATOS ====================
let categories = [];
let products = [];
let cart = [];

let activeCategoryId = null;
let searchTerm = "";

// Número del administrador (oculto en el código)
const ADMIN_PHONE = "+51932600214";

// ==================== INICIALIZACIÓN ====================
function init() {
    loadData();
    setupEventListeners();
    renderCategories();
    renderProducts();
    renderCart();
}

function loadData() {
    const storedCats = localStorage.getItem("kiosko_categories");
    const storedProds = localStorage.getItem("kiosko_products");
    
    categories = storedCats ? JSON.parse(storedCats) : [
        { id: "cat1", name: "Bebidas" },
        { id: "cat2", name: "Snacks" },
        { id: "cat3", name: "Dulces" }
    ];
    
    products = storedProds ? JSON.parse(storedProds) : [
        { id: "p1", name: "Kero", brand: "300ml", price: 26.00, categoryId: "cat1" },
        { id: "p2", name: "Bio Aloe", brand: "500ml", price: 30.00, categoryId: "cat1" },
        { id: "p3", name: "Frugos Fresh", brand: "500ml", price: 16.50, categoryId: "cat1" }
    ];
    
    if (categories.length > 0 && !activeCategoryId) {
        activeCategoryId = categories[0].id;
    }
}

function saveData() {
    localStorage.setItem("kiosko_categories", JSON.stringify(categories));
    localStorage.setItem("kiosko_products", JSON.stringify(products));
}

// ==================== RENDERIZADO ====================
function renderCategories() {
    const container = document.getElementById("categoriesTabs");
    if (!container) return;
    
    container.innerHTML = categories.map(cat => `
        <button class="category-tab ${activeCategoryId === cat.id ? 'active' : ''}" data-id="${cat.id}">
            ${cat.name}
        </button>
    `).join('');
    
    document.querySelectorAll('.category-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            activeCategoryId = btn.dataset.id;
            renderCategories();
            renderProducts();
        });
    });
}

function renderProducts() {
    const container = document.getElementById("productsGrid");
    if (!container) return;
    
    let filtered = products.filter(p => p.categoryId === activeCategoryId);
    
    if (searchTerm.trim()) {
        filtered = filtered.filter(p => 
            p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            (p.brand && p.brand.toLowerCase().includes(searchTerm.toLowerCase()))
        );
    }
    
    if (filtered.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:40px;">No hay productos en esta categoría</div>';
        return;
    }
    
    container.innerHTML = filtered.map(prod => `
        <div class="product-card">
            <div class="product-name">${prod.name}</div>
            <div class="product-brand">${prod.brand || ''}</div>
            <div class="product-price">S/ ${prod.price.toFixed(2)}</div>
            <button class="add-to-cart-btn" data-id="${prod.id}">
                <i class="fas fa-plus"></i> Añadir
            </button>
        </div>
    `).join('');
    
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            addToCart(btn.dataset.id);
        });
    });
}

// ==================== CARRITO ====================
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    const existing = cart.find(item => item.productId === productId);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({ productId, quantity: 1, product });
    }
    
    renderCart();
    showNotification(`${product.name} añadido al carrito`, "success");
}

function renderCart() {
    const container = document.getElementById("cartItems");
    const totalSpan = document.getElementById("totalAmount");
    
    if (!container) return;
    
    if (cart.length === 0) {
        container.innerHTML = '<div class="empty-cart">No hay productos en el carrito</div>';
        totalSpan.textContent = "S/ 0.00";
        return;
    }
    
    let total = 0;
    container.innerHTML = cart.map(item => {
        const subtotal = item.product.price * item.quantity;
        total += subtotal;
        return `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.product.name} ${item.product.brand ? `(${item.product.brand})` : ''}</div>
                    <div class="cart-item-price">S/ ${item.product.price.toFixed(2)} c/u</div>
                </div>
                <div class="cart-item-controls">
                    <button class="cart-decrease" data-id="${item.productId}">-</button>
                    <span>${item.quantity}</span>
                    <button class="cart-increase" data-id="${item.productId}">+</button>
                    <button class="cart-remove" data-id="${item.productId}"><i class="fas fa-trash"></i></button>
                </div>
                <div>S/ ${subtotal.toFixed(2)}</div>
            </div>
        `;
    }).join('');
    
    totalSpan.textContent = `S/ ${total.toFixed(2)}`;
    
    // Event listeners
    document.querySelectorAll('.cart-decrease').forEach(btn => {
        btn.onclick = () => updateQuantity(btn.dataset.id, -1);
    });
    document.querySelectorAll('.cart-increase').forEach(btn => {
        btn.onclick = () => updateQuantity(btn.dataset.id, 1);
    });
    document.querySelectorAll('.cart-remove').forEach(btn => {
        btn.onclick = () => removeFromCart(btn.dataset.id);
    });
}

function updateQuantity(productId, delta) {
    const item = cart.find(i => i.productId === productId);
    if (item) {
        item.quantity += delta;
        if (item.quantity <= 0) {
            cart = cart.filter(i => i.productId !== productId);
        }
        renderCart();
    }
}

function removeFromCart(productId) {
    cart = cart.filter(i => i.productId !== productId);
    renderCart();
    showNotification("Producto eliminado", "info");
}

function clearCart() {
    cart = [];
    renderCart();
}

// ==================== COMPARTIR POR WHATSAPP (PDF) ====================
async function shareOrderAsPDF() {
    if (cart.length === 0) {
        showNotification("El carrito está vacío", "error");
        return;
    }
    
    const now = new Date();
    const fecha = now.toLocaleDateString('es-PE');
    const hora = now.toLocaleTimeString('es-PE');
    const total = cart.reduce((sum, item) => sum + (item.product.price * item.quantity), 0);
    
    // Crear contenido del pedido
    let orderText = `🍹 *PEDIDO KIOSKO* 🍹\n\n`;
    orderText += `📅 *Fecha:* ${fecha}\n`;
    orderText += `⏰ *Hora:* ${hora}\n\n`;
    orderText += `*PRODUCTOS:*\n`;
    
    cart.forEach(item => {
        orderText += `📦 ${item.quantity} x ${item.product.name} ${item.product.brand ? `(${item.product.brand})` : ''} - S/ ${item.product.price.toFixed(2)} = S/ ${(item.product.price * item.quantity).toFixed(2)}\n`;
    });
    
    orderText += `\n💰 *TOTAL: S/ ${total.toFixed(2)}*\n`;
    orderText += `\n📱 Pedido generado desde Kiosko App.`;
    
    // Guardar pedido en el historial del admin
    const order = {
        id: "ORD-" + Date.now(),
        date: now.toISOString(),
        fecha: fecha,
        hora: hora,
        items: cart.map(item => ({
            productId: item.productId,
            name: item.product.name,
            brand: item.product.brand,
            quantity: item.quantity,
            price: item.product.price,
            subtotal: item.product.price * item.quantity
        })),
        total: total,
        status: "pending"
    };
    
    // Obtener pedidos existentes y guardar
    const existingOrders = JSON.parse(localStorage.getItem("kiosko_orders") || "[]");
    existingOrders.unshift(order);
    localStorage.setItem("kiosko_orders", JSON.stringify(existingOrders));
    
    // Enviar por WhatsApp
    const whatsappUrl = `https://wa.me/${ADMIN_PHONE}?text=${encodeURIComponent(orderText)}`;
    window.open(whatsappUrl, '_blank');
    
    // Limpiar carrito
    cart = [];
    renderCart();
    
    showNotification("Pedido enviado correctamente", "success");
}

// ==================== NOTIFICACIONES ====================
function showNotification(message, type = "info") {
    const notif = document.getElementById("notification");
    if (!notif) return;
    
    notif.textContent = message;
    notif.style.background = type === "success" ? "#27ae60" : type === "error" ? "#e74c3c" : "#3498db";
    notif.classList.add("show");
    
    setTimeout(() => {
        notif.classList.remove("show");
    }, 3000);
}

// ==================== ADMIN LOGIN (Solo número oculto) ====================
function setupEventListeners() {
    // Login admin
    document.getElementById("adminAccessBtn")?.addEventListener("click", () => {
        document.getElementById("adminLoginModal").style.display = "flex";
    });
    
    document.getElementById("verifyAdminPhoneBtn")?.addEventListener("click", () => {
        const phone = document.getElementById("adminPhoneInput").value.trim();
        // El número está hardcodeado pero no se muestra al usuario
        if (phone === "+51 932 600 214" || phone === "932600214" || phone === "932 600 214") {
            localStorage.setItem("kiosko_admin_auth", "true");
            window.location.href = "admin.html";
        } else {
            document.getElementById("adminLoginError").textContent = "Número no autorizado. Solo el administrador tiene acceso.";
        }
    });
    
    // Cerrar modales
    document.querySelectorAll(".close").forEach(btn => {
        btn.onclick = () => {
            document.querySelectorAll(".modal").forEach(m => m.style.display = "none");
        };
    });
    
    // Vaciar carrito
    document.getElementById("clearCartBtn")?.addEventListener("click", clearCart);
    
    // Compartir pedido
    document.getElementById("shareWhatsAppBtn")?.addEventListener("click", shareOrderAsPDF);
    
    // Búsqueda
    document.getElementById("searchInput")?.addEventListener("input", (e) => {
        searchTerm = e.target.value;
        renderProducts();
    });
    
    // Cerrar modal al hacer clic fuera
    window.onclick = (e) => {
        if (e.target.classList.contains("modal")) {
            e.target.style.display = "none";
        }
    };
}

// Iniciar
init();