<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Get user profile picture - handle if column doesn't exist
$user_id = $_SESSION['user_id'];
$profile_pic = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['username']) . "&background=F28B50&color=fff";

try {
    $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user && !empty($user['profile_pic'])) {
        $profile_pic = $user['profile_pic'];
    }
} catch (PDOException $e) {
    // Column doesn't exist yet, use default avatar
    // Run add_profile_pic_column_fix.sql to add the column
}

$products = $pdo->query("SELECT * FROM products")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purr'Coffee POS</title>
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
            --input-bg: #FFFFFF;
            --shadow: rgba(0, 0, 0, 0.08);
            --dark-gray: #5A5A5A;
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
            --dark-gray: #E0E0E0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text-main); display: flex; height: 100vh; overflow: hidden; transition: background 0.3s ease; }

        /* LEFT SIDEBAR */
        .sidebar { width: 160px; background: var(--white); padding: 25px 18px; display: flex; flex-direction: column; border-right: 2px solid var(--border); transition: background 0.3s ease; }
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

        /* MAIN CONTENT */
        .main-content { flex: 1; padding: 25px 35px; overflow-y: auto; display: flex; flex-direction: column; }
        .top-bar { display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 30px; }
        .search-bar { flex: 1; max-width: 520px; background: var(--white); border-radius: 22px; display: flex; align-items: center; padding: 11px 22px; border: 2px solid var(--border); transition: all 0.2s; }
        .search-bar i { width: 18px; height: 18px; color: var(--text-muted); margin-right: 10px; }
        .search-bar input { border: none; outline: none; width: 100%; font-size: 13px; background: transparent; color: var(--text-main); }
        .search-bar input::placeholder { color: var(--text-muted); }
        .user-profile { display: flex; align-items: center; gap: 10px; padding: 6px 12px; background: var(--white); border-radius: 18px; }
        .user-profile img { width: 36px; height: 36px; border-radius: 50%; }
        .user-info { text-align: left; }
        .user-name { font-weight: 600; font-size: 12px; }
        .user-email { font-size: 10px; color: var(--text-muted); }

        /* CATEGORIES */
        .categories { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .cat-btn { padding: 10px 24px; border-radius: 22px; border: 2px solid var(--border); background: var(--white); color: var(--text-main); font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .cat-btn:hover { background: var(--light-orange); color: var(--orange); }
        .cat-btn.active { background: var(--orange); color: white; border-color: var(--orange); }

        /* SECTION TITLE */
        .section-title { font-size: 20px; font-weight: 700; margin-bottom: 22px; }

        /* PRODUCT GRID - 2 columns */
        .product-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; }
        .product-card { background: var(--white); border-radius: 18px; padding: 18px; display: flex; flex-direction: column; transition: all 0.2s; border: 2px solid var(--border); }
        .product-card:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); border-color: var(--orange); }
        
        .product-top { display: flex; gap: 18px; margin-bottom: 15px; }
        .product-image { width: 90px; height: 110px; background: var(--card-bg); border-radius: 14px; object-fit: contain; flex-shrink: 0; }
        .product-info { flex: 1; display: flex; flex-direction: column; }
        .product-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; }
        .product-name { font-size: 15px; font-weight: 700; color: var(--text-main); }
        .product-price { font-size: 15px; font-weight: 700; color: var(--orange); }
        .product-desc { font-size: 11px; color: var(--text-muted); line-height: 1.4; margin-bottom: auto; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        
        .size-row { display: flex; align-items: center; gap: 8px; margin-bottom: 15px; }
        .size-label { font-size: 12px; font-weight: 600; color: var(--text-main); margin-right: 5px; }
        .size-btn { padding: 7px 18px; border-radius: 20px; border: 2px solid var(--border); background: var(--card-bg); font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; color: var(--text-muted); }
        .size-btn:hover { background: var(--orange); color: white; border-color: var(--orange); }
        .size-btn.active { background: var(--orange); color: white; border-color: var(--orange); }
        
        .product-actions { display: flex; align-items: center; gap: 12px; }
        .qty-controls { display: flex; align-items: center; gap: 12px; }
        .qty-btn { width: 28px; height: 28px; border-radius: 8px; border: none; background: var(--card-bg); font-size: 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-main); transition: all 0.2s; }
        .qty-btn:hover { background: var(--input-bg); }
        .qty-val { font-weight: 700; font-size: 14px; min-width: 20px; text-align: center; }
        .add-to-cart-btn { flex: 1; background: var(--orange); color: white; border: none; padding: 10px 20px; border-radius: 22px; font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.2s; }
        .add-to-cart-btn:hover { background: #e07a42; }
        .add-to-cart-btn.added { background: var(--orange); }
        .product-card.sold-out { opacity: 0.6; }
        .sold-out-badge { position: absolute; top: 8px; left: 8px; background: #EB5757; color: white; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 20px; z-index: 2; }
        .add-to-cart-btn:disabled { background: #BEBEBE; cursor: not-allowed; opacity: 1; }

        /* CART PANEL */
        .cart-panel { width: 320px; background: var(--white); padding: 25px 22px; display: flex; flex-direction: column; transition: background 0.3s ease; border-left: 2px solid var(--border); }
        .cart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
        .cart-title { font-size: 18px; font-weight: 700; }
        .order-number { font-size: 11px; color: var(--text-muted); }
        .order-type-toggle { display: flex; gap: 6px; margin-bottom: 22px; background: var(--bg); padding: 4px; border-radius: 12px; border: 2px solid var(--border); }
        .type-btn { flex: 1; padding: 9px; border-radius: 10px; border: none; background: var(--card-bg); color: var(--text-muted); font-weight: 600; font-size: 12px; cursor: pointer; transition: all 0.2s; }
        .type-btn.active { background: var(--dark-gray); color: white; }
        [data-theme="dark"] .type-btn.active { background: var(--orange); color: white; }
        .cart-items { flex: 1; overflow-y: auto; margin-bottom: 18px; }
        .cart-item { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; padding-bottom: 18px; border-bottom: 2px solid var(--border); }
        .cart-item:last-child { border-bottom: none; padding-bottom: 0; }
        .cart-item-img { width: 55px; height: 55px; background: var(--card-bg); border-radius: 11px; object-fit: contain; border: 2px solid var(--border); }
        .cart-item-info { flex: 1; }
        .cart-item-name { font-weight: 700; font-size: 13px; margin-bottom: 2px; }
        .cart-item-size { font-size: 10px; color: var(--text-muted); margin-bottom: 4px; }
        .cart-item-price { font-weight: 700; font-size: 13px; color: var(--orange); }
        .cart-item-controls { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
        .cart-qty-controls { display: flex; align-items: center; gap: 8px; }
        .cart-qty-btn { width: 22px; height: 22px; border-radius: 6px; border: 2px solid var(--border); background: var(--card-bg); font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-main); transition: 0.2s; }
        .cart-qty-btn:hover { border-color: var(--orange); color: var(--orange); }
        .cart-qty-val { font-weight: 700; font-size: 12px; min-width: 12px; text-align: center; }
        .empty-cart { text-align: center; padding: 50px 20px; color: var(--text-muted); }
        .empty-cart i { width: 44px; height: 44px; margin-bottom: 12px; opacity: 0.3; }
        .empty-cart-title { font-weight: 600; margin-bottom: 4px; font-size: 14px; }
        .empty-cart-text { font-size: 11px; }
        .cart-summary { border-top: 2px solid var(--border); padding-top: 18px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
        .summary-label { color: var(--text-muted); font-weight: 500; }
        .summary-value { font-weight: 700; color: var(--text-main); }
        .summary-discount { color: #27AE60; }
        .total-row { display: flex; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--card-bg); }
        .total-label { font-size: 15px; font-weight: 700; }
        .total-value { font-size: 16px; font-weight: 700; color: var(--orange); }
        .place-order-btn { width: 100%; background: var(--orange); color: white; border: none; padding: 14px; border-radius: 13px; font-weight: 700; font-size: 14px; cursor: pointer; margin-top: 18px; transition: none; }
        .place-order-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .print-receipt-btn { width: 100%; background: transparent; color: var(--text-muted); border: 2px solid var(--border); padding: 10px; border-radius: 13px; font-weight: 600; font-size: 13px; cursor: pointer; margin-top: 8px; transition: none; }
        .print-receipt-btn:hover { border-color: var(--orange); color: var(--orange); }

        /* Receipt Container (hidden until print) */
        .receipt-print {
            display: none;
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 10mm;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }

        /* Print Styles - Professional Receipt */
        @media print {
            * { visibility: hidden !important; }
            .receipt-print,
            .receipt-print * { visibility: visible !important; }
            .receipt-print {
                display: block !important;
                position: fixed !important;
                inset: 0 !important;
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 40px !important;
                font-family: 'Courier New', monospace !important;
                font-size: 16px !important;
                line-height: 1.8 !important;
                color: #000 !important;
                background: #fff !important;
                border: none !important;
            }
            .receipt-store-name { font-size: 28px !important; }
            .receipt-store-info { font-size: 15px !important; }
            .receipt-order-info { font-size: 15px !important; }
            .receipt-total-row { font-size: 16px !important; }
            .receipt-total-row.final { font-size: 22px !important; }
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
        }
        
        .receipt-store-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .receipt-store-info {
            font-size: 10px;
            line-height: 1.3;
        }
        
        .receipt-order-info {
            margin: 15px 0;
            font-size: 11px;
        }
        
        .receipt-items {
            margin: 15px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 10px 0;
        }
        
        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .receipt-item-name {
            flex: 1;
            font-weight: bold;
        }
        
        .receipt-item-details {
            font-size: 10px;
            color: #666;
            margin-left: 10px;
        }
        
        .receipt-item-price {
            text-align: right;
            min-width: 60px;
        }
        
        .receipt-totals {
            margin: 15px 0;
        }
        
        .receipt-total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .receipt-total-row.final {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 8px;
            margin-top: 8px;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px dashed #000;
            font-size: 10px;
        }
        
        .receipt-thank-you {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo">Purr'<span>Coffee</span></div>
        <div class="nav-group">
            <a href="pos.php" class="nav-item active">
                <i data-lucide="coffee"></i> Menu
            </a>
            <a href="orders.php" class="nav-item">
                <i data-lucide="clipboard-list"></i> Orders
            </a>
            <a href="reports.php" class="nav-item">
                <i data-lucide="bar-chart-3"></i> Reports
            </a>
        </div>
        <div style="margin-top: auto;">
            <div class="theme-toggle" onclick="toggleTheme()">
                <i data-lucide="moon" id="themeIcon"></i>
                <span id="themeText">Dark Mode</span>
            </div>
            <a href="settings.php" class="nav-item">
                <i data-lucide="settings"></i> Settings
            </a>
            <a href="logout.php" class="nav-item">
                <i data-lucide="log-out"></i> Log out
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="search-bar">
                <i data-lucide="search"></i>
                <input type="text" id="menuSearch" placeholder="Search..." onkeyup="searchMenu()">
            </div>
        </div>

        <div class="categories">
            <button class="cat-btn active" onclick="filterCategory('all', this)">All</button>
            <button class="cat-btn" onclick="filterCategory('Coffee', this)">Coffee</button>
            <button class="cat-btn" onclick="filterCategory('Non Coffee', this)">Non Coffee</button>
            <button class="cat-btn" onclick="filterCategory('Food', this)">Food</button>
            <button class="cat-btn" onclick="filterCategory('Snack', this)">Snack</button>
            <button class="cat-btn" onclick="filterCategory('Dessert', this)">Dessert</button>
        </div>

        <h2 class="section-title" id="catTitle">All menu</h2>

        <div class="product-grid" id="productGrid">
            <?php foreach($products as $p): 
                $isDrink = in_array($p['category'], ['Coffee', 'Non Coffee']);
                $basePrice = $p['price'];
                $priceM = isset($p['price_m']) ? $p['price_m'] : $basePrice + 20;
                $priceL = isset($p['price_l']) ? $p['price_l'] : $basePrice + 40;
            ?>
            <?php $isSoldOut = isset($p['stock']) && $p['stock'] !== null && intval($p['stock']) === 0; ?>
            <div class="product-card <?= $isSoldOut ? 'sold-out' : '' ?>" data-category="<?= $p['category'] ?>" data-name="<?= strtolower($p['name']) ?>" data-stock="<?= $p['stock'] ?? '' ?>">
                <div class="product-top" style="position:relative;">
                    <?php if($isSoldOut): ?><span class="sold-out-badge">Sold Out</span><?php endif; ?>
                    <img src="<?= $p['image_url'] ?>" class="product-image" alt="<?= $p['name'] ?>">
                    <div class="product-info">
                        <div class="product-header">
                            <div class="product-name"><?= $p['name'] ?></div>
                            <div class="product-price">₱ <span class="price-val"><?= number_format($basePrice, 2) ?></span></div>
                        </div>
                        <div class="product-desc"><?= htmlspecialchars($p['description'] ?? 'The combination of coffee, milk, and palm sugar makes this drink have a delicious taste.') ?></div>
                    </div>
                </div>
                
                <?php if($isDrink): ?>
                <div class="size-row">
                    <span class="size-label">Size</span>
                    <button class="size-btn active" data-price="<?= $basePrice ?>" onclick="selectSize(this)">Small</button>
                    <button class="size-btn" data-price="<?= $priceM ?>" onclick="selectSize(this)">Medium</button>
                    <button class="size-btn" data-price="<?= $priceL ?>" onclick="selectSize(this)">Large</button>
                </div>
                <?php else: ?>
                <div style="height: 15px;"></div>
                <?php endif; ?>

                <div class="product-actions">
                    <div class="qty-controls">
                        <button class="qty-btn" onclick="updateQty(this, -1)">-</button>
                        <span class="qty-val">1</span>
                        <button class="qty-btn" onclick="updateQty(this, 1)">+</button>
                    </div>
                    <button class="add-to-cart-btn" <?= $isSoldOut ? 'disabled' : '' ?> onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>', '<?= $p['image_url'] ?>', this)"><?= $isSoldOut ? 'Sold Out' : 'Add to Cart' ?></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="cart-panel">
        <div class="user-profile" style="margin-bottom: 25px;">
            <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile">
            <div class="user-info" style="text-align: left;">
                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="user-email"><?= htmlspecialchars($_SESSION['username']) ?>@gmail.com</div>
            </div>
        </div>

        <div class="cart-header">
            <div class="cart-title">Cart</div>
            <div class="order-number" id="orderNumber">Order #1</div>
        </div>

        <div class="order-type-toggle">
            <button class="type-btn active" onclick="selectType(this)">Delivery</button>
            <button class="type-btn" onclick="selectType(this)">Dine in</button>
            <button class="type-btn" onclick="selectType(this)">Take away</button>
        </div>

        <div class="cart-items" id="cartDisplay"></div>

        <div class="cart-summary">
            <div class="summary-row">
                <span class="summary-label">Subtotal</span>
                <span class="summary-value" id="subtotal">₱ 0.00</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Tax (<span id="taxRate">12</span>%)</span>
                <span class="summary-value" id="taxAmount">₱ 0.00</span>
            </div>
            <div class="total-row">
                <span class="total-label">Total</span>
                <span class="total-value" id="finalTotal">₱ 0.00</span>
            </div>
            <button class="place-order-btn" onclick="checkout()">Place an order</button>
            <button class="print-receipt-btn" id="printReceiptBtn" onclick="printReceipt()" style="display:none;">🖨️ Print Last Receipt</button>
        </div>
    </div>

    <!-- Hidden Receipt for Printing -->
    <div class="receipt-print" id="receiptPrint">
        <div class="receipt-header">
            <div class="receipt-store-name">PURR'COFFEE</div>
            <div class="receipt-store-info">
                123 Coffee Street, Manila<br>
                +63 912 345 6789<br>
                TIN: 123-456-789-000
            </div>
        </div>
        
        <div class="receipt-order-info">
            <div>Order #: <span id="receiptOrderNum"></span></div>
            <div>Date: <span id="receiptDate"></span></div>
            <div>Time: <span id="receiptTime"></span></div>
            <div>Cashier: <?= htmlspecialchars($_SESSION['username']) ?></div>
        </div>
        
        <div class="receipt-items" id="receiptItems">
            <!-- Items will be inserted here -->
        </div>
        
        <div class="receipt-totals">
            <div class="receipt-total-row">
                <span>Subtotal:</span>
                <span id="receiptSubtotal">₱ 0.00</span>
            </div>
            <div class="receipt-total-row">
                <span>Tax (<span id="receiptTaxRate">12</span>%):</span>
                <span id="receiptTax">₱ 0.00</span>
            </div>
            <div class="receipt-total-row final">
                <span>TOTAL:</span>
                <span id="receiptTotal">₱ 0.00</span>
            </div>
        </div>
        
        <div class="receipt-footer">
            <div class="receipt-thank-you">THANK YOU!</div>
            <div>Please come again</div>
            <div style="margin-top: 10px;">www.purrcoffee.com</div>
        </div>
    </div>

    <script src="theme.js"></script>
    <script>
        lucide.createIcons();
        let cart = [];

        // Sound effects
        function playSound(type) {
            const soundEnabled = localStorage.getItem('setting_soundEffects') === 'true';
            if (!soundEnabled) return;
            
            // Create audio context for beep sounds
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            // Different sounds for different actions
            switch(type) {
                case 'add':
                    oscillator.frequency.value = 800;
                    gainNode.gain.value = 0.1;
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.1);
                    break;
                case 'remove':
                    oscillator.frequency.value = 400;
                    gainNode.gain.value = 0.1;
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.1);
                    break;
                case 'checkout':
                    oscillator.frequency.value = 1000;
                    gainNode.gain.value = 0.15;
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.2);
                    break;
            }
        }

        // Order number management - Random 6-digit number
        function generateOrderNumber() {
            // Generate random 6-digit number (100000 to 999999)
            return Math.floor(100000 + Math.random() * 900000);
        }

        function getOrderNumber() {
            // Always generate a new random order number
            return generateOrderNumber();
        }

        function updateOrderDisplay() {
            const orderNum = getOrderNumber();
            document.getElementById('orderNumber').innerText = 'Order #' + orderNum;
            // Store current order number temporarily
            sessionStorage.setItem('currentOrderNumber', orderNum);
        }

        window.addEventListener('DOMContentLoaded', () => {
            updateOrderDisplay();
            const savedCart = localStorage.getItem('cart');
            if (savedCart) {
                cart = JSON.parse(savedCart);
                updateUI();
            }
        });

        function saveCart() {
            localStorage.setItem('cart', JSON.stringify(cart));
        }

        function searchMenu() {
            let input = document.getElementById('menuSearch').value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(card => {
                let name = card.getAttribute('data-name');
                card.style.display = name.includes(input) ? 'flex' : 'none';
            });
        }

        function filterCategory(cat, btn) {
            if(btn) {
                document.querySelectorAll('.cat-btn').forEach(item => item.classList.remove('active'));
                btn.classList.add('active');
            }
            document.getElementById('catTitle').innerText = (cat === 'all' ? 'All' : cat) + ' menu';
            document.querySelectorAll('.product-card').forEach(card => {
                let cardCat = card.getAttribute('data-category');
                card.style.display = (cat === 'all' || cardCat === cat) ? 'flex' : 'none';
            });
        }

        function selectSize(btn) {
            const card = btn.closest('.product-card');
            card.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const newPrice = parseFloat(btn.getAttribute('data-price')).toFixed(2);
            card.querySelector('.price-val').innerText = newPrice;
        }

        function selectType(btn) {
            btn.parentElement.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        function updateQty(btn, delta) {
            const span = btn.parentElement.querySelector('.qty-val');
            let val = parseInt(span.innerText) + delta;
            if (val < 1) val = 1;
            span.innerText = val;
        }

        function addToCart(id, name, img, btn) {
            const card = btn.closest('.product-card');
            // Stock guard — block sold-out products
            const stock = card.dataset.stock;
            if (stock !== '' && stock !== undefined && parseInt(stock) === 0) return;
            const qty = parseInt(card.querySelector('.qty-val').innerText);
            let size = "Standard";
            let price = 0;
            const activeSizeBtn = card.querySelector('.size-btn.active');
            if (activeSizeBtn) {
                size = activeSizeBtn.innerText;
                price = parseFloat(activeSizeBtn.getAttribute('data-price'));
            } else {
                price = parseFloat(card.querySelector('.price-val').innerText);
            }
            const cartId = id + '-' + size;
            let item = cart.find(i => i.cartId === cartId);
            if (item) { 
                item.qty += qty; 
            } else { 
                cart.push({ cartId, id, name, price, img, qty, size }); 
            }
            
            // Update button appearance
            btn.classList.add('added');
            btn.innerText = 'Added to cart';
            
            card.querySelector('.qty-val').innerText = '1';
            saveCart();
            updateUI();
            
            // Hide print receipt button when new order starts
            document.getElementById('printReceiptBtn').style.display = 'none';
            
            // Play sound effect
            playSound('add');
            
            // Show notification if enabled
            showNotification('Added to cart: ' + name);
        }

        function updateUI() {
            const list = document.getElementById('cartDisplay');
            list.innerHTML = '';
            let subtotal = 0;
            
            if (cart.length === 0) {
                list.innerHTML = `
                    <div class="empty-cart">
                        <i data-lucide="shopping-cart"></i>
                        <div class="empty-cart-title">Your cart is empty</div>
                        <div class="empty-cart-text">Add items to get started</div>
                    </div>`;
                lucide.createIcons();
            } else {
                cart.forEach(item => {
                    subtotal += item.price * item.qty;
                    list.innerHTML += `
                        <div class="cart-item">
                            <img src="${item.img}" alt="${item.name}" class="cart-item-img">
                            <div class="cart-item-info">
                                <div class="cart-item-name">${item.name}</div>
                                <div class="cart-item-size">${item.size !== 'Standard' ? item.size + ' • ' : ''}${item.qty}00g</div>
                                <div class="cart-item-price">₱ ${(item.price * item.qty).toFixed(2)}</div>
                            </div>
                            <div class="cart-item-controls">
                                <div class="cart-qty-controls">
                                    <button class="cart-qty-btn" onclick="changeCartQty('${item.cartId}', -1)">-</button>
                                    <span class="cart-qty-val">${item.qty}</span>
                                    <button class="cart-qty-btn" onclick="changeCartQty('${item.cartId}', 1)">+</button>
                                </div>
                            </div>
                        </div>`;
                });
            }
            
            // Get tax rate from settings (default 12%)
            const taxRate = parseFloat(localStorage.getItem('setting_taxRate') || '12');
            const taxAmount = subtotal * (taxRate / 100);
            const total = subtotal + taxAmount;
            
            // Update display
            document.getElementById('subtotal').innerText = '₱ ' + subtotal.toFixed(2);
            document.getElementById('taxRate').innerText = taxRate.toFixed(0);
            document.getElementById('taxAmount').innerText = '₱ ' + taxAmount.toFixed(2);
            document.getElementById('finalTotal').innerText = '₱ ' + total.toFixed(2);
        }

        function changeCartQty(cartId, delta) {
            let item = cart.find(i => i.cartId === cartId);
            item.qty += delta;
            if (item.qty < 1) {
                cart = cart.filter(i => i.cartId !== cartId);
                playSound('remove');
            }
            saveCart();
            updateUI();
        }

        // Notification system
        function showNotification(message) {
            const orderAlertsEnabled = localStorage.getItem('setting_orderAlerts') === 'true';
            if (!orderAlertsEnabled) return;
            
            // Check if browser supports notifications
            if (!("Notification" in window)) return;
            
            // Request permission if not granted
            if (Notification.permission === "granted") {
                new Notification("Purr'Coffee POS", {
                    body: message,
                    icon: "placeholder.jpg"
                });
            } else if (Notification.permission !== "denied") {
                Notification.requestPermission().then(function (permission) {
                    if (permission === "granted") {
                        new Notification("Purr'Coffee POS", {
                            body: message,
                            icon: "placeholder.jpg"
                        });
                    }
                });
            }
        }

        async function checkout() {
            if(cart.length === 0) return alert("Cart is empty");
            const btn = document.querySelector('.place-order-btn');
            btn.disabled = true;
            btn.innerText = 'Processing...';
            
            // Play checkout sound
            playSound('checkout');
            
            try {
                const res = await fetch('process_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        cart, 
                        total: parseFloat(document.getElementById('finalTotal').innerText.replace(/[^0-9.]/g, '')),
                        order_number: parseInt(sessionStorage.getItem('currentOrderNumber') || generateOrderNumber())
                    })
                });
                if (!res.ok) throw new Error('Network response was not ok');
                const result = await res.json();
                if(result.success) { 
                    // Prepare receipt BEFORE clearing cart
                    prepareReceipt();
                    
                    alert("Order placed successfully!"); 
                    
                    // Auto print receipt if enabled (after alert so cart data is still in receipt)
                    const autoPrintEnabled = localStorage.getItem('setting_autoPrint') === 'true';
                    if (autoPrintEnabled) {
                        printReceipt();
                    }
                    
                    // Show print button for manual printing
                    document.getElementById('printReceiptBtn').style.display = 'block';
                    
                    // Show notification
                    showNotification('Order completed successfully!');
                    
                    cart = []; 
                    saveCart();
                    // Generate new random order number for next transaction
                    updateOrderDisplay();
                    updateUI(); 
                } else {
                    alert("Error: " + (result.message || "Failed to process order"));
                }
            } catch (error) {
                console.error('Checkout error:', error);
                alert("Error processing order. Please try again.");
            } finally {
                btn.disabled = false;
                btn.innerText = 'Place an order';
            }
        }

        // Prepare receipt for printing
        function prepareReceipt() {
            // Get current order number
            const orderNum = sessionStorage.getItem('currentOrderNumber') || getOrderNumber();
            document.getElementById('receiptOrderNum').innerText = orderNum;
            
            // Get current date and time
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('receiptDate').innerText = dateStr;
            document.getElementById('receiptTime').innerText = timeStr;
            
            // Get tax rate
            const taxRate = parseFloat(localStorage.getItem('setting_taxRate') || '12');
            document.getElementById('receiptTaxRate').innerText = taxRate.toFixed(0);
            
            // Calculate totals
            let subtotal = 0;
            cart.forEach(item => {
                subtotal += item.price * item.qty;
            });
            const tax = subtotal * (taxRate / 100);
            const total = subtotal + tax;
            
            // Update receipt totals
            document.getElementById('receiptSubtotal').innerText = '₱ ' + subtotal.toFixed(2);
            document.getElementById('receiptTax').innerText = '₱ ' + tax.toFixed(2);
            document.getElementById('receiptTotal').innerText = '₱ ' + total.toFixed(2);
            
            // Build items list
            const itemsContainer = document.getElementById('receiptItems');
            itemsContainer.innerHTML = '';
            
            cart.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.style.marginBottom = '10px';
                
                const itemLine1 = document.createElement('div');
                itemLine1.style.display = 'flex';
                itemLine1.style.justifyContent = 'space-between';
                itemLine1.style.fontWeight = 'bold';
                
                const itemName = document.createElement('span');
                itemName.textContent = item.name;
                
                const itemPrice = document.createElement('span');
                itemPrice.textContent = '₱ ' + (item.price * item.qty).toFixed(2);
                
                itemLine1.appendChild(itemName);
                itemLine1.appendChild(itemPrice);
                
                const itemLine2 = document.createElement('div');
                itemLine2.style.fontSize = '10px';
                itemLine2.style.color = '#666';
                itemLine2.textContent = `  ${item.qty} x ₱${item.price.toFixed(2)}`;
                if (item.size !== 'Standard') {
                    itemLine2.textContent += ` (${item.size})`;
                }
                
                itemDiv.appendChild(itemLine1);
                itemDiv.appendChild(itemLine2);
                itemsContainer.appendChild(itemDiv);
            });
            
            // Load store info from localStorage if available
            const storeName = localStorage.getItem('store_name');
            const storeAddress = localStorage.getItem('store_address');
            const storePhone = localStorage.getItem('store_phone');
            const storeTin = localStorage.getItem('store_tin');
            
            if (storeName || storeAddress || storePhone || storeTin) {
                const storeInfo = document.querySelector('.receipt-store-info');
                storeInfo.innerHTML = `
                    ${storeAddress || '123 Coffee Street, Manila'}<br>
                    ${storePhone || '+63 912 345 6789'}<br>
                    TIN: ${storeTin || '123-456-789-000'}
                `;
                
                if (storeName) {
                    document.querySelector('.receipt-store-name').textContent = storeName.toUpperCase();
                }
            }
        }

        // Print receipt function
        function printReceipt() {
            const receipt = document.getElementById('receiptPrint');
            receipt.style.display = 'block';
            window.print();
            receipt.style.display = 'none';
        }
    </script>
</body>
</html>
