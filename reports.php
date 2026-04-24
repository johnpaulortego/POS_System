<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];

// Get today's sales - use MySQL CURDATE() to avoid timezone mismatch
$stmt = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$todaySales = $stmt->fetch();

// Get this week's sales
$stmt = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)");
$weekSales = $stmt->fetch();

// Get this month's sales
$stmt = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())");
$monthSales = $stmt->fetch();

// Get all time sales
$stmt = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM orders");
$allTimeSales = $stmt->fetch();

// Custom date range
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo   = $_GET['to']   ?? date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount),0) as total FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$dateFrom, $dateTo]);
$customRange = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Purr'Coffee</title>
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
            --shadow: rgba(0, 0, 0, 0.08);
            --input-bg: #FFFFFF;
        }

        [data-theme="dark"] {
            --bg: #1A1A1A;
            --white: #2A2A2A;
            --text-main: #FFFFFF;
            --text-muted: #AAAAAA;
            --border: #444444;
            --card-bg: #2A2A2A;
            --shadow: rgba(0, 0, 0, 0.3);
            --input-bg: #333333;
        }

        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; -webkit-font-smoothing: antialiased; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; transition: background 0.3s ease, color 0.3s ease; }

        /* SIDEBAR */
        .sidebar-left { width: 160px; background: var(--white); padding: 25px 18px; display: flex; flex-direction: column; border-right: 2px solid var(--border); transition: background 0.3s ease; }
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
        .content-main { flex: 1; padding: 40px 50px; overflow-y: auto; }
        .page-title { font-size: 28px; font-weight: 800; margin-bottom: 35px; }

        /* STATS GRID */
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card-bg); border: 2px solid var(--border); border-radius: 18px; padding: 30px; transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px var(--shadow); }
        .stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .stat-title { font-size: 14px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-icon { width: 48px; height: 48px; background: var(--light-orange); border-radius: 14px; display: flex; align-items: center; justify-content: center; }
        .stat-icon svg { width: 24px; height: 24px; color: var(--orange); stroke: var(--orange); }
        [data-theme="dark"] .stat-icon { background: rgba(242, 139, 80, 0.15); }
        .stat-value { font-size: 36px; font-weight: 800; color: var(--text-main); margin-bottom: 8px; }
        .stat-label { font-size: 13px; color: var(--text-muted); }

        /* TABLE */
        .table-container { background: var(--card-bg); border: 2px solid var(--border); border-radius: 18px; overflow: hidden; }
        .table-header { padding: 25px 30px; border-bottom: 2px solid var(--border); }
        .table-title { font-size: 18px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 18px 30px; background: var(--bg); font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        td { padding: 20px 30px; font-size: 14px; border-top: 1px solid var(--border); color: var(--text-main); }
        .period-col { font-weight: 600; }
        .revenue-col { font-weight: 800; color: var(--orange); font-size: 16px; }
        .orders-col { color: var(--text-muted); }

        /* CHARTS ROW */
        .charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .chart-card { background: var(--card-bg); border: 2px solid var(--border); border-radius: 18px; padding: 30px; }
        .chart-header { margin-bottom: 25px; }
        .chart-title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        .chart-subtitle { font-size: 12px; color: var(--text-muted); }

        /* PIE CHART */
        .pie-chart-container { display: flex; align-items: center; gap: 30px; }
        .pie-chart { width: 200px; height: 200px; }
        .pie-legend { flex: 1; display: flex; flex-direction: column; gap: 15px; }
        .legend-item { display: flex; align-items: center; gap: 10px; padding: 12px; background: var(--bg); border-radius: 10px; }
        .legend-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
        .legend-label { flex: 1; font-size: 13px; font-weight: 600; color: var(--text-main); }
        .legend-value { font-size: 14px; font-weight: 700; color: var(--text-main); }

        /* AREA CHART */
        .area-chart { width: 100%; height: 200px; }
        .area-svg { width: 100%; height: 100%; }
        [data-theme="dark"] .area-svg text { fill: var(--text-muted); }
        .date-filter { background: var(--card-bg); border: 2px solid var(--border); border-radius: 18px; padding: 20px 25px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .date-filter label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
        .date-filter input[type="date"] { padding: 9px 14px; border-radius: 10px; border: 2px solid var(--border); background: var(--input-bg); color: var(--text-main); font-size: 13px; font-weight: 600; outline: none; }
        .date-filter input[type="date"]:focus { border-color: var(--orange); }
        .date-filter-btn { padding: 9px 20px; border-radius: 10px; border: none; background: var(--orange); color: white; font-weight: 700; font-size: 13px; cursor: pointer; }
        .custom-stat { background: var(--card-bg); border: 2px solid var(--orange); border-radius: 18px; padding: 20px 25px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; }
        .custom-stat-label { font-size: 13px; color: var(--text-muted); font-weight: 600; }
        .custom-stat-value { font-size: 22px; font-weight: 800; color: var(--orange); }
    </style>
</head>
<body>

    <div class="sidebar-left">
        <div class="logo">Purr'<span>Coffee</span></div>
        <div class="nav-group">
            <a href="pos.php" class="nav-item"><i data-lucide="coffee"></i> Menu</a>
            <a href="orders.php" class="nav-item"><i data-lucide="clipboard-list"></i> Orders</a>
            <a href="reports.php" class="nav-item active"><i data-lucide="bar-chart-3"></i> Reports</a>
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
        <h1 class="page-title">Sales Reports</h1>

        <!-- DATE RANGE FILTER -->
        <form method="GET" class="date-filter">
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase; white-space:nowrap;">From</span>
                    <input type="date" name="from" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase; white-space:nowrap;">To</span>
                    <input type="date" name="to" value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <button type="submit" class="date-filter-btn">Apply</button>
                <a href="reports.php" style="font-size:13px; color:var(--text-muted); font-weight:600; text-decoration:none;">Reset</a>
            </div>
        </form>

        <?php if(isset($_GET['from'])): ?>
        <div class="custom-stat">
            <div>
                <div class="custom-stat-label">Custom Range: <?= htmlspecialchars($dateFrom) ?> → <?= htmlspecialchars($dateTo) ?></div>
                <div class="custom-stat-value">₱<?= number_format($customRange['total'],2) ?></div>
            </div>
            <div style="text-align:right;">
                <div class="custom-stat-label">Orders in range</div>
                <div style="font-size:22px;font-weight:800;"><?= $customRange['count'] ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Today's Sales</div>
                    <div class="stat-icon">
                        <i data-lucide="calendar"></i>
                    </div>
                </div>
                <div class="stat-value">₱<?= number_format($todaySales['total'] ?? 0, 2) ?></div>
                <div class="stat-label"><?= $todaySales['count'] ?? 0 ?> orders today</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Revenue</div>
                    <div class="stat-icon">
                        <i data-lucide="trending-up"></i>
                    </div>
                </div>
                <div class="stat-value">₱<?= number_format($allTimeSales['total'] ?? 0, 2) ?></div>
                <div class="stat-label"><?= $allTimeSales['count'] ?? 0 ?> total orders</div>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">Revenue Distribution</div>
                    <div class="chart-subtitle">Sales breakdown by period</div>
                </div>
                <div class="pie-chart-container">
                    <?php
                    // Non-overlapping: today only, rest of week (week minus today), rest of month (month minus week)
                    $todayVal    = floatval($todaySales['total']);
                    $weekOnlyVal = max(floatval($weekSales['total']) - $todayVal, 0);
                    $monthOnlyVal= max(floatval($monthSales['total']) - floatval($weekSales['total']), 0);
                    $allTimeVal  = floatval($allTimeSales['total']);

                    $pieTotal = max($todayVal + $weekOnlyVal + $monthOnlyVal, 1);
                    $todayPercent = ($todayVal / $pieTotal) * 100;
                    $weekPercent  = ($weekOnlyVal / $pieTotal) * 100;
                    $monthPercent = ($monthOnlyVal / $pieTotal) * 100;
                    ?>
                    <svg class="pie-chart" viewBox="0 0 200 200">
                        <circle cx="100" cy="100" r="80" fill="none" stroke="#F28B50" stroke-width="40" 
                                stroke-dasharray="<?= $todayPercent * 5.03 ?> 502.4" 
                                transform="rotate(-90 100 100)"/>
                        <circle cx="100" cy="100" r="80" fill="none" stroke="#27AE60" stroke-width="40" 
                                stroke-dasharray="<?= $weekPercent * 5.03 ?> 502.4" 
                                stroke-dashoffset="<?= -$todayPercent * 5.03 ?>"
                                transform="rotate(-90 100 100)"/>
                        <circle cx="100" cy="100" r="80" fill="none" stroke="#3498DB" stroke-width="40" 
                                stroke-dasharray="<?= $monthPercent * 5.03 ?> 502.4" 
                                stroke-dashoffset="<?= -($todayPercent + $weekPercent) * 5.03 ?>"
                                transform="rotate(-90 100 100)"/>
                        <text x="100" y="95" text-anchor="middle" font-size="24" font-weight="700" fill="var(--text-main)">₱<?= number_format($allTimeVal, 0) ?></text>
                        <text x="100" y="115" text-anchor="middle" font-size="12" fill="var(--text-muted)">Total</text>
                    </svg>
                    <div class="pie-legend">
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #F28B50;"></span>
                            <span class="legend-label">Today</span>
                            <span class="legend-value">₱<?= number_format($todayVal, 0) ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #27AE60;"></span>
                            <span class="legend-label">This Week</span>
                            <span class="legend-value">₱<?= number_format($weekSales['total'], 0) ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #3498DB;"></span>
                            <span class="legend-label">This Month</span>
                            <span class="legend-value">₱<?= number_format($monthSales['total'], 0) ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot" style="background: #9B59B6;"></span>
                            <span class="legend-label">All Time</span>
                            <span class="legend-value">₱<?= number_format($allTimeVal, 0) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">Revenue Trend</div>
                    <div class="chart-subtitle">Sales performance over time</div>
                </div>
                <div class="area-chart">
                    <?php
                    $maxValue = max($todaySales['total'], $weekSales['total'], $monthSales['total'], 1);
                    $todayHeight = ($todaySales['total'] / $maxValue) * 100;
                    $weekHeight = ($weekSales['total'] / $maxValue) * 100;
                    $monthHeight = ($monthSales['total'] / $maxValue) * 100;
                    ?>
                    <svg viewBox="0 0 400 200" class="area-svg">
                        <defs>
                            <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" style="stop-color:#F28B50;stop-opacity:0.3" />
                                <stop offset="100%" style="stop-color:#F28B50;stop-opacity:0.05" />
                            </linearGradient>
                        </defs>
                        <path d="M 50 <?= 180 - $todayHeight * 1.5 ?> L 175 <?= 180 - $weekHeight * 1.5 ?> L 300 <?= 180 - $monthHeight * 1.5 ?> L 300 180 L 50 180 Z" 
                              fill="url(#areaGradient)" stroke="#F28B50" stroke-width="3"/>
                        <circle cx="50" cy="<?= 180 - $todayHeight * 1.5 ?>" r="5" fill="#F28B50"/>
                        <circle cx="175" cy="<?= 180 - $weekHeight * 1.5 ?>" r="5" fill="#F28B50"/>
                        <circle cx="300" cy="<?= 180 - $monthHeight * 1.5 ?>" r="5" fill="#F28B50"/>
                        <text x="50" y="195" text-anchor="middle" font-size="11" fill="var(--text-muted)">Today</text>
                        <text x="175" y="195" text-anchor="middle" font-size="11" fill="var(--text-muted)">Week</text>
                        <text x="300" y="195" text-anchor="middle" font-size="11" fill="var(--text-muted)">Month</text>
                    </svg>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <div class="table-title">Sales Summary</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Revenue</th>
                        <th>Orders</th>
                        <th>Average Order</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="period-col">Today</td>
                        <td class="revenue-col">₱<?= number_format($todaySales['total'] ?? 0, 2) ?></td>
                        <td class="orders-col"><?= $todaySales['count'] ?? 0 ?> orders</td>
                        <td>₱<?= $todaySales['count'] > 0 ? number_format($todaySales['total'] / $todaySales['count'], 2) : '0.00' ?></td>
                    </tr>
                    <tr>
                        <td class="period-col">This Week</td>
                        <td class="revenue-col">₱<?= number_format($weekSales['total'] ?? 0, 2) ?></td>
                        <td class="orders-col"><?= $weekSales['count'] ?? 0 ?> orders</td>
                        <td>₱<?= $weekSales['count'] > 0 ? number_format($weekSales['total'] / $weekSales['count'], 2) : '0.00' ?></td>
                    </tr>
                    <tr>
                        <td class="period-col">This Month</td>
                        <td class="revenue-col">₱<?= number_format($monthSales['total'] ?? 0, 2) ?></td>
                        <td class="orders-col"><?= $monthSales['count'] ?? 0 ?> orders</td>
                        <td>₱<?= $monthSales['count'] > 0 ? number_format($monthSales['total'] / $monthSales['count'], 2) : '0.00' ?></td>
                    </tr>
                    <tr>
                        <td class="period-col">All Time</td>
                        <td class="revenue-col">₱<?= number_format($allTimeSales['total'] ?? 0, 2) ?></td>
                        <td class="orders-col"><?= $allTimeSales['count'] ?? 0 ?> orders</td>
                        <td>₱<?= $allTimeSales['count'] > 0 ? number_format($allTimeSales['total'] / $allTimeSales['count'], 2) : '0.00' ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script src="theme.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
