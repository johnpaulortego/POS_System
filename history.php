<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
// Fetch only 'completed' or 'cancelled' orders for history
$query = "SELECT * FROM orders WHERE cashier_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | Purr'Coffee</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --orange: #F28B50;
            --light-orange: #FFF5F0;
            --bg: #EEEEEE;
            --white: #FFFFFF;
            --text-main: #2D2D2D;
            --text-muted: #8B8B8B;
            --border: #D0D0D0;
            --red: #EB5757;
            --card-bg: #FFFFFF;
            --input-bg: #FFFFFF;
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
            --shadow: rgba(0, 0, 0, 0.3);
        }

        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-font-smoothing: antialiased; }
        body { margin: 0; background: var(--bg); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; transition: background 0.3s ease, color 0.3s ease; }

        /* --- SIDEBAR (Consistent) --- */
        .sidebar-left { width: 160px; background: var(--white); padding: 25px 18px; display: flex; flex-direction: column; border-right: 1px solid var(--border); transition: background 0.3s ease; }
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

        /* --- CONTENT --- */
        .content-main { flex: 1; padding: 35px 50px; overflow-y: auto; }
        .header-row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 35px; }
        .page-title { font-size: 28px; font-weight: 800; margin: 0; }
        
        /* Search/Filter Bar */
        .history-tools { display: flex; gap: 15px; margin-bottom: 25px; align-items: center; }
        .filter-tabs { display: flex; gap: 8px; }
        .filter-tab { padding: 10px 20px; border-radius: 20px; border: 2px solid var(--border); background: var(--card-bg); font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; color: var(--text-main); }
        .filter-tab:hover { border-color: var(--orange); }
        .filter-tab.active { background: var(--orange); color: white; border-color: var(--orange); }
        .search-box { flex: 1; max-width: 300px; background: var(--card-bg); border: 2px solid var(--border); border-radius: 20px; padding: 10px 18px; display: flex; align-items: center; gap: 10px; transition: background 0.3s ease; }
        .search-box input { border: none; outline: none; width: 100%; font-size: 13px; background: transparent; color: var(--text-main); }
        .search-box input::placeholder { color: var(--text-muted); }
        .search-box i { width: 16px; height: 16px; color: var(--text-muted); }
        .export-btn { padding: 10px 18px; border-radius: 20px; border: 2px solid var(--border); background: var(--card-bg); font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; color: var(--text-main); display: flex; align-items: center; gap: 6px; }
        .export-btn:hover { background: var(--light-orange); border-color: var(--orange); color: var(--orange); }
        .export-btn i { width: 16px; height: 16px; }
        
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

        /* History Cards */
        .history-card { background: var(--card-bg); border-radius: 20px; border: 2px solid var(--border); padding: 20px; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between; transition: 0.2s; }
        .history-card:hover { border-color: var(--orange); transform: translateX(5px); box-shadow: 0 4px 12px var(--shadow); }
        
        .info-group { display: flex; align-items: center; gap: 20px; }
        .icon-circle { width: 50px; height: 50px; background: var(--light-orange); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .icon-circle svg { width: 24px; height: 24px; color: var(--orange); stroke: var(--orange); }
        [data-theme="dark"] .icon-circle { background: rgba(242, 139, 80, 0.15); }
        
        .details h4 { margin: 0 0 6px 0; font-size: 16px; font-weight: 800; }
        .details p { margin: 0; font-size: 12px; color: var(--text-muted); }

        .price-status { text-align: right; }
        .price { font-size: 18px; font-weight: 800; display: block; margin-bottom: 8px; color: var(--text-main); }
        .status-pill { padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
        .status-completed { background: #E6F7ED; color: #27AE60; }
        .status-pending { background: #FFF9E6; color: #D4A017; }
        .status-cancelled { background: #FFE6E6; color: var(--red); }
        [data-theme="dark"] .status-completed { background: #1A3D2A; color: #4ADE80; }
        [data-theme="dark"] .status-pending { background: #3D3520; color: #FFD700; }
        [data-theme="dark"] .status-cancelled { background: #3D1A1A; color: #FF6B6B; }

        .reorder-btn { background: transparent; border: 2px solid var(--border); padding: 8px 16px; border-radius: 12px; font-weight: 700; font-size: 12px; cursor: pointer; margin-left: 25px; transition: 0.2s; color: var(--text-main); }
        .reorder-btn:hover { background: var(--orange); color: white; border-color: var(--orange); }
        
        /* Empty State */
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
            <a href="pos.php" class="nav-item"><i data-lucide="menu"></i> Menu</a>
            <a href="orders.php" class="nav-item"><i data-lucide="bookmark"></i> My orders</a>
            <a href="history.php" class="nav-item active"><i data-lucide="clock"></i> History</a>
            <div style="height: 60px;"></div>
            <a href="settings.php" class="nav-item"><i data-lucide="settings"></i> Settings</a>
        </div>
        <div class="theme-toggle" onclick="toggleTheme()">
            <i data-lucide="moon" id="themeIcon"></i>
            <span id="themeText">Dark Mode</span>
        </div>
        <a href="logout.php" class="nav-item"><i data-lucide="log-out"></i> Log out</a>
    </div>

    <div class="content-main">
        <div class="header-row">
            <div>
                <h1 class="page-title">Order History</h1>
                <p style="color: var(--text-muted); margin: 5px 0 0 0; font-size: 14px;">Review your past transactions.</p>
            </div>
        </div>

        <?php 
        $totalHistory = count($history);
        $completedOrders = 0;
        $totalRevenue = 0;
        foreach ($history as $order) {
            if (isset($order['status']) && $order['status'] == 'completed') {
                $completedOrders++;
            }
            if (isset($order['total_amount'])) {
                $totalRevenue += $order['total_amount'];
            }
        }
        ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="archive"></i></div>
                <div class="stat-label">Total History</div>
                <div class="stat-value"><?= $totalHistory ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="check-circle"></i></div>
                <div class="stat-label">Completed</div>
                <div class="stat-value"><?= $completedOrders ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i data-lucide="trending-up"></i></div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">₱<?= number_format($totalRevenue, 2) ?></div>
            </div>
        </div>

        <div class="history-tools">
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterHistory('all', this)">All</button>
                <button class="filter-tab" onclick="filterHistory('completed', this)">Completed</button>
                <button class="filter-tab" onclick="filterHistory('pending', this)">Pending</button>
            </div>
            <div class="search-box">
                <i data-lucide="search"></i>
                <input type="text" id="histSearch" placeholder="Search by Order ID..." onkeyup="searchHistory()">
            </div>
            <button class="export-btn" onclick="exportHistory()">
                <i data-lucide="download"></i> Export
            </button>
        </div>

        <div id="historyList">
            <?php if (count($history) > 0): ?>
                <?php foreach ($history as $row): 
                    $status = isset($row['status']) ? $row['status'] : 'pending';
                    $statusClass = $status == 'completed' ? 'status-completed' : ($status == 'cancelled' ? 'status-cancelled' : 'status-pending');
                ?>
                <div class="history-card" data-status="<?= $status ?>" data-search="<?= strtolower($row['id'] . ' ' . date('F d Y', strtotime($row['created_at']))) ?>">
                    <div class="info-group">
                        <div class="icon-circle">
                            <i data-lucide="shopping-bag"></i>
                        </div>
                        <div class="details">
                            <h4>Order #<?= str_pad($row['id'], 5, '0', STR_PAD_LEFT) ?></h4>
                            <p><?= date('F d, Y • h:i A', strtotime($row['created_at'])) ?></p>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center;">
                        <div class="price-status">
                            <span class="price">₱<?= number_format(isset($row['total_amount']) ? $row['total_amount'] : 0, 2) ?></span>
                            <span class="status-pill <?= $statusClass ?>">
                                <?= ucfirst($status) ?>
                            </span>
                        </div>
                        <button class="reorder-btn" onclick="viewDetails(<?= $row['id'] ?>)">View Details</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i data-lucide="calendar-x"></i>
                    <h3>No history yet</h3>
                    <p>Your completed orders will appear here.</p>
                    <a href="pos.php">Go to Menu →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="theme.js"></script>
    <script>
        lucide.createIcons();

        function filterHistory(status, btn) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
            btn.classList.add('active');

            // Filter cards
            document.querySelectorAll('.history-card').forEach(card => {
                const cardStatus = card.getAttribute('data-status');
                if (status === 'all' || cardStatus === status) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function searchHistory() {
            let term = document.getElementById('histSearch').value.toLowerCase();
            document.querySelectorAll('.history-card').forEach(card => {
                let searchData = card.getAttribute('data-search');
                if (searchData.includes(term)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function viewDetails(orderId) {
            // Redirect to view order details
            window.location.href = 'view_orders.php?id=' + orderId;
        }

        function exportHistory() {
            // Export history to CSV
            alert('Exporting history to CSV...');
            // You can implement CSV export functionality here
        }
    </script>
</body>
</html>