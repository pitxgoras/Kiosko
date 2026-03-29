<?php
session_start();
require_once '../config/database.php';

// Verificar autenticación
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

$conn = getConnection();

// Obtener estadísticas
$stats = [];

// Total de pedidos
$result = $conn->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $result->fetch_assoc()['total'];

// Pedidos por estado
$result = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while($row = $result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}

// Ingresos totales
$result = $conn->query("SELECT SUM(total) as total FROM orders WHERE status = 'completed'");
$stats['revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Obtener categorías
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Obtener productos
$products = $conn->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.name");

// Obtener pedidos recientes
$orders = $conn->query("SELECT o.*, COUNT(oi.id) as items_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id GROUP BY o.id ORDER BY o.order_date DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosko - Panel Administrador</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
        }

        .admin-header {
            background: linear-gradient(135deg, #2c3e50, #1a2632);
            padding: 1rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
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

        .btn-logout {
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            cursor: pointer;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #27ae60;
        }

        .time-filters {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .time-filter {
            padding: 0.5rem 1.5rem;
            border: none;
            background: white;
            border-radius: 2rem;
            cursor: pointer;
            transition: 0.2s;
        }

        .time-filter.active {
            background: #2c3e50;
            color: white;
        }

        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        .chart-card {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
        }

        .section {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ecf0f1;
        }

        .btn-primary, .btn-secondary {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-primary {
            background: #27ae60;
            color: white;
        }

        .btn-secondary {
            background: #3498db;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .export-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .category-item, .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .orders-table {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

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
            border-radius: 1rem;
            width: 90%;
            max-width: 450px;
        }

        .modal-content input, .modal-content select {
            width: 100%;
            padding: 0.75rem;
            margin: 0.5rem 0;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
        }

        .close {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="logo-area">
            <i class="fas fa-store"></i>
            <h1>Kiosko Admin</h1>
        </div>
        <div>
            <button id="logoutBtn" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Salir</button>
        </div>
    </header>

    <div class="dashboard-container">
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-value"><?= $stats['total_orders'] ?></div>
                <div>Pedidos Totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?= $stats['pending'] ?? 0 ?></div>
                <div>Pendientes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?= $stats['completed'] ?? 0 ?></div>
                <div>Completados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-value"><?= $stats['rejected'] ?? 0 ?></div>
                <div>Rechazados</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-value">S/ <?= number_format($stats['revenue'], 2) ?></div>
                <div>Ingresos Totales</div>
            </div>
        </div>

        <!-- Filtros de tiempo -->
        <div class="time-filters">
            <button class="time-filter active" data-filter="day">📅 Hoy</button>
            <button class="time-filter" data-filter="week">📆 Esta Semana</button>
            <button class="time-filter" data-filter="month">📊 Este Mes</button>
        </div>

        <!-- Gráficos -->
        <div class="charts-row">
            <div class="chart-card">
                <h3>Ventas por Periodo</h3>
                <canvas id="salesChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Productos Más Vendidos</h3>
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>

        <!-- Exportar Reportes -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-download"></i> Exportar Reportes</h2>
                <div class="export-buttons">
                    <button id="exportDayBtn" class="btn-secondary"><i class="fas fa-calendar-day"></i> Exportar Hoy</button>
                    <button id="exportWeekBtn" class="btn-secondary"><i class="fas fa-calendar-week"></i> Exportar Semana</button>
                    <button id="exportMonthBtn" class="btn-secondary"><i class="fas fa-calendar-alt"></i> Exportar Mes</button>
                </div>
            </div>
        </div>

        <!-- Gestión de Categorías -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-folder"></i> Categorías</h2>
                <button id="addCategoryBtn" class="btn-primary">+ Nueva Categoría</button>
            </div>
            <div id="categoriesList">
                <?php while($cat = $categories->fetch_assoc()): ?>
                    <div class="category-item" data-id="<?= $cat['id'] ?>">
                        <span><i class="fas fa-tag"></i> <?= htmlspecialchars($cat['name']) ?></span>
                        <div>
                            <button class="edit-category btn-secondary" data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars($cat['name']) ?>">Editar</button>
                            <button class="delete-category btn-danger" data-id="<?= $cat['id'] ?>">Eliminar</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Gestión de Productos -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-box"></i> Productos</h2>
                <button id="addProductBtn" class="btn-primary">+ Nuevo Producto</button>
            </div>
            <div id="productsList">
                <?php while($prod = $products->fetch_assoc()): ?>
                    <div class="product-item" data-id="<?= $prod['id'] ?>">
                        <span><strong><?= htmlspecialchars($prod['name']) ?></strong> <?= htmlspecialchars($prod['brand'] ?? '') ?> - S/ <?= number_format($prod['price'], 2) ?> (<?= htmlspecialchars($prod['category_name']) ?>)</span>
                        <div>
                            <button class="edit-product btn-secondary" data-id="<?= $prod['id'] ?>">Editar</button>
                            <button class="delete-product btn-danger" data-id="<?= $prod['id'] ?>">Eliminar</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Gestión de Pedidos -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-truck"></i> Pedidos</h2>
                <select id="orderStatusFilter">
                    <option value="all">Todos</option>
                    <option value="pending">Pendientes</option>
                    <option value="completed">Completados</option>
                    <option value="rejected">Rechazados</option>
                </select>
            </div>
            <div class="orders-table">
                <table id="ordersTable">
                    <thead>
                        <tr><th>ID</th><th>Fecha</th><th>Productos</th><th>Total</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody id="ordersBody">
                        <?php while($order = $orders->fetch_assoc()): ?>
                            <tr data-id="<?= $order['id'] ?>">
                                <td><?= $order['order_number'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></td>
                                <td><?= $order['items_count'] ?> artículos</td>
                                <td>S/ <?= number_format($order['total'], 2) ?></td>
                                <td>
                                    <select class="order-status" data-id="<?= $order['id'] ?>">
                                        <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pendiente</option>
                                        <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completado</option>
                                        <option value="rejected" <?= $order['status'] == 'rejected' ? 'selected' : '' ?>>Rechazado</option>
                                    </select>
                                </td>
                                <td><button class="view-order btn-secondary" data-id="<?= $order['id'] ?>">Ver</button></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modales -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3 id="categoryModalTitle">Nueva Categoría</h3>
            <input type="text" id="categoryName" placeholder="Nombre de la categoría">
            <button id="saveCategoryBtn" class="btn-primary">Guardar</button>
        </div>
    </div>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3 id="productModalTitle">Nuevo Producto</h3>
            <input type="text" id="productName" placeholder="Nombre">
            <input type="text" id="productBrand" placeholder="Presentación">
            <input type="number" id="productPrice" placeholder="Precio" step="0.01">
            <select id="productCategory"></select>
            <input type="number" id="productStock" placeholder="Stock">
            <button id="saveProductBtn" class="btn-primary">Guardar</button>
        </div>
    </div>

    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Detalle del Pedido</h3>
            <div id="orderDetails"></div>
        </div>
    </div>

    <script>
        let salesChart, topProductsChart;
        let currentFilter = 'day';

        async function loadDashboard() {
            const response = await fetch(`api/get_stats.php?filter=${currentFilter}`);
            const data = await response.json();
            
            if (salesChart) salesChart.destroy();
            const ctx = document.getElementById('salesChart').getContext('2d');
            salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Ventas (S/.)',
                        data: data.sales,
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: { responsive: true }
            });
            
            if (topProductsChart) topProductsChart.destroy();
            const ctx2 = document.getElementById('topProductsChart').getContext('2d');
            topProductsChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: data.topProducts.map(p => p.name),
                    datasets: [{
                        label: 'Unidades Vendidas',
                        data: data.topProducts.map(p => p.total_sold),
                        backgroundColor: '#3498db'
                    }]
                },
                options: { responsive: true }
            });
        }

        async function updateOrderStatus(orderId, status) {
            await fetch('api/update_order_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: orderId, status })
            });
            location.reload();
        }

        async function exportReport(period) {
            window.open(`api/export_report.php?period=${period}`, '_blank');
        }

        // CRUD Categorías
        async function addCategory(name) {
            await fetch('api/add_category.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name })
            });
            location.reload();
        }

        async function editCategory(id, name) {
            await fetch('api/edit_category.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name })
            });
            location.reload();
        }

        async function deleteCategory(id) {
            if (confirm('¿Eliminar esta categoría?')) {
                await fetch('api/delete_category.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                location.reload();
            }
        }

        async function addProduct(product) {
            await fetch('api/add_product.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(product)
            });
            location.reload();
        }

        async function editProduct(product) {
            await fetch('api/edit_product.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(product)
            });
            location.reload();
        }

        async function deleteProduct(id) {
            if (confirm('¿Eliminar este producto?')) {
                await fetch('api/delete_product.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                location.reload();
            }
        }

        // Event Listeners
        document.querySelectorAll('.time-filter').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.time-filter').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentFilter = btn.dataset.filter;
                loadDashboard();
            });
        });

        document.getElementById('exportDayBtn')?.addEventListener('click', () => exportReport('day'));
        document.getElementById('exportWeekBtn')?.addEventListener('click', () => exportReport('week'));
        document.getElementById('exportMonthBtn')?.addEventListener('click', () => exportReport('month'));

        document.getElementById('addCategoryBtn')?.addEventListener('click', () => {
            document.getElementById('categoryModalTitle').textContent = 'Nueva Categoría';
            document.getElementById('categoryName').value = '';
            document.getElementById('categoryModal').style.display = 'flex';
        });

        document.getElementById('saveCategoryBtn')?.addEventListener('click', () => {
            const name = document.getElementById('categoryName').value;
            if (name) addCategory(name);
        });

        document.querySelectorAll('.edit-category').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const name = btn.dataset.name;
                document.getElementById('categoryModalTitle').textContent = 'Editar Categoría';
                document.getElementById('categoryName').value = name;
                document.getElementById('categoryModal').dataset.editId = id;
                document.getElementById('categoryModal').style.display = 'flex';
            });
        });

        document.querySelectorAll('.delete-category').forEach(btn => {
            btn.addEventListener('click', () => deleteCategory(btn.dataset.id));
        });

        document.getElementById('addProductBtn')?.addEventListener('click', async () => {
            const cats = await fetch('api/get_categories.php').then(r => r.json());
            document.getElementById('productCategory').innerHTML = cats.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
            document.getElementById('productModalTitle').textContent = 'Nuevo Producto';
            document.getElementById('productName').value = '';
            document.getElementById('productBrand').value = '';
            document.getElementById('productPrice').value = '';
            document.getElementById('productStock').value = '';
            document.getElementById('productModal').style.display = 'flex';
        });

        document.getElementById('saveProductBtn')?.addEventListener('click', () => {
            const product = {
                name: document.getElementById('productName').value,
                brand: document.getElementById('productBrand').value,
                price: document.getElementById('productPrice').value,
                category_id: document.getElementById('productCategory').value,
                stock: document.getElementById('productStock').value
            };
            if (product.name && product.price) addProduct(product);
        });

        document.querySelectorAll('.edit-product').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const prod = await fetch(`api/get_product.php?id=${id}`).then(r => r.json());
                const cats = await fetch('api/get_categories.php').then(r => r.json());
                document.getElementById('productCategory').innerHTML = cats.map(c => `<option value="${c.id}" ${c.id == prod.category_id ? 'selected' : ''}>${c.name}</option>`).join('');
                document.getElementById('productModalTitle').textContent = 'Editar Producto';
                document.getElementById('productName').value = prod.name;
                document.getElementById('productBrand').value = prod.brand;
                document.getElementById('productPrice').value = prod.price;
                document.getElementById('productStock').value = prod.stock;
                document.getElementById('productModal').dataset.editId = id;
                document.getElementById('productModal').style.display = 'flex';
            });
        });

        document.querySelectorAll('.delete-product').forEach(btn => {
            btn.addEventListener('click', () => deleteProduct(btn.dataset.id));
        });

        document.querySelectorAll('.order-status').forEach(select => {
            select.addEventListener('change', () => updateOrderStatus(select.dataset.id, select.value));
        });

        document.getElementById('orderStatusFilter')?.addEventListener('change', () => {
            const status = document.getElementById('orderStatusFilter').value;
            const rows = document.querySelectorAll('#ordersBody tr');
            rows.forEach(row => {
                const select = row.querySelector('.order-status');
                if (status === 'all' || (select && select.value === status)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        document.getElementById('logoutBtn')?.addEventListener('click', () => {
            window.location.href = 'api/logout.php';
        });

        document.querySelectorAll('.close').forEach(btn => {
            btn.onclick = () => {
                document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
            };
        });

        loadDashboard();
    </script>
</body>
</html>