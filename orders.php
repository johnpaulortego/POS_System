<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Fetch orders for the logged-in user
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM orders WHERE cashier_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | Purr'Coffee</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --orange: #F28B50;
            --light-orange: #FFF5F0;
            --bg: #F5F5F5;
            --white: #FAFAFA;
            --text-main: #2D2D2D;
            --text-muted: #8B8B8B;
            --border: #BEBEBE;
            --green: #27AE60;
            --card-bg: #FFFFFF;
            --input-bg: #FFFFFF;
            --table-hover: #F8F8F8;
            --shadow: rgba(0, 0, 0, 0.08);
        }

        [data-theme="dark"] {
            --bg: #1A1A1A;
            --white: #2A2A2A;
            --text-main: #FFFFFF;
            --text-muted: #AAAAAA;
            --border: #444444;
            --card-bg: #2A2A2A;
            --input-bg: #333333;
            --table-hover: #333333;
            --shadow: rgba(0, 0, 0, 0.3);
        }

        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-font-smoothing: antialiased; }
        body { margin: 0; background: var(--bg); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; transition: background 0.3s ease, color 0.3s ease; }

        /* --- CONSISTENT SIDEBAR --- */
        .sidebar-left { width: 160px; background: var(--white); padding: 25px 18px; display: flex; flex-direction: column; border-right: 2px solid var(--border); transition: background 0.3s ease; }
        .logo { font-size: 18px; font-weight: 700; margin-bottom: 50px; }
        .logo span { color: var(--orange); }
        .nav-group { flex: 0; margin-bottom: 60px; }
        .nav-item { display: flex; align-items: center; padding: 10px 14px; text-decoration: none; color: var(--text-muted); font-weight: 500; margin-bottom: 8px; transition: 0.2s; position: relative; font-size: 13px; }
        .nav-item i { margin-right: 10px; width: 18px; height: 18px; }
        .nav-item.active { color: var(--orange); font-weight: 600; }
        .nav-item.active::after { content: ''; position: absolute; right: -18px; top: 50%; transform: translateY(-50%); width: 4px; height: 28px; background: var(--orange); border-radius: 2px 0 0 2px; }
        .nav-item:hover { color: var(--orange); }
        
        /* Theme Toggle */
        .theme-toggle { display: flex; align-items: center; padding: 10px 14px; cursor: pointer; color: var(--text-muted); font-weight: 500; margin-bottom: 8px; transition: 0.2s; font-size: 13px; }
        .theme-toggle:hover { color: var(--orange); }
        .theme-toggle i { width: 18px; height: 18px; margin-right: 10px; }

        /* --- CONTENT AREA --- */
        .content-main { flex: 1; padding: 35px 50px; overflow-y: auto; }
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 28px; font-weight: 800; margin: 0; }
        
        /* Stats Cards */
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card-bg); border: 2px solid var(--border); border-radius: 18px; padding: 20px; transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px var(--shadow); }
        .stat-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .stat-value { font-size: 28px; font-weight: 800; color: var(--text-main); }
        .stat-icon { width: 40px; height: 40px; background: var(--light-orange); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .stat-icon svg { width: 20px; height: 20px; color: var(--orange); stroke: var(--orange); }
        [data-theme="dark"] .stat-icon { background: rgba(242, 139, 80, 0.15); }
        [data-theme="dark"] .stat-icon svg { color: var(--orange); stroke: var(--orange); }
        
        /* Filter Bar */
        .filter-bar { display: flex; gap: 15px; margin-bottom: 25px; align-items: center; }
        .filter-tabs { display: flex; gap: 8px; flex: 1; }
        .filter-tab { padding: 10px 20px; border-radius: 20px; border: 2px solid var(--border); background: var(--card-bg); font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; color: var(--text-main); }
        .filter-tab:hover { border-color: var(--orange); }
        .filter-tab.active { background: var(--orange); color: white; border-color: var(--orange); }
        .search-box { flex: 1; max-width: 300px; background: var(--input-bg); border-radius: 20px; display: flex; align-items: center; padding: 10px 18px; border: 2px solid var(--border); }
        .search-box input { border: none; outline: none; width: 100%; font-size: 13px; background: transparent; color: var(--text-main); }
        .search-box input::placeholder { color: var(--text-muted); }
        .search-box i { width: 16px; height: 16px; color: var(--text-muted); margin-right: 8px; }
        .refresh-btn { padding: 10px 18px; border-radius: 20px; border: 2px solid var(--border); background: var(--card-bg); font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; color: var(--text-main); display: flex; align-items: center; gap: 6px; }
        .refresh-btn:hover { background: var(--light-orange); border-color: var(--orange); color: var(--orange); }
        .refresh-btn i { width: 16px; height: 16px; }
        
        /* --- ORDERS TABLE --- */
        .orders-container { background: var(--card-bg); border-radius: 24px; border: 2px solid var(--border); overflow: hidden; transition: background 0.3s ease; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 20px 25px; background: var(--table-hover); font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid var(--border); }
        td { padding: 20px 25px; font-size: 14px; border-bottom: 1px solid var(--border); color: var(--text-main); }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: var(--table-hover); cursor: pointer; }
        
        .order-id { font-weight: 700; color: var(--text-main); }
        .status-pill { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .status-pending { background: #FFF9E6; color: #D4A017; }
        .status-completed { background: #E6F7ED; color: var(--green); }
        [data-theme="dark"] .status-pending { background: #3D3520; color: #FFD700; }
        [data-theme="dark"] .status-completed { background: #1A3D2A; color: #4ADE80; }
        
        .price-col { font-weight: 800; color: var(--text-main); }
        .date-col { color: var(--text-muted); font-size: 13px; }
        
        .view-btn { padding: 8px 16px; border-radius: 10px; border: 2px solid var(--border); background: var(--card-bg); font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; color: var(--text-main); }
        .view-btn:hover { background: var(--light-orange); border-color: var(--orange); color: var(--orange); }
        .complete-btn { padding: 8px 16px; border-radius: 10px; border: 2px solid var(--green); background: transparent; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; color: var(--green); margin-right: 6px; }
        .complete-btn:hover { background: var(--green); color: white; }
        .complete-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 999; justify-content: center; align-items: center; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: var(--card-bg); border-radius: 24px; border: 2px solid var(--border); padding: 30px; width: 400px; max-width: 90vw; max-height: 90vh; overflow-y: auto; }

        /* Receipt inside modal */
        .receipt { font-family: 'Courier New', monospace; font-size: 13px; color: #000; background: #fff; padding: 20px; border-radius: 12px; border: 2px dashed #ccc; }
        [data-theme="dark"] .receipt { color: #000; background: #fff; }
        .receipt-head { text-align: center; padding-bottom: 12px; border-bottom: 2px dashed #000; margin-bottom: 12px; }
        .receipt-store { font-size: 17px; font-weight: 900; letter-spacing: 1px; }
        .receipt-sub { font-size: 11px; line-height: 1.5; margin-top: 4px; color: #444; }
        .receipt-meta { font-size: 11px; line-height: 1.8; margin-bottom: 12px; }
        .receipt-items { border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 10px 0; margin-bottom: 12px; }
        .receipt-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px; }
        .receipt-row-name { flex: 1; font-weight: 700; }
        .receipt-row-qty { color: #555; font-size: 11px; margin-left: 8px; }
        .receipt-row-price { font-weight: 700; min-width: 70px; text-align: right; }
        .receipt-totals { font-size: 12px; }
        .receipt-total-line { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .receipt-total-line.final { font-size: 15px; font-weight: 900; border-top: 2px solid #000; padding-top: 8px; margin-top: 8px; }
        .receipt-footer { text-align: center; border-top: 2px dashed #000; padding-top: 12px; margin-top: 12px; font-size: 11px; line-height: 1.8; }
        .receipt-thankyou { font-size: 14px; font-weight: 900; }

        .modal-actions { display: flex; gap: 10px; margin-top: 16px; }
        .modal-print-btn { flex: 1; padding: 12px; border-radius: 12px; border: none; background: var(--orange); color: white; font-weight: 700; font-size: 14px; cursor: pointer; }
        .modal-close { flex: 1; padding: 12px; border-radius: 12px; border: 2px solid var(--border); background: transparent; font-weight: 700; font-size: 14px; cursor: pointer; color: var(--text-main); transition: 0.2s; }
        .modal-close:hover { border-color: var(--orange); color: var(--orange); }

        /* Print receipt from modal */
        @media print {
            body * { visibility: hidden !important; }
            .receipt, .receipt * { visibility: visible !important; }
            .receipt {
                display: block !important;
                position: fixed !important;
                inset: 0 !important;
                width: 100% !important;
                height: 100% !important;
                padding: 40px !important;
                font-size: 16px !important;
                font-family: 'Courier New', monospace !important;
                color: #000 !important;
                background: #fff !important;
                border: none !important;
            }
            .receipt-store { font-size: 28px !important; }
            .receipt-sub { font-size: 15px !important; }
            .receipt-meta { font-size: 15px !important; }
            .receipt-row { font-size: 16px !important; }
            .receipt-total-line { font-size: 16px !important; }
            .receipt-total-line.final { font-size: 22px !important; }
            .receipt-footer { font-size: 15px !important; }
            .receipt-thankyou { font-size: 22px !important; }
            .modal-actions { display: none !important; }
        }
        .empty-state { text-align: center; padding: 100px 0; color: var(--text-muted); }
        .empty-state i { width: 64px; height: 64px; margin-bottom: 20px; opacity: 0.3; }
        .empty-state h3 { font-size: 22px; font-weight: 700; margin-bottom: 10px; color: var(--text-main); }
        .empty-state p { font-size: 14px; margin-bottom: 25px; }
        .empty-state a { display: inline-block; padding: 12px 28px; background: var(--orange); color: white; text-decoration: none; border-radius: 20px; font-weight: 700; font-size: 14px; transition: 0.2s; }
        .empty-state a:hover { transform: scale(1.05); box-shadow: 0 8px 16px rgba(242, 139, 80, 0.3); }
    </style>
</head>
<body>

    <div class="sidebar-left">
        <div class="logo">Purr'<span>Coffee</span></div>
        <div class="nav-group">
            <a href="pos.php" class="nav-item"><i data-lucide="coffee"></i> Menu</a>
            <a href="orders.php" class="nav-item active"><i data-lucide="clipboard-list"></i> Orders</a>
            <a href="reports.php" class="nav-item"><i data-lucide="bar-chart-3"></i> Reports</a>
        </div>
        <div style="margin-top: auto;">
            <div class="theme-toggle" onclick="toggleTheme()">
                <i data-lucide="moon" id="themeIcon"></i>
                <span id="themeText">Dark Mode</span>
            </div>
            <a href="settings.php" class="nav-item"><i data-lucide="settings"></i> Settings</a>
            <a href="logout.php" class="nav-item"><i data-lucide="log-out"></i> Log out</a>
        </div>
    </div>

    <div class="content-main">
        <div class="header-row">
            <h1 class="page-title">My Orders</h1>
        </div>

        <?php 
        $totalOrders = count($orders);
        $completedOrders = 0;
        foreach ($orders as $order) {
            if (isset($order['status']) && $order['status'] == 'completed') {
                $completedOrders++;
            }
        }
        $totalSpent = 0;
        foreach ($orders as $order) {
            if (isset($order['total_amount'])) {
                $totalSpent += $order['total_amount'];
            }
        }
        ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="shopping-bag"></i></div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= $totalOrders ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="check-circle"></i></div>
                <div class="stat-label">Completed</div>
                <div class="stat-value"><?= $completedOrders ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="wallet"></i></div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">₱<?= number_format($totalSpent, 2) ?></div>
            </div>
        </div>

        <div class="filter-bar">
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterOrders('all', this)">All Orders</button>
                <button class="filter-tab" onclick="filterOrders('pending', this)">Processing</button>
                <button class="filter-tab" onclick="filterOrders('completed', this)">Completed</button>
            </div>
            <div class="search-box">
                <i data-lucide="search"></i>
                <input type="text" id="orderSearch" placeholder="Search by Order ID..." onkeyup="searchOrders()">
            </div>
            <button class="refresh-btn" onclick="location.reload()">
                <i data-lucide="refresh-cw"></i> Refresh
            </button>
        </div>

        <?php if (count($orders) > 0): ?>
        <div class="orders-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr class="order-row" data-status="<?= isset($order['status']) ? $order['status'] : 'pending' ?>" data-id="<?= $order['id'] ?>">
                        <td class="order-id">#<?= isset($order['order_number']) && $order['order_number'] ? $order['order_number'] : str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                        <td class="date-col"><?= date('M d, Y • h:i A', strtotime($order['created_at'])) ?></td>
                        <td>
                            <?php if (isset($order['status']) && $order['status'] == 'completed'): ?>
                                <span class="status-pill status-completed"><i data-lucide="check-circle" style="width:14px;"></i> Completed</span>
                            <?php else: ?>
                                <span class="status-pill status-pending"><i data-lucide="clock" style="width:14px;"></i> Processing</span>
                            <?php endif; ?>
                        </td>
                        <td class="price-col">₱<?= number_format(isset($order['total_amount']) ? $order['total_amount'] : 0, 2) ?></td>
                        <td>
                            <?php if (!isset($order['status']) || $order['status'] !== 'completed'): ?>
                                <button class="complete-btn" onclick="markComplete(<?= $order['id'] ?>, this); event.stopPropagation();">✓ Complete</button>
                            <?php endif; ?>
                            <button class="view-btn" onclick="viewDetails(<?= $order['id'] ?>); event.stopPropagation();">View Receipt</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i data-lucide="coffee"></i>
            <h3>No orders yet</h3>
            <p>Your recent coffee runs will appear here.</p>
            <a href="pos.php">Go to Menu →</a>
        </div>
        <?php endif; ?>
    </div>

    <div class="modal-overlay" id="detailModal" onclick="if(event.target===this)closeModal()">
        <div class="modal-box">
            <div id="modalBody"></div>
            <div class="modal-actions">
                <button class="modal-print-btn" onclick="printReceipt()">🖨️ Print</button>
                <button class="modal-close" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="theme.js"></script>
    <script>
        lucide.createIcons();

        function filterOrders(status, btn) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
            btn.classList.add('active');

            // Filter rows
            document.querySelectorAll('.order-row').forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                if (status === 'all' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function searchOrders() {
            const input = document.getElementById('orderSearch').value.toLowerCase();
            document.querySelectorAll('.order-row').forEach(row => {
                const orderId = row.getAttribute('data-id');
                const orderIdText = row.querySelector('.order-id').innerText.toLowerCase();
                if (orderIdText.includes(input) || orderId.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function closeModal() {
            document.getElementById('detailModal').classList.remove('open');
        }

        function printReceipt() {
            const receipt = document.querySelector('.receipt');
            if (!receipt) return;
            receipt.style.display = 'block';
            window.print();
        }

        async function viewDetails(orderId) {
            document.getElementById('modalBody').innerHTML = '<div style="text-align:center;padding:30px;color:#8B8B8B;">Loading...</div>';
            document.getElementById('detailModal').classList.add('open');

            const res = await fetch('get_order_details.php?id=' + orderId + '&format=json');
            const data = await res.json();

            const storeName  = localStorage.getItem('store_name')    || "PURR'COFFEE";
            const storeAddr  = localStorage.getItem('store_address')  || '123 Coffee Street, Manila';
            const storePhone = localStorage.getItem('store_phone')    || '+63 912 345 6789';
            const storeTin   = localStorage.getItem('store_tin')      || '123-456-789-000';
            const taxRate    = parseFloat(localStorage.getItem('setting_taxRate') || '12');

            const subtotal = data.items.reduce((s, i) => s + i.price * i.qty, 0);
            const tax      = subtotal * (taxRate / 100);
            const total    = subtotal + tax;

            const now = new Date(data.order.created_at);
            const dateStr = now.toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
            const timeStr = now.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' });

            let itemsHtml = '';
            data.items.forEach(i => {
                itemsHtml += `
                <div class="receipt-row">
                    <span class="receipt-row-name">${i.name}</span>
                    <span class="receipt-row-qty">${i.qty}x</span>
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
                    <div>Date: ${dateStr}</div>
                    <div>Time: ${timeStr}</div>
                    <div>Cashier: <?= htmlspecialchars($_SESSION['username']) ?></div>
                    <div>Status: <strong>${data.order.status === 'completed' ? 'Completed' : 'Processing'}</strong></div>
                </div>
                <div class="receipt-items">${itemsHtml}</div>
                <div class="receipt-totals">
                    <div class="receipt-total-line"><span>Subtotal:</span><span>₱${subtotal.toFixed(2)}</span></div>
                    <div class="receipt-total-line"><span>Tax (${taxRate}%):</span><span>₱${tax.toFixed(2)}</span></div>
                    <div class="receipt-total-line final"><span>TOTAL:</span><span>₱${total.toFixed(2)}</span></div>
                </div>
                <div class="receipt-footer">
                    <div class="receipt-thankyou">THANK YOU!</div>
                    <div>Please come again</div>
                    <div style="margin-top:6px;">www.purrcoffee.com</div>
                </div>
            </div>`;
        }

        async function markComplete(orderId, btn) {
            if (!confirm('Mark this order as completed?')) return;
            
            btn.disabled = true;
            btn.innerText = 'Updating...';
            
            try {
                const formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('status', 'completed');
                
                const response = await fetch('update_order_status.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    // Update UI without page reload
                    const row = btn.closest('tr');
                    row.setAttribute('data-status', 'completed');
                    
                    // Update status pill
                    const statusCell = row.querySelector('td:nth-child(3)');
                    statusCell.innerHTML = '<span class="status-pill status-completed"><i data-lucide="check-circle" style="width:14px;"></i> Completed</span>';
                    
                    // Remove the complete button
                    btn.remove();
                    
                    // Refresh icons
                    lucide.createIcons();
                    
                    // Update stats
                    location.reload();
                } else {
                    alert('Failed to update order status');
                    btn.disabled = false;
                    btn.innerText = '✓ Complete';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating order status');
                btn.disabled = false;
                btn.innerText = '✓ Complete';
            }
        }
    </script>
</body>
</html>
