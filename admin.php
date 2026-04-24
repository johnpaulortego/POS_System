<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Stats
$total_sales    = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders")->fetchColumn();
$total_orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$today_sales    = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$today_orders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Top selling products
$top_products = $pdo->query("
    SELECT p.name, SUM(oi.quantity) as total_qty, SUM(oi.quantity * oi.price_at_sale) as revenue
    FROM order_items oi JOIN products p ON oi.product_id = p.id
    GROUP BY p.id, p.name ORDER BY total_qty DESC LIMIT 5
")->fetchAll();

// Recent orders
$recent_orders = $pdo->query("
    SELECT o.*, u.username FROM orders o 
    JOIN users u ON o.cashier_id = u.id 
    ORDER BY o.created_at DESC LIMIT 6
")->fetchAll();

// 7-day daily revenue chart data
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at)=?");
    $stmt->execute([$date]);
    $chartData[] = ['label' => $label, 'value' => floatval($stmt->fetchColumn()), 'date' => $date];
}
$chartMax = max(max(array_column($chartData, 'value')), 1);

// Cashier performance
$cashier_stats = $pdo->query("
    SELECT u.username, COUNT(o.id) as order_count, COALESCE(SUM(o.total_amount),0) as revenue
    FROM users u LEFT JOIN orders o ON o.cashier_id = u.id
    GROUP BY u.id, u.username ORDER BY revenue DESC LIMIT 5
")->fetchAll();

// Avg order value
$avg_order = $total_orders > 0 ? $total_sales / $total_orders : 0;

// Best selling product name
$best_product = !empty($top_products) ? $top_products[0]['name'] : 'N/A';

// Completion rate
$completed = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='completed'")->fetchColumn();
$completion_rate = $total_orders > 0 ? round(($completed / $total_orders) * 100) : 0;

// Low stock products (stock IS NOT NULL AND stock <= 5)
try {
    $low_stock = $pdo->query("SELECT id, name, stock FROM products WHERE stock IS NOT NULL AND stock <= 5 ORDER BY stock ASC LIMIT 10")->fetchAll();
} catch (Exception $e) {
    $low_stock = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Purr'Coffee</title>
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
            --card-bg: #FFFFFF;
            --table-hover: #F8F8F8;
            --shadow: rgba(0,0,0,0.08);
            --green: #27AE60;
            --blue: #3498DB;
            --purple: #9B59B6;
        }
        [data-theme="dark"] {
            --bg: #1A1A1A; --white: #2A2A2A; --text-main: #FFFFFF;
            --text-muted: #AAAAAA; --border: #444444; --card-bg: #2A2A2A;
            --table-hover: #333333; --shadow: rgba(0,0,0,0.3);
        }
        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; transition: background 0.3s ease; }

        /* SIDEBAR */
        .sidebar-left { width: 160px; background: var(--white); padding: 25px 18px; display: flex; flex-direction: column; border-right: 2px solid var(--border); transition: background 0.3s ease; flex-shrink: 0; }
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
        .page-header { margin-bottom: 30px; }
        .page-title { font-size: 26px; font-weight: 800; }
        .page-sub { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        /* STAT CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 28px; }
        .stat-card { background: var(--card-bg); border: 2px solid var(--border); border-radius: 18px; padding: 22px; transition: 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px var(--shadow); }
        .stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
        .stat-icon i { width: 20px; height: 20px; }
        .ic-orange { background: var(--light-orange); color: var(--orange); }
        .ic-green  { background: #E6F7ED; color: var(--green); }
        .ic-blue   { background: #EBF5FB; color: var(--blue); }
        .ic-purple { background: #F5EEF8; color: var(--purple); }
        [data-theme="dark"] .ic-orange { background: rgba(242,139,80,0.15); }
        [data-theme="dark"] .ic-green  { background: rgba(39,174,96,0.15); }
        [data-theme="dark"] .ic-blue   { background: rgba(52,152,219,0.15); }
        [data-theme="dark"] .ic-purple { background: rgba(155,89,182,0.15); }
        .stat-value { font-size: 24px; font-weight: 800; margin-bottom: 4px; }
        .stat-label { font-size: 12px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }

        /* TWO COL LAYOUT */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .full-col { margin-bottom: 20px; }

        /* CARDS */
        .card { background: var(--card-bg); border: 2px solid var(--border); border-radius: 18px; overflow: hidden; }
        .card-head { padding: 20px 25px; border-bottom: 2px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 15px; font-weight: 700; }
        .card-link { font-size: 12px; font-weight: 700; color: var(--orange); text-decoration: none; }
        .card-link:hover { text-decoration: underline; }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th { padding: 14px 22px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; background: var(--bg); border-bottom: 2px solid var(--border); text-align: left; }
        td { padding: 16px 22px; font-size: 13px; border-bottom: 1px solid var(--border); color: var(--text-main); }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: var(--table-hover); }
        .status-pill { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .status-completed { background: #E6F7ED; color: var(--green); }
        .status-pending   { background: #FFF9E6; color: #D4A017; }
        .status-cancelled { background: #FDEDEC; color: #E74C3C; }
        [data-theme="dark"] .status-completed { background: #1A3D2A; color: #4ADE80; }
        [data-theme="dark"] .status-pending   { background: #3D3520; color: #FFD700; }

        /* TOP PRODUCTS */
        .product-row { display: flex; align-items: center; padding: 14px 22px; border-bottom: 1px solid var(--border); gap: 14px; }
        .product-row:last-child { border-bottom: none; }
        .product-rank { width: 26px; height: 26px; border-radius: 8px; background: var(--light-orange); color: var(--orange); font-size: 12px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .product-rank.gold   { background: #FFF8E1; color: #F59E0B; }
        .product-rank.silver { background: #F3F4F6; color: #6B7280; }
        .product-rank.bronze { background: #FEF3E2; color: #D97706; }
        .product-name { flex: 1; font-weight: 600; font-size: 13px; }
        .product-qty  { font-size: 12px; color: var(--text-muted); }
        .product-rev  { font-weight: 700; font-size: 13px; color: var(--orange); }

        /* RECOMMENDATIONS */
        .rec-list { padding: 8px 0; }
        .rec-item { display: flex; align-items: flex-start; gap: 14px; padding: 16px 22px; border-bottom: 1px solid var(--border); }
        .rec-item:last-child { border-bottom: none; }
        .rec-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px; }
        .rec-icon i { width: 16px; height: 16px; }
        .rec-body { flex: 1; }
        .rec-title { font-size: 13px; font-weight: 700; margin-bottom: 3px; }
        .rec-desc  { font-size: 12px; color: var(--text-muted); line-height: 1.5; }
        .rec-badge { font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 20px; margin-left: auto; flex-shrink: 0; align-self: flex-start; margin-top: 2px; }
        .badge-tip  { background: #EBF5FB; color: var(--blue); }
        .badge-warn { background: #FFF9E6; color: #D4A017; }
        .badge-good { background: #E6F7ED; color: var(--green); }
        [data-theme="dark"] .badge-tip  { background: rgba(52,152,219,0.15); }
        [data-theme="dark"] .badge-warn { background: rgba(212,160,23,0.15); }
        [data-theme="dark"] .badge-good { background: rgba(39,174,96,0.15); }
        /* BAR CHART */
        .bar-chart { display: flex; align-items: flex-end; gap: 8px; height: 140px; padding: 0 4px; }
        .bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end; }
        .bar { width: 100%; background: var(--orange); border-radius: 6px 6px 0 0; min-height: 4px; transition: 0.3s; opacity: 0.85; cursor: pointer; position: relative; }
        .bar:hover { opacity: 1; }
        .bar-tooltip { position: absolute; bottom: 105%; left: 50%; transform: translateX(-50%); background: var(--text-main); color: var(--white); font-size: 10px; font-weight: 700; padding: 3px 7px; border-radius: 6px; white-space: nowrap; display: none; }
        .bar:hover .bar-tooltip { display: block; }
        .bar-label { font-size: 10px; color: var(--text-muted); font-weight: 600; }
        .bar-today { background: var(--green); opacity: 1; }
        /* CASHIER TABLE */
        .cashier-row { display: flex; align-items: center; padding: 12px 22px; border-bottom: 1px solid var(--border); gap: 12px; }
        .cashier-row:last-child { border-bottom: none; }
        .cashier-avatar { width: 32px; height: 32px; border-radius: 10px; background: var(--light-orange); color: var(--orange); font-size: 13px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        [data-theme="dark"] .cashier-avatar { background: rgba(242,139,80,0.15); }
        .cashier-name { flex: 1; font-weight: 600; font-size: 13px; }
        .cashier-orders { font-size: 12px; color: var(--text-muted); }
        .cashier-rev { font-weight: 700; font-size: 13px; color: var(--orange); }
        /* PENDING BADGE on sidebar */
        .nav-badge { background: var(--orange); color: white; font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 20px; margin-left: auto; }
    </style>
</head>
<body>

    <div class="sidebar-left">
        <div class="logo">Purr'<span>Coffee</span></div>
        <div class="nav-group">
            <a href="admin.php" class="nav-item active"><i data-lucide="layout-dashboard"></i> Dashboard</a>
            <a href="manage_products.php" class="nav-item"><i data-lucide="coffee"></i> Products</a>
            <a href="admin_orders.php" class="nav-item"><i data-lucide="shopping-cart"></i> Orders <?php if($pending_orders > 0): ?><span class="nav-badge"><?= $pending_orders ?></span><?php endif; ?></a>
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
            <div class="page-title">Admin Dashboard</div>
            <div class="page-sub">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?> — here's what's happening today.</div>
        </div>

        <!-- STAT CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon ic-green"><i data-lucide="banknote"></i></div>
                <div class="stat-value">₱<?= number_format($total_sales, 2) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon ic-orange"><i data-lucide="calendar"></i></div>
                <div class="stat-value">₱<?= number_format($today_sales, 2) ?></div>
                <div class="stat-label">Today's Sales</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon ic-blue"><i data-lucide="shopping-bag"></i></div>
                <div class="stat-value"><?= $total_orders ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon ic-purple"><i data-lucide="clock"></i></div>
                <div class="stat-value"><?= $pending_orders ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
        </div>

        <!-- 7-DAY CHART + CASHIER PERFORMANCE -->
        <div class="two-col" style="margin-bottom:20px;">
            <div class="card">
                <div class="card-head">
                    <div class="card-title">Revenue — Last 7 Days</div>
                    <div style="font-size:12px;color:var(--text-muted);"><?= date('M d') ?> – <?= date('M d', strtotime('-6 days')) ?></div>
                </div>
                <div style="padding:20px 22px 10px;">
                    <div class="bar-chart">
                        <?php foreach($chartData as $day): 
                            $height = $chartMax > 0 ? ($day['value'] / $chartMax) * 100 : 0;
                            $isToday = $day['date'] === date('Y-m-d');
                        ?>
                        <div class="bar-wrap">
                            <div class="bar <?= $isToday ? 'bar-today' : '' ?>" style="height:<?= max($height, 3) ?>%">
                                <div class="bar-tooltip">₱<?= number_format($day['value'],0) ?></div>
                            </div>
                            <div class="bar-label"><?= $day['label'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <div class="card-title">Cashier Performance</div>
                    <a href="admin_orders.php" class="card-link">View orders →</a>
                </div>
                <?php if(empty($cashier_stats)): ?>
                <div style="text-align:center;color:var(--text-muted);padding:40px;">No data yet</div>
                <?php else: ?>
                <?php foreach($cashier_stats as $c): ?>
                <div class="cashier-row">
                    <div class="cashier-avatar"><?= strtoupper(substr($c['username'],0,1)) ?></div>
                    <div class="cashier-name"><?= htmlspecialchars($c['username']) ?></div>
                    <div class="cashier-orders"><?= $c['order_count'] ?> orders</div>
                    <div class="cashier-rev">₱<?= number_format($c['revenue'],0) ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="two-col">
            <!-- RECENT ORDERS -->
            <div class="card">
                <div class="card-head">
                    <div class="card-title">Recent Orders</div>
                    <a href="admin_orders.php" class="card-link">View all →</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Cashier</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $o): ?>
                        <tr>
                            <td style="font-weight:700;">#<?= isset($o['order_number']) && $o['order_number'] ? $o['order_number'] : str_pad($o['id'],6,'0',STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($o['username']) ?></td>
                            <td style="font-weight:800; color:var(--orange);">₱<?= number_format($o['total_amount'],2) ?></td>
                            <td><span class="status-pill status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_orders)): ?>
                        <tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:30px;">No orders yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TOP PRODUCTS -->
            <div class="card">
                <div class="card-head">
                    <div class="card-title">Top Selling Products</div>
                    <a href="manage_products.php" class="card-link">Manage →</a>
                </div>
                <?php if(empty($top_products)): ?>
                <div style="text-align:center; color:var(--text-muted); padding:40px;">No sales data yet</div>
                <?php else: ?>
                <?php $ranks = ['gold','silver','bronze','',''];
                foreach($top_products as $i => $p): ?>
                <div class="product-row">
                    <div class="product-rank <?= $ranks[$i] ?>"><?= $i+1 ?></div>
                    <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="product-qty"><?= $p['total_qty'] ?> sold</div>
                    <div class="product-rev">₱<?= number_format($p['revenue'],0) ?></div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- LOW STOCK WARNING -->
        <?php if(!empty($low_stock)): ?>
        <div class="card full-col" style="margin-bottom:20px; border-color:#D4A017;">
            <div class="card-head" style="background:#FFF9E6;">
                <div class="card-title" style="color:#D4A017;">⚠️ Low Stock Alert</div>
                <a href="manage_products.php" class="card-link">Manage Stock →</a>
            </div>
            <div style="padding:8px 0;">
                <?php foreach($low_stock as $ls): ?>
                <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 22px; border-bottom:1px solid var(--border);">
                    <span style="font-weight:600; font-size:13px;"><?= htmlspecialchars($ls['name']) ?></span>
                    <?php if($ls['stock'] == 0): ?>
                        <span style="font-size:12px; font-weight:700; color:#EB5757;">Sold Out</span>
                    <?php else: ?>
                        <span style="font-size:12px; font-weight:700; color:#D4A017;"><?= $ls['stock'] ?> left</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- RECOMMENDATIONS -->
        <div class="card full-col">
            <div class="card-head">
                <div class="card-title">💡 Recommendations</div>
                <div style="font-size:12px; color:var(--text-muted);">Based on your store data</div>
            </div>
            <div class="rec-list">

                <?php if($pending_orders > 5): ?>
                <div class="rec-item">
                    <div class="rec-icon ic-orange"><i data-lucide="alert-circle"></i></div>
                    <div class="rec-body">
                        <div class="rec-title">High Pending Orders (<?= $pending_orders ?>)</div>
                        <div class="rec-desc">You have <?= $pending_orders ?> orders still pending. Consider marking completed orders to keep your dashboard accurate and improve customer tracking.</div>
                    </div>
                    <span class="rec-badge badge-warn">Action Needed</span>
                </div>
                <?php endif; ?>

                <?php if($today_orders == 0): ?>
                <div class="rec-item">
                    <div class="rec-icon ic-blue"><i data-lucide="sun"></i></div>
                    <div class="rec-body">
                        <div class="rec-title">No Sales Today Yet</div>
                        <div class="rec-desc">You haven't recorded any orders today. Make sure the POS is active and staff are logged in. Consider running a daily special to boost traffic.</div>
                    </div>
                    <span class="rec-badge badge-tip">Tip</span>
                </div>
                <?php elseif($today_sales > 0): ?>
                <div class="rec-item">
                    <div class="rec-icon ic-green"><i data-lucide="trending-up"></i></div>
                    <div class="rec-body">
                        <div class="rec-title">Great Day So Far — ₱<?= number_format($today_sales,2) ?> in Sales</div>
                        <div class="rec-desc">You've processed <?= $today_orders ?> order<?= $today_orders > 1 ? 's' : '' ?> today. Keep the momentum going — peak hours are usually mid-morning and early afternoon.</div>
                    </div>
                    <span class="rec-badge badge-good">On Track</span>
                </div>
                <?php endif; ?>

                <?php if($completion_rate < 70 && $total_orders > 0): ?>
                <div class="rec-item">
                    <div class="rec-icon ic-purple"><i data-lucide="check-circle"></i></div>
                    <div class="rec-body">
                        <div class="rec-title">Low Completion Rate (<?= $completion_rate ?>%)</div>
                        <div class="rec-desc">Only <?= $completion_rate ?>% of orders are marked as completed. Regularly updating order statuses helps with accurate revenue reporting and customer service.</div>
                    </div>
                    <span class="rec-badge badge-warn">Improve</span>
                </div>
                <?php else: ?>
                <div class="rec-item">
                    <div class="rec-icon ic-green"><i data-lucide="check-circle"></i></div>
                    <div class="rec-body">
                        <div class="rec-title">Good Completion Rate (<?= $completion_rate ?>%)</div>
                        <div class="rec-desc">Your team is keeping up with order completions. This helps ensure accurate revenue data and a smooth customer experience.</div>
                    </div>
                    <span class="rec-badge badge-good">Healthy</span>
                </div>
                <?php endif; ?>

                <?php if($avg_order > 0): ?>
                <div class="rec-item">
                    <div class="rec-icon ic-blue"><i data-lucide="receipt"></i></div>
                    <div class="rec-body">
                        <div class="rec-title">Average Order Value: ₱<?= number_format($avg_order,2) ?></div>
                        <div class="rec-desc">
                            <?php if($avg_order < 150): ?>
                            Your average order is on the lower side. Try upselling add-ons like extra shots, syrups, or pairing snacks with drinks to increase basket size.
                            <?php elseif($avg_order < 300): ?>
                            Solid average order value. Consider introducing combo meals or bundle deals to push it higher and reward customers with better value.
                            <?php else: ?>
                            Excellent average order value! Your upselling strategy is working well. Keep promoting premium items and seasonal specials.
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="rec-badge badge-tip">Insight</span>
                </div>
                <?php endif; ?>

                <?php if(!empty($best_product)): ?>
                <div class="rec-item">
                    <div class="rec-icon ic-orange"><i data-lucide="star"></i></div>
                    <div class="rec-body">
                        <div class="rec-title">Best Seller: <?= htmlspecialchars($best_product) ?></div>
                        <div class="rec-desc">This is your most ordered item. Make sure it's always in stock, prominently displayed on the menu, and consider creating a variation or seasonal version to keep it fresh.</div>
                    </div>
                    <span class="rec-badge badge-good">Top Item</span>
                </div>
                <?php endif; ?>

                <?php if($total_products < 5): ?>
                <div class="rec-item">
                    <div class="rec-icon ic-orange"><i data-lucide="plus-circle"></i></div>
                    <div class="rec-body">
                        <div class="rec-title">Expand Your Menu</div>
                        <div class="rec-desc">You only have <?= $total_products ?> product<?= $total_products != 1 ? 's' : '' ?> listed. Adding more variety — seasonal drinks, food pairings, or desserts — can attract more customers and increase revenue.</div>
                    </div>
                    <span class="rec-badge badge-tip">Tip</span>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="theme.js"></script>
    <script>lucide.createIcons();</script>
</body>
</html>
