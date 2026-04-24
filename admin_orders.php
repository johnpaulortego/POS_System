<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$orders = $pdo->query("
    SELECT o.*, u.username as cashier_name 
    FROM orders o 
    JOIN users u ON o.cashier_id = u.id
    ORDER BY o.created_at DESC
")->fetchAll();

// Stats
$total      = count($orders);
$completed  = array_filter($orders, fn($o) => $o['status'] === 'completed');
$pending    = array_filter($orders, fn($o) => $o['status'] === 'pending');
$cancelled  = array_filter($orders, fn($o) => $o['status'] === 'cancelled');
$revenue    = array_sum(array_column(iterator_to_array((function() use ($orders) { foreach($orders as $o) if($o['status']==='completed') yield $o; })()), 'total_amount'));
$todayCount = count(array_filter($orders, fn($o) => date('Y-m-d', strtotime($o['created_at'])) === date('Y-m-d')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Monitoring | Purr'Coffee</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --orange: #F28B50; --light-orange: #FFF5F0;
            --bg: #F5F5F5; --white: #FAFAFA;
            --text-main: #2D2D2D; --text-muted: #8B8B8B;
            --border: #BEBEBE; --card-bg: #FFFFFF; --input-bg: #FFFFFF;
            --table-hover: #F8F8F8; --green: #27AE60; --red: #EB5757;
            --shadow: rgba(0,0,0,0.08);
        }
        [data-theme="dark"] {
            --bg: #1A1A1A; --white: #2A2A2A; --text-main: #FFFFFF;
            --text-muted: #AAAAAA; --border: #444444;
            --card-bg: #2A2A2A; --input-bg: #333333; --table-hover: #333333;
        }
        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; transition: background 0.3s ease; }

        /* SIDEBAR */
        .sidebar-left { width: 160px; background: var(--white); padding: 25px 18px; display: flex; flex-direction: column; border-right: 2px solid var(--border); flex-shrink: 0; }
        .logo { font-size: 18px; font-weight: 700; margin-bottom: 50px; }
        .logo span { color: var(--orange); }
        .nav-group { flex: 0; margin-bottom: 60px; }
        .nav-item { display: flex; align-items: center; padding: 10px 14px; text-decoration: none; color: var(--text-muted); font-weight: 500; margin-bottom: 8px; transition: 0.2s; position: relative; font-size: 13px; }
        .nav-item i { margin-right: 10px; width: 18px; height: 18px; }
        .nav-item.active { color: var(--orange); font-weight: 600; }
        .nav-item.active::after { content: ''; position: absolute; right: -18px; top: 50%; transform: translateY(-50%); width: 4px; height: 28px; background: var(--orange); border-radius: 2px 0 0 2px; }
        .nav-item:hover { color: var(--orange); }
        .theme-toggle { display: flex; align-items: center; padding: 10px 14px; cursor: pointer; color: var(--text-muted); font-weight: 500; margin-bottom: 8px; transition: 0.2s; font-size: 13px; }
        .theme-toggle:hover { color: var(--orange); }
        .theme-toggle i { width: 18px; height: 18px; margin-right: 10px; }

        /* CONTENT */
        .content-main { flex: 1; padding: 35px 40px; overflow-y: auto; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; }
        .page-sub { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        /* STATS */
        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--card-bg); border: 2px solid var(--border); border-radius: 16px; padding: 18px 20px; }
        .stat-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .stat-icon i { width: 18px; height: 18px; }
        .ic-orange { background: var(--light-orange); color: var(--orange); stroke: var(--orange); }
        .ic-green  { background: #E6F7ED; color: var(--green); }
        .ic-yellow { background: #FFF9E6; color: #D4A017; }
        .ic-red    { background: #FFF0F0; color: var(--red); }
        .ic-blue   { background: #EBF5FB; color: #3498DB; }
        [data-theme="dark"] .ic-orange { background: rgba(242,139,80,0.15); }
        [data-theme="dark"] .ic-green  { background: rgba(39,174,96,0.15); }
        [data-theme="dark"] .ic-yellow { background: rgba(212,160,23,0.15); }
        [data-theme="dark"] .ic-red    { background: rgba(235,87,87,0.15); }
        [data-theme="dark"] .ic-blue   { background: rgba(52,152,219,0.15); }
        .stat-val   { font-size: 22px; font-weight: 800; line-height: 1; margin-bottom: 4px; }
        .stat-label { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }

        /* FILTER BAR */
        .filter-bar { display: flex; gap: 12px; margin-bottom: 18px; align-items: center; flex-wrap: wrap; }
        .filter-tab { padding: 9px 18px; border-radius: 20px; border: 2px solid var(--border); background: var(--card-bg); font-size: 13px; font-weight: 600; cursor: pointer; color: var(--text-main); transition: 0.2s; }
        .filter-tab.active { background: var(--orange); color: white; border-color: var(--orange); }
        .filter-tab:hover:not(.active) { border-color: var(--orange); color: var(--orange); }
        .search-box { background: var(--card-bg); border: 2px solid var(--border); border-radius: 20px; display: flex; align-items: center; padding: 9px 16px; gap: 8px; margin-left: auto; }
        .search-box input { border: none; outline: none; background: transparent; font-size: 13px; color: var(--text-main); width: 200px; }
        .search-box input::placeholder { color: var(--text-muted); }
        .search-box i { width: 15px; height: 15px; color: var(--text-muted); flex-shrink: 0; }
        .refresh-btn { padding: 9px 16px; border-radius: 20px; border: 2px solid var(--border); background: var(--card-bg); font-size: 13px; font-weight: 600; cursor: pointer; color: var(--text-main); display: flex; align-items: center; gap: 6px; transition: 0.2s; }
        .refresh-btn:hover { border-color: var(--orange); color: var(--orange); }
        .refresh-btn i { width: 15px; height: 15px; }
        .nav-badge { background: white; color: var(--orange); font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 20px; margin-left: auto; }

        /* TABLE */
        .table-card { background: var(--card-bg); border: 2px solid var(--border); border-radius: 18px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 14px 20px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; background: var(--bg); border-bottom: 2px solid var(--border); text-align: left; }
        td { padding: 15px 20px; font-size: 13px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: var(--table-hover); }

        .order-num { font-weight: 800; color: var(--orange); }
        .cashier-cell { display: flex; align-items: center; gap: 8px; font-weight: 600; }
        .cashier-cell i { width: 15px; height: 15px; color: var(--text-muted); }
        .date-cell { color: var(--text-muted); font-size: 12px; }
        .amount-cell { font-weight: 800; font-size: 14px; }

        .status-pill { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .status-completed { background: #E6F7ED; color: var(--green); }
        .status-pending   { background: #FFF9E6; color: #D4A017; }
        .status-cancelled { background: #FFF0F0; color: var(--red); }
        [data-theme="dark"] .status-completed { background: #1A3D2A; color: #4ADE80; }
        [data-theme="dark"] .status-pending   { background: #3D3520; color: #FFD700; }
        [data-theme="dark"] .status-cancelled { background: #3D1A1A; color: #FF6B6B; }

        .action-select { padding: 7px 10px; border-radius: 10px; border: 2px solid var(--border); font-size: 12px; font-weight: 600; outline: none; background: var(--input-bg); color: var(--text-main); cursor: pointer; transition: 0.2s; }
        .action-select:focus { border-color: var(--orange); }

        .details-btn { padding: 7px 14px; border-radius: 10px; border: 2px solid var(--border); background: transparent; font-size: 12px; font-weight: 700; cursor: pointer; color: var(--text-main); transition: 0.2s; }
        .details-btn:hover { border-color: var(--orange); color: var(--orange); }

        .empty-state { text-align: center; padding: 60px; color: var(--text-muted); }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: var(--card-bg); border-radius: 24px; border: 2px solid var(--border); padding: 30px; width: 420px; max-width: 90vw; max-height: 90vh; overflow-y: auto; }
        .modal-title { font-size: 18px; font-weight: 800; margin-bottom: 20px; }
        .receipt { font-family: 'Courier New', monospace; font-size: 13px; color: #000; background: #fff; padding: 20px; border-radius: 12px; border: 2px dashed #ccc; }
        [data-theme="dark"] .receipt { color: #000; background: #fff; }
        .receipt-head { text-align: center; padding-bottom: 12px; border-bottom: 2px dashed #000; margin-bottom: 12px; }
        .receipt-store { font-size: 17px; font-weight: 900; letter-spacing: 1px; }
        .receipt-sub { font-size: 11px; line-height: 1.5; margin-top: 4px; color: #444; }
        .receipt-meta { font-size: 11px; line-height: 1.8; margin-bottom: 12px; }
        .receipt-items { border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin-bottom: 12px; }
        .receipt-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px; }
        .receipt-row-name { flex: 1; font-weight: 700; }
        .receipt-row-price { font-weight: 700; min-width: 70px; text-align: right; }
        .receipt-totals { font-size: 12px; }
        .receipt-total-line { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .receipt-total-line.final { font-size: 15px; font-weight: 900; border-top: 2px solid #000; padding-top: 8px; margin-top: 8px; }
        .receipt-footer { text-align: center; border-top: 2px dashed #000; padding-top: 12px; margin-top: 12px; font-size: 11px; line-height: 1.8; }
        .modal-actions { display: flex; gap: 10px; margin-top: 16px; }
        .modal-print-btn { flex: 1; padding: 12px; border-radius: 12px; border: none; background: var(--orange); color: white; font-weight: 700; font-size: 14px; cursor: pointer; }
        .modal-close-btn { flex: 1; padding: 12px; border-radius: 12px; border: 2px solid var(--border); background: transparent; font-weight: 700; font-size: 14px; cursor: pointer; color: var(--text-main); transition: 0.2s; }
        .modal-close-btn:hover { border-color: var(--orange); color: var(--orange); }

        @media print {
            body * { visibility: hidden !important; }
            .receipt, .receipt * { visibility: visible !important; }
            .receipt { display: block !important; position: fixed !important; inset: 0 !important; width: 100% !important; padding: 40px !important; font-size: 16px !important; color: #000 !important; background: #fff !important; border: none !important; }
            .receipt-store { font-size: 28px !important; }
            .receipt-total-line.final { font-size: 22px !important; }
        }
    </style>
</head>
<body>

    <div class="sidebar-left">
        <div class="logo">Purr'<span>Coffee</span></div>
        <div class="nav-group">
            <a href="admin.php" class="nav-item"><i data-lucide="layout-dashboard"></i> Dashboard</a>
            <a href="manage_products.php" class="nav-item"><i data-lucide="coffee"></i> Products</a>
            <a href="admin_orders.php" class="nav-item active"><i data-lucide="shopping-cart"></i> Orders <?php if(count($pending) > 0): ?><span class="nav-badge"><?= count($pending) ?></span><?php endif; ?></a>
        </div>
        <div style="margin-top: auto;">
            <div class="theme-toggle" onclick="toggleTheme()">
                <i data-lucide="moon" id="themeIcon"></i>
                <span id="themeText">Dark Mode</span>
            </div>
            <a href="logout.php" class="nav-item"><i data-lucide="log-out"></i> Log out</a>
        </div>
    </div>

    <div class="content-main">
        <div class="page-header">
            <div>
                <div class="page-title">Order Monitoring</div>
                <div class="page-sub">Track all transactions and manage order statuses</div>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon ic-orange"><i data-lucide="shopping-bag"></i></div>
                <div class="stat-val"><?= $total ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon ic-green"><i data-lucide="check-circle"></i></div>
                <div class="stat-val"><?= count($completed) ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon ic-yellow"><i data-lucide="clock"></i></div>
                <div class="stat-val"><?= count($pending) ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon ic-red"><i data-lucide="x-circle"></i></div>
                <div class="stat-val"><?= count($cancelled) ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon ic-blue"><i data-lucide="calendar"></i></div>
                <div class="stat-val"><?= $todayCount ?></div>
                <div class="stat-label">Today</div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="filter-bar">
            <button class="filter-tab active" onclick="filterOrders('all', this)">All</button>
            <button class="filter-tab" onclick="filterOrders('pending', this)">Pending</button>
            <button class="filter-tab" onclick="filterOrders('completed', this)">Completed</button>
            <button class="filter-tab" onclick="filterOrders('cancelled', this)">Cancelled</button>
            <div class="search-box">
                <i data-lucide="search"></i>
                <input type="text" id="searchInput" placeholder="Search order or cashier..." oninput="searchOrders()">
            </div>
            <button class="refresh-btn" onclick="exportCSV()" style="border-color:var(--green);color:var(--green);">
                <i data-lucide="download"></i> Export CSV
            </button>
            <button class="refresh-btn" onclick="location.reload()">
                <i data-lucide="refresh-cw"></i> Refresh
            </button>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Cashier</th>
                        <th>Date & Time</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTable">
                    <?php if(empty($orders)): ?>
                    <tr><td colspan="6" class="empty-state">No orders found.</td></tr>
                    <?php endif; ?>
                    <?php foreach($orders as $o): ?>
                    <tr class="order-row" data-status="<?= $o['status'] ?>" data-search="<?= strtolower($o['cashier_name']) . ' ' . ($o['order_number'] ?? $o['id']) ?>">
                        <td class="order-num">#<?= isset($o['order_number']) && $o['order_number'] ? $o['order_number'] : str_pad($o['id'],6,'0',STR_PAD_LEFT) ?></td>
                        <td>
                            <div class="cashier-cell">
                                <i data-lucide="user"></i>
                                <?= htmlspecialchars($o['cashier_name']) ?>
                            </div>
                        </td>
                        <td class="date-cell"><?= date('M d, Y • h:i A', strtotime($o['created_at'])) ?></td>
                        <td class="amount-cell">₱<?= number_format($o['total_amount'], 2) ?></td>
                        <td>
                            <span class="status-pill status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
                        </td>
                        <td>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <form method="POST" action="update_order_status.php" style="margin:0;">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" class="action-select" data-original="<?= $o['status'] ?>" onchange="confirmStatusChange(this)">
                                        <option value="pending"   <?= $o['status']==='pending'   ? 'selected':'' ?>>Pending</option>
                                        <option value="completed" <?= $o['status']==='completed' ? 'selected':'' ?>>Completed</option>
                                        <option value="cancelled" <?= $o['status']==='cancelled' ? 'selected':'' ?>>Cancelled</option>
                                    </select>
                                </form>
                                <button class="details-btn" onclick="viewDetails(<?= $o['id'] ?>)">Receipt</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RECEIPT MODAL -->
    <div class="modal-overlay" id="detailModal" onclick="if(event.target===this)closeModal()">
        <div class="modal-box">
            <div id="modalBody"></div>
            <div class="modal-actions">
                <button class="modal-print-btn" onclick="printReceipt()">🖨️ Print</button>
                <button class="modal-close-btn" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="theme.js"></script>
    <script>
        lucide.createIcons();

        let currentFilter = 'all';

        function filterOrders(status, btn) {
            currentFilter = status;
            document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            applyFilters();
        }

        function searchOrders() { applyFilters(); }

        function applyFilters() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.order-row').forEach(row => {
                const matchStatus = currentFilter === 'all' || row.dataset.status === currentFilter;
                const matchSearch = row.dataset.search.includes(search);
                row.style.display = matchStatus && matchSearch ? '' : 'none';
            });
        }

        function closeModal() {
            document.getElementById('detailModal').classList.remove('open');
        }

        function printReceipt() {
            window.print();
        }

        function confirmStatusChange(select) {
            const newStatus = select.value;
            const oldStatus = select.getAttribute('data-original') || select.value;
            if (newStatus === oldStatus) return;
            const labels = { completed: 'Completed', cancelled: 'Cancelled', pending: 'Pending' };
            if (confirm(`Change order status to "${labels[newStatus]}"?`)) {
                select.form.submit();
            } else {
                select.value = oldStatus;
            }
        }

        function exportCSV() {
            const rows = [['Order #','Cashier','Date','Amount','Status']];
            document.querySelectorAll('.order-row').forEach(row => {
                const cells = row.querySelectorAll('td');
                rows.push([
                    cells[0].innerText.trim(),
                    cells[1].innerText.trim(),
                    cells[2].innerText.trim(),
                    cells[3].innerText.trim(),
                    cells[4].innerText.trim()
                ]);
            });
            const csv = rows.map(r => r.map(c => '"'+c.replace(/"/g,'""')+'"').join(',')).join('\n');
            const blob = new Blob([csv], {type:'text/csv'});
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'orders_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
        }

        async function viewDetails(orderId) {
            document.getElementById('modalBody').innerHTML = '<div style="text-align:center;padding:30px;color:#8B8B8B;">Loading...</div>';
            document.getElementById('detailModal').classList.add('open');

            const res  = await fetch('get_order_details.php?id=' + orderId + '&format=json');
            const data = await res.json();

            const storeName  = localStorage.getItem('store_name')   || "PURR'COFFEE";
            const storeAddr  = localStorage.getItem('store_address') || '123 Coffee Street, Manila';
            const storePhone = localStorage.getItem('store_phone')   || '+63 912 345 6789';
            const storeTin   = localStorage.getItem('store_tin')     || '123-456-789-000';
            const taxRate    = parseFloat(localStorage.getItem('setting_taxRate') || '12');

            const subtotal = data.items.reduce((s, i) => s + i.price * i.qty, 0);
            const tax      = subtotal * (taxRate / 100);
            const total    = subtotal + tax;

            const now     = new Date(data.order.created_at);
            const dateStr = now.toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
            const timeStr = now.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' });

            let itemsHtml = '';
            data.items.forEach(i => {
                itemsHtml += `
                <div class="receipt-row">
                    <span class="receipt-row-name">${i.name}</span>
                    <span style="font-size:10px;color:#666;margin:0 8px;">${i.qty}x</span>
                    <span class="receipt-row-price">₱${(i.price * i.qty).toFixed(2)}</span>
                </div>
                <div style="font-size:10px;color:#666;margin-bottom:6px;padding-left:2px;">@ ₱${parseFloat(i.price).toFixed(2)} each</div>`;
            });

            document.getElementById('modalBody').innerHTML = `
            <div class="receipt">
                <div class="receipt-head">
                    <div class="receipt-store">${storeName.toUpperCase()}</div>
                    <div class="receipt-sub">${storeAddr}<br>${storePhone}<br>TIN: ${storeTin}</div>
                </div>
                <div class="receipt-meta">
                    <div>Order #: <strong>${data.order_number}</strong></div>
                    <div>Date: ${dateStr} ${timeStr}</div>
                    <div>Status: <strong>${data.order.status ? data.order.status.charAt(0).toUpperCase() + data.order.status.slice(1) : ''}</strong></div>
                </div>
                <div class="receipt-items">${itemsHtml}</div>
                <div class="receipt-totals">
                    <div class="receipt-total-line"><span>Subtotal:</span><span>₱${subtotal.toFixed(2)}</span></div>
                    <div class="receipt-total-line"><span>Tax (${taxRate}%):</span><span>₱${tax.toFixed(2)}</span></div>
                    <div class="receipt-total-line final"><span>TOTAL:</span><span>₱${total.toFixed(2)}</span></div>
                </div>
                <div class="receipt-footer">
                    <div style="font-weight:900;font-size:14px;">THANK YOU!</div>
                    <div>Please come again</div>
                    <div style="margin-top:6px;">www.purrcoffee.com</div>
                </div>
            </div>`;
        }
    </script>
</body>
</html>
