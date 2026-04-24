<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$products = $pdo->query("SELECT * FROM products ORDER BY category, name")->fetchAll();
$categories = ['Coffee','Non Coffee','Food','Snack','Dessert'];

$total = count($products);
$byCategory = [];
foreach ($products as $p) {
    $byCategory[$p['category']] = ($byCategory[$p['category']] ?? 0) + 1;
}

// Sales count per product
$salesMap = [];
$salesData = $pdo->query("SELECT product_id, SUM(quantity) as total_sold FROM order_items GROUP BY product_id")->fetchAll();
foreach ($salesData as $s) { $salesMap[$s['product_id']] = $s['total_sold']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | Purr'Coffee</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --orange: #F28B50; --light-orange: #FFF5F0;
            --bg: #F5F5F5; --white: #FAFAFA;
            --text-main: #2D2D2D; --text-muted: #8B8B8B;
            --border: #BEBEBE; --card-bg: #FFFFFF; --input-bg: #FFFFFF;
            --red: #EB5757; --shadow: rgba(0,0,0,0.08);
        }
        [data-theme="dark"] {
            --bg: #1A1A1A; --white: #2A2A2A; --text-main: #FFFFFF;
            --text-muted: #AAAAAA; --border: #444444;
            --card-bg: #2A2A2A; --input-bg: #333333;
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
        .add-btn { background: var(--orange); color: white; border: none; padding: 12px 22px; border-radius: 14px; font-weight: 700; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; flex-shrink: 0; }
        .add-btn:hover { background: #e07a42; }
        .add-btn i { width: 18px; height: 18px; }

        /* STATS */
        .stats-row { display: flex; gap: 14px; margin-bottom: 24px; flex-wrap: wrap; }
        .stat-chip { background: var(--card-bg); border: 2px solid var(--border); border-radius: 14px; padding: 14px 20px; display: flex; align-items: center; gap: 12px; }
        .stat-chip-icon { width: 36px; height: 36px; border-radius: 10px; background: var(--light-orange); display: flex; align-items: center; justify-content: center; }
        .stat-chip-icon i { width: 16px; height: 16px; color: var(--orange); stroke: var(--orange); }
        [data-theme="dark"] .stat-chip-icon { background: rgba(242, 139, 80, 0.2); }
        .stat-chip-val { font-size: 20px; font-weight: 800; line-height: 1; }
        .stat-chip-label { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }

        /* FILTER BAR */
        .filter-bar { display: flex; gap: 12px; margin-bottom: 20px; align-items: center; }
        .search-box { background: var(--card-bg); border: 2px solid var(--border); border-radius: 20px; display: flex; align-items: center; padding: 10px 18px; gap: 8px; flex: 1; max-width: 320px; }
        .search-box input { border: none; outline: none; background: transparent; font-size: 13px; color: var(--text-main); width: 100%; }
        .search-box input::placeholder { color: var(--text-muted); }
        .search-box i { width: 16px; height: 16px; color: var(--text-muted); flex-shrink: 0; }
        .cat-filter { padding: 10px 18px; border-radius: 20px; border: 2px solid var(--border); background: var(--card-bg); font-size: 13px; font-weight: 600; cursor: pointer; color: var(--text-main); transition: 0.2s; }
        .cat-filter.active { background: var(--orange); color: white; border-color: var(--orange); }
        .cat-filter:hover:not(.active) { border-color: var(--orange); color: var(--orange); }

        /* TABLE */
        .table-card { background: var(--card-bg); border: 2px solid var(--border); border-radius: 18px; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 16px 20px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; background: var(--bg); border-bottom: 2px solid var(--border); text-align: left; }
        td { padding: 14px 20px; font-size: 13px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: var(--bg); }
        .prod-img { width: 52px; height: 52px; border-radius: 12px; object-fit: cover; border: 2px solid var(--border); }
        .prod-name { font-weight: 700; font-size: 14px; }
        .prod-desc { font-size: 11px; color: var(--text-muted); margin-top: 2px; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cat-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; background: var(--light-orange); color: var(--orange); }
        .price-cell { font-weight: 800; color: var(--orange); font-size: 14px; }
        .action-btns { display: flex; gap: 8px; }
        .edit-btn { padding: 7px 16px; border-radius: 10px; border: 2px solid var(--orange); background: transparent; color: var(--orange); font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .edit-btn:hover { background: var(--orange); color: white; }
        .del-btn { width: 34px; height: 34px; border-radius: 10px; border: 2px solid var(--border); background: transparent; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .del-btn:hover { border-color: var(--red); color: var(--red); background: #FFF5F5; }
        .del-btn i { width: 15px; height: 15px; }
        .empty-row td { text-align: center; padding: 60px; color: var(--text-muted); }

        /* MODAL */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal.open { display: flex; }
        .modal-box { background: var(--card-bg); width: 520px; max-width: 95vw; max-height: 90vh; overflow-y: auto; border-radius: 24px; padding: 35px; animation: slideUp 0.25s ease; }
        @keyframes slideUp { from { transform: translateY(16px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-title { font-size: 20px; font-weight: 800; margin-bottom: 28px; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 11px; font-weight: 800; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 12px 15px; border-radius: 12px; border: 2px solid var(--border);
            outline: none; font-size: 14px; font-weight: 500; background: var(--input-bg);
            color: var(--text-main); transition: border 0.2s; font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--orange); }
        .form-group textarea { resize: none; height: 90px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }

        /* Image preview */
        .img-preview-wrap { position: relative; width: 100%; height: 140px; border: 2px dashed var(--border); border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden; transition: border 0.2s; }
        .img-preview-wrap:hover { border-color: var(--orange); }
        .img-preview-wrap img { width: 100%; height: 100%; object-fit: contain; }
        .img-placeholder { text-align: center; color: var(--text-muted); }
        .img-placeholder i { width: 32px; height: 32px; margin-bottom: 8px; }
        .img-placeholder p { font-size: 12px; font-weight: 600; }

        .modal-footer { display: flex; gap: 12px; margin-top: 24px; }
        .cancel-btn { padding: 13px 24px; border-radius: 12px; border: 2px solid var(--border); background: transparent; font-weight: 700; font-size: 14px; cursor: pointer; color: var(--text-main); transition: 0.2s; }
        .cancel-btn:hover { border-color: var(--orange); color: var(--orange); }
        .submit-btn { flex: 1; background: var(--orange); color: white; border: none; padding: 13px; border-radius: 12px; font-weight: 800; font-size: 14px; cursor: pointer; transition: 0.2s; }
        .submit-btn:hover { background: #e07a42; }

        /* Success toast */
        .toast { position: fixed; bottom: 30px; right: 30px; background: #27AE60; color: white; padding: 14px 22px; border-radius: 14px; font-weight: 700; font-size: 14px; display: none; z-index: 9999; animation: slideUp 0.3s ease; }
        .toast.show { display: block; }
        .nav-badge { background: var(--orange); color: white; font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 20px; margin-left: auto; }
        .stock-badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .stock-unlimited { background:#F3F4F6; color:#6B7280; }
        .stock-ok { background:#E6F7ED; color:#27AE60; }
        .stock-low { background:#FFF9E6; color:#D4A017; }
        .stock-out { background:#FFF0F0; color:#EB5757; }
        [data-theme="dark"] .stock-unlimited { background:rgba(107,114,128,0.15); }
        [data-theme="dark"] .stock-ok { background:rgba(39,174,96,0.15); }
        [data-theme="dark"] .stock-low { background:rgba(212,160,23,0.15); }
        [data-theme="dark"] .stock-out { background:rgba(235,87,87,0.15); }
    </style>
</head>
<body>

    <div class="sidebar-left">
        <div class="logo">Purr'<span>Coffee</span></div>
        <div class="nav-group">
            <a href="admin.php" class="nav-item"><i data-lucide="layout-dashboard"></i> Dashboard</a>
            <a href="manage_products.php" class="nav-item active"><i data-lucide="coffee"></i> Products</a>
            <?php $pendingCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(); ?>
            <a href="admin_orders.php" class="nav-item"><i data-lucide="shopping-cart"></i> Orders <?php if($pendingCount > 0): ?><span class="nav-badge"><?= $pendingCount ?></span><?php endif; ?></a>
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
                <div class="page-title">Product Inventory</div>
                <div class="page-sub">Manage your menu items — <?= $total ?> products total</div>
            </div>
            <button class="add-btn" onclick="openModal()">
                <i data-lucide="plus"></i> Add Product
            </button>
        </div>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-chip">
                <div class="stat-chip-icon"><i data-lucide="package"></i></div>
                <div>
                    <div class="stat-chip-val"><?= $total ?></div>
                    <div class="stat-chip-label">Total Products</div>
                </div>
            </div>
            <?php foreach($byCategory as $cat => $count): ?>
            <div class="stat-chip">
                <div class="stat-chip-icon"><i data-lucide="tag"></i></div>
                <div>
                    <div class="stat-chip-val"><?= $count ?></div>
                    <div class="stat-chip-label"><?= $cat ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- FILTER BAR -->
        <div class="filter-bar">
            <div class="search-box">
                <i data-lucide="search"></i>
                <input type="text" id="searchInput" placeholder="Search products..." oninput="filterProducts()">
            </div>
            <button class="cat-filter active" onclick="filterCat('all', this)">All</button>
            <?php foreach($categories as $cat): ?>
            <button class="cat-filter" onclick="filterCat('<?= $cat ?>', this)"><?= $cat ?></button>
            <?php endforeach; ?>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Small</th>
                        <th>Medium</th>
                        <th>Large</th>
                        <th>Stock</th>
                        <th>Sold</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productTable">
                    <?php foreach($products as $p): ?>
                    <tr class="product-row" 
                        data-name="<?= strtolower($p['name']) ?>" 
                        data-cat="<?= $p['category'] ?>"
                        data-id="<?= $p['id'] ?>"
                        data-fullname="<?= htmlspecialchars($p['name']) ?>"
                        data-price="<?= $p['price'] ?>"
                        data-pricem="<?= $p['price_m'] ?? '' ?>"
                        data-pricel="<?= $p['price_l'] ?? '' ?>"
                        data-desc="<?= htmlspecialchars($p['description'] ?? '') ?>"
                        data-img="<?= htmlspecialchars($p['image_url'] ?? '') ?>"
                        data-stock="<?= $p['stock'] ?? '' ?>">
                        <td>
                            <div style="display:flex; align-items:center; gap:14px;">
                                <img src="<?= htmlspecialchars($p['image_url'] ?? 'placeholder.jpg') ?>" class="prod-img" onerror="this.onerror=null;this.src='placeholder.jpg';">
                                <div>
                                    <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="prod-desc"><?= htmlspecialchars($p['description'] ?? '') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="cat-badge"><?= $p['category'] ?></span></td>
                        <td class="price-cell">₱<?= number_format($p['price'], 2) ?></td>
                        <td class="price-cell"><?= isset($p['price_m']) && $p['price_m'] ? '₱'.number_format($p['price_m'],2) : '<span style="color:var(--text-muted)">—</span>' ?></td>
                        <td class="price-cell"><?= isset($p['price_l']) && $p['price_l'] ? '₱'.number_format($p['price_l'],2) : '<span style="color:var(--text-muted)">—</span>' ?></td>
                        <td>
                            <?php if (!isset($p['stock']) || $p['stock'] === null): ?>
                                <span class="stock-badge stock-unlimited">∞</span>
                            <?php elseif ($p['stock'] == 0): ?>
                                <span class="stock-badge stock-out">Sold Out</span>
                            <?php elseif ($p['stock'] <= 5): ?>
                                <span class="stock-badge stock-low"><?= $p['stock'] ?></span>
                            <?php else: ?>
                                <span class="stock-badge stock-ok"><?= $p['stock'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:700; color:var(--orange);"><?= $salesMap[$p['id']] ?? 0 ?></td>
                        <td>
                            <div class="action-btns">
                                <button class="edit-btn" onclick="openModal(this.closest('tr'))">Edit</button>
                                <button class="del-btn" onclick="deleteProduct(<?= $p['id'] ?>)">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($products)): ?>
                    <tr class="empty-row"><td colspan="8">No products yet. Click "Add Product" to get started.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL -->
    <div id="productModal" class="modal" onclick="if(event.target===this)closeModal()">
        <div class="modal-box">
            <div class="modal-title" id="modalTitle">Add New Product</div>
            <form action="save_product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="form_id">

                <!-- Image Upload -->
                <div class="form-group">
                    <label>Product Image</label>
                    <div class="img-preview-wrap" onclick="document.getElementById('imgInput').click()">
                        <img id="imgPreview" src="" style="display:none;">
                        <div class="img-placeholder" id="imgPlaceholder">
                            <i data-lucide="image"></i>
                            <p>Click to upload image</p>
                        </div>
                    </div>
                    <input type="file" id="imgInput" name="image" accept="image/*" style="display:none" onchange="previewImg(event)">
                </div>

                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" id="form_name" placeholder="e.g. Iced Caramel Macchiato" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="form_cat">
                            <?php foreach($categories as $cat): ?>
                            <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Small Price (₱)</label>
                        <input type="number" name="price" id="form_price" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-row" id="sizeRow">
                    <div class="form-group">
                        <label>Medium Price (₱)</label>
                        <input type="number" name="price_m" id="form_price_m" placeholder="0.00" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Large Price (₱)</label>
                        <input type="number" name="price_l" id="form_price_l" placeholder="0.00" step="0.01" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="form_desc" placeholder="Describe the taste or ingredients..."></textarea>
                </div>

                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" id="form_stock" placeholder="Leave blank for unlimited" min="0" step="1">
                </div>

                <div class="modal-footer">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="submit-btn" id="submitBtn">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script src="theme.js"></script>
    <script>
        lucide.createIcons();

        <?php if(isset($_GET['success'])): ?>
        window.addEventListener('DOMContentLoaded', () => showToast('<?= $_GET['success'] === 'deleted' ? 'Product deleted!' : 'Product saved successfully!' ?>'));
        <?php endif; ?>

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        function openModal(row = null) {
            const modal = document.getElementById('productModal');
            if (row) {
                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('submitBtn').textContent = 'Update Product';
                document.getElementById('form_id').value      = row.dataset.id;
                document.getElementById('form_name').value    = row.dataset.fullname;
                document.getElementById('form_cat').value     = row.dataset.cat;
                document.getElementById('form_price').value   = row.dataset.price;
                document.getElementById('form_price_m').value = row.dataset.pricem;
                document.getElementById('form_price_l').value = row.dataset.pricel;
                document.getElementById('form_desc').value    = row.dataset.desc;
                document.getElementById('form_stock').value   = row.dataset.stock ?? '';
                // Show existing image
                const img = row.dataset.img;
                if (img) {
                    document.getElementById('imgPreview').src = img;
                    document.getElementById('imgPreview').style.display = 'block';
                    document.getElementById('imgPlaceholder').style.display = 'none';
                }
            } else {
                document.getElementById('modalTitle').textContent = 'Add New Product';
                document.getElementById('submitBtn').textContent = 'Save Product';
                document.getElementById('form_id').value      = '';
                document.getElementById('form_name').value    = '';
                document.getElementById('form_price').value   = '';
                document.getElementById('form_price_m').value = '';
                document.getElementById('form_price_l').value = '';
                document.getElementById('form_desc').value    = '';
                document.getElementById('form_stock').value   = '';
                document.getElementById('imgPreview').style.display = 'none';
                document.getElementById('imgPlaceholder').style.display = 'block';
                document.getElementById('imgInput').value = '';
            }
            modal.classList.add('open');
            lucide.createIcons();
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('open');
        }

        function previewImg(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('imgPreview').src = e.target.result;
                document.getElementById('imgPreview').style.display = 'block';
                document.getElementById('imgPlaceholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }

        function deleteProduct(id) {
            if (confirm('Delete this product? This cannot be undone.')) {
                window.location.href = 'delete_product.php?id=' + id;
            }
        }

        let currentCat = 'all';
        function filterCat(cat, btn) {
            currentCat = cat;
            document.querySelectorAll('.cat-filter').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            filterProducts();
        }

        function filterProducts() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.product-row').forEach(row => {
                const matchCat  = currentCat === 'all' || row.dataset.cat === currentCat;
                const matchName = row.dataset.name.includes(search);
                row.style.display = matchCat && matchName ? '' : 'none';
            });
        }
    </script>
</body>
</html>
