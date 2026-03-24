// ==================== VERIFICAR AUTENTICACIÓN ====================
function checkAuth() {
    const isAuth = localStorage.getItem("kiosko_admin_auth");
    if (!isAuth) {
        window.location.href = "index.html";
    }
}

// ==================== DATOS ====================
let categories = [];
let products = [];
let orders = [];
let currentTimeFilter = "day";
let salesChart = null;
let topProductsChart = null;

// ==================== INICIALIZACIÓN ====================
function initAdmin() {
    checkAuth();
    loadData();
    setupEventListeners();
    renderStats();
    renderCharts();
    renderAdminPanel();
    renderOrders();
}

function loadData() {
    categories = JSON.parse(localStorage.getItem("kiosko_categories") || "[]");
    products = JSON.parse(localStorage.getItem("kiosko_products") || "[]");
    orders = JSON.parse(localStorage.getItem("kiosko_orders") || "[]");
}

function saveData() {
    localStorage.setItem("kiosko_categories", JSON.stringify(categories));
    localStorage.setItem("kiosko_products", JSON.stringify(products));
    localStorage.setItem("kiosko_orders", JSON.stringify(orders));
}

// ==================== ESTADÍSTICAS EN TIEMPO REAL ====================
function getFilteredOrders() {
    const now = new Date();
    let startDate = new Date();
    
    if (currentTimeFilter === "day") {
        startDate.setHours(0, 0, 0, 0);
    } else if (currentTimeFilter === "week") {
        startDate.setDate(now.getDate() - 7);
    } else if (currentTimeFilter === "month") {
        startDate.setMonth(now.getMonth() - 1);
    }
    
    return orders.filter(order => new Date(order.date) >= startDate);
}

function renderStats() {
    const filteredOrders = getFilteredOrders();
    const totalOrders = filteredOrders.length;
    const pendingOrders = filteredOrders.filter(o => o.status === "pending").length;
    const completedOrders = filteredOrders.filter(o => o.status === "completed").length;
    const rejectedOrders = filteredOrders.filter(o => o.status === "rejected").length;
    const totalRevenue = filteredOrders
        .filter(o => o.status === "completed")
        .reduce((sum, o) => sum + o.total, 0);
    
    document.getElementById("totalOrders").textContent = totalOrders;
    document.getElementById("pendingOrders").textContent = pendingOrders;
    document.getElementById("completedOrders").textContent = completedOrders;
    document.getElementById("rejectedOrders").textContent = rejectedOrders;
    document.getElementById("totalRevenue").textContent = `S/ ${totalRevenue.toFixed(2)}`;
}

// ==================== GRÁFICOS ====================
function renderCharts() {
    const filteredOrders = getFilteredOrders();
    
    // Ventas por día/semana/mes
    const salesData = {};
    filteredOrders.forEach(order => {
        const date = new Date(order.date);
        let key;
        if (currentTimeFilter === "day") {
            key = `${date.getHours()}:00`;
        } else if (currentTimeFilter === "week") {
            key = date.toLocaleDateString('es-PE', { weekday: 'short' });
        } else {
            key = `${date.getDate()}/${date.getMonth() + 1}`;
        }
        
        if (!salesData[key]) salesData[key] = 0;
        salesData[key] += order.total;
    });
    
    const ctx1 = document.getElementById("salesChart").getContext("2d");
    if (salesChart) salesChart.destroy();
    salesChart = new Chart(ctx1, {
        type: "line",
        data: {
            labels: Object.keys(salesData),
            datasets: [{
                label: "Ventas (S/.)",
                data: Object.values(salesData),
                borderColor: "#27ae60",
                backgroundColor: "rgba(39, 174, 96, 0.1)",
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: "top" }
            }
        }
    });
    
    // Top productos más vendidos
    const productSales = {};
    filteredOrders.forEach(order => {
        order.items.forEach(item => {
            if (!productSales[item.name]) productSales[item.name] = 0;
            productSales[item.name] += item.quantity;
        });
    });
    
    const sortedProducts = Object.entries(productSales)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 5);
    
    const ctx2 = document.getElementById("topProductsChart").getContext("2d");
    if (topProductsChart) topProductsChart.destroy();
    topProductsChart = new Chart(ctx2, {
        type: "bar",
        data: {
            labels: sortedProducts.map(p => p[0]),
            datasets: [{
                label: "Unidades Vendidas",
                data: sortedProducts.map(p => p[1]),
                backgroundColor: "#3498db"
            }]
        },
        options: {
            responsive: true
        }
    });
}

// ==================== PANEL DE GESTIÓN DE PRODUCTOS ====================
function renderAdminPanel() {
    const container = document.getElementById("adminCategoriesContainer");
    if (!container) return;
    
    container.innerHTML = categories.map(cat => `
        <div class="admin-category" data-cat-id="${cat.id}">
            <div class="admin-category-header">
                <strong><i class="fas fa-folder"></i> ${cat.name}</strong>
                <div>
                    <button class="edit-category" data-id="${cat.id}"><i class="fas fa-edit"></i> Editar</button>
                    <button class="delete-category" data-id="${cat.id}"><i class="fas fa-trash"></i> Eliminar</button>
                </div>
            </div>
            <div class="admin-products">
                ${products.filter(p => p.categoryId === cat.id).map(prod => `
                    <div class="admin-product-item">
                        <span><strong>${prod.name}</strong> ${prod.brand ? `(${prod.brand})` : ''} - S/ ${prod.price.toFixed(2)}</span>
                        <div>
                            <button class="edit-product" data-id="${prod.id}"><i class="fas fa-pen"></i></button>
                            <button class="delete-product" data-id="${prod.id}"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                `).join("")}
                <button class="add-product-to-cat" data-cat-id="${cat.id}" style="margin-top: 10px;">
                    <i class="fas fa-plus"></i> Agregar Producto
                </button>
            </div>
        </div>
    `).join("");
    
    // Event listeners para CRUD
    document.querySelectorAll(".edit-category").forEach(btn => {
        btn.onclick = () => editCategory(btn.dataset.id);
    });
    document.querySelectorAll(".delete-category").forEach(btn => {
        btn.onclick = () => deleteCategory(btn.dataset.id);
    });
    document.querySelectorAll(".edit-product").forEach(btn => {
        btn.onclick = () => editProduct(btn.dataset.id);
    });
    document.querySelectorAll(".delete-product").forEach(btn => {
        btn.onclick = () => deleteProduct(btn.dataset.id);
    });
    document.querySelectorAll(".add-product-to-cat").forEach(btn => {
        btn.onclick = () => openProductModal(btn.dataset.catId);
    });
}

function editCategory(id) {
    const cat = categories.find(c => c.id === id);
    if (!cat) return;
    const newName = prompt("Editar categoría:", cat.name);
    if (newName) {
        cat.name = newName;
        saveData();
        renderAdminPanel();
        // Actualizar pestañas en la tienda si está abierta
        localStorage.setItem("kiosko_needs_refresh", "true");
    }
}

function deleteCategory(id) {
    if (confirm("¿Eliminar esta categoría? Se eliminarán todos sus productos.")) {
        categories = categories.filter(c => c.id !== id);
        products = products.filter(p => p.categoryId !== id);
        saveData();
        renderAdminPanel();
        localStorage.setItem("kiosko_needs_refresh", "true");
    }
}

function editProduct(id) {
    const prod = products.find(p => p.id === id);
    if (!prod) return;
    const newName = prompt("Nuevo nombre:", prod.name);
    const newPrice = prompt("Nuevo precio:", prod.price);
    if (newName && newPrice) {
        prod.name = newName;
        prod.price = parseFloat(newPrice);
        saveData();
        renderAdminPanel();
        localStorage.setItem("kiosko_needs_refresh", "true");
    }
}

function deleteProduct(id) {
    if (confirm("¿Eliminar este producto?")) {
        products = products.filter(p => p.id !== id);
        saveData();
        renderAdminPanel();
        localStorage.setItem("kiosko_needs_refresh", "true");
    }
}

function openProductModal(categoryId) {
    const name = prompt("Nombre del producto:");
    if (!name) return;
    const brand = prompt("Presentación (ej: 500ml):");
    const price = parseFloat(prompt("Precio:"));
    if (isNaN(price)) return;
    
    const newProduct = {
        id: "prod_" + Date.now(),
        name: name,
        brand: brand || "",
        price: price,
        categoryId: categoryId
    };
    
    products.push(newProduct);
    saveData();
    renderAdminPanel();
    localStorage.setItem("kiosko_needs_refresh", "true");
}

// ==================== GESTIÓN DE PEDIDOS ====================
function renderOrders() {
    const statusFilter = document.getElementById("orderStatusFilter")?.value || "all";
    let filteredOrders = [...orders];
    
    if (statusFilter !== "all") {
        filteredOrders = filteredOrders.filter(o => o.status === statusFilter);
    }
    
    const container = document.getElementById("ordersList");
    if (!container) return;
    
    if (filteredOrders.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:40px;">No hay pedidos para mostrar</div>';
        return;
    }
    
    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Productos</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                ${filteredOrders.map(order => `
                    <tr>
                        <td><strong>${order.id}</strong></td>
                        <td>${order.fecha || new Date(order.date).toLocaleDateString('es-PE')}</td>
                        <td>${order.hora || new Date(order.date).toLocaleTimeString('es-PE')}</td>
                        <td>${order.items.reduce((sum, i) => sum + i.quantity, 0)} artículos</td>
                        <td>S/ ${order.total.toFixed(2)}</td>
                        <td>
                            <span class="status-badge status-${order.status}">
                                ${order.status === "pending" ? "Pendiente" : order.status === "completed" ? "Completado" : "Rechazado"}
                            </span>
                        </td>
                        <td>
                            <button class="view-order-btn" data-id="${order.id}">Ver Detalle</button>
                        </td>
                    </tr>
                `).join("")}
            </tbody>
        </table>
    `;
    
    document.querySelectorAll(".view-order-btn").forEach(btn => {
        btn.onclick = () => showOrderDetail(btn.dataset.id);
    });
}

function showOrderDetail(orderId) {
    const order = orders.find(o => o.id === orderId);
    if (!order) return;
    
    const modal = document.getElementById("orderDetailModal");
    const content = document.getElementById("orderDetailContent");
    
    content.innerHTML = `
        <p><strong>Pedido ID:</strong> ${order.id}</p>
        <p><strong>Fecha:</strong> ${order.fecha || new Date(order.date).toLocaleDateString('es-PE')}</p>
        <p><strong>Hora:</strong> ${order.hora || new Date(order.date).toLocaleTimeString('es-PE')}</p>
        <hr>
        <h4>Productos:</h4>
        ${order.items.map(item => `
            <div style="margin: 10px 0; padding: 8px; background: #f8f9fa; border-radius: 8px;">
                <strong>${item.quantity}x</strong> ${item.name} ${item.brand ? `(${item.brand})` : ''}<br>
                S/ ${item.price.toFixed(2)} c/u → S/ ${item.subtotal.toFixed(2)}
            </div>
        `).join("")}
        <hr>
        <h3 style="color: #27ae60;">Total: S/ ${order.total.toFixed(2)}</h3>
    `;
    
    modal.style.display = "flex";
    
    // Configurar botones de estado
    const statusBtns = document.querySelectorAll(".status-btn");
    statusBtns.forEach(btn => {
        btn.onclick = () => updateOrderStatus(order.id, btn.dataset.status);
    });
}

function updateOrderStatus(orderId, newStatus) {
    const order = orders.find(o => o.id === orderId);
    if (order) {
        order.status = newStatus;
        saveData();
        renderOrders();
        renderStats();
        renderCharts();
        document.getElementById("orderDetailModal").style.display = "none";
        showNotification(`Pedido ${orderId} actualizado a ${newStatus}`, "success");
    }
}

function showNotification(message, type) {
    // Crear notificación simple
    const notif = document.createElement("div");
    notif.textContent = message;
    notif.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${type === "success" ? "#27ae60" : "#3498db"};
        color: white;
        padding: 12px 20px;
        border-radius: 10px;
        z-index: 2000;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
}

// ==================== EVENT LISTENERS ====================
function setupEventListeners() {
    // Filtros de tiempo
    document.querySelectorAll(".time-filter").forEach(btn => {
        btn.onclick = () => {
            document.querySelectorAll(".time-filter").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");
            currentTimeFilter = btn.dataset.filter;
            renderStats();
            renderCharts();
        };
    });
    
    // Filtro de pedidos
    document.getElementById("orderStatusFilter")?.addEventListener("change", renderOrders);
    
    // Agregar categoría
    document.getElementById("addCategoryBtn")?.addEventListener("click", () => {
        const name = prompt("Nombre de la nueva categoría:");
        if (name) {
            categories.push({ id: "cat_" + Date.now(), name: name });
            saveData();
            renderAdminPanel();
            localStorage.setItem("kiosko_needs_refresh", "true");
        }
    });
    
    // Agregar producto general
    document.getElementById("addProductBtn")?.addEventListener("click", () => {
        if (categories.length === 0) {
            alert("Primero crea una categoría");
            return;
        }
        const categoryId = prompt("ID de categoría (disponibles: " + categories.map(c => c.id).join(", ") + "):");
        if (categoryId && categories.find(c => c.id === categoryId)) {
            openProductModal(categoryId);
        } else {
            alert("Categoría no válida");
        }
    });
    
    // Logout
    document.getElementById("logoutAdminBtn")?.addEventListener("click", () => {
        localStorage.removeItem("kiosko_admin_auth");
        window.location.href = "index.html";
    });
    
    // Volver a la tienda
    document.getElementById("backToStoreBtn")?.addEventListener("click", () => {
        window.location.href = "index.html";
    });
    
    // Cerrar modales
    document.querySelectorAll(".close").forEach(btn => {
        btn.onclick = () => {
            document.querySelectorAll(".modal").forEach(m => m.style.display = "none");
        };
    });
    
    window.onclick = (e) => {
        if (e.target.classList.contains("modal")) {
            e.target.style.display = "none";
        }
    };
}

// Iniciar
initAdmin();