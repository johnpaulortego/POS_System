<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$product_id  = $_POST['product_id'] ?? null;
$name        = trim($_POST['name']);
$category    = $_POST['category'];
$price       = floatval($_POST['price']);
$price_m     = $_POST['price_m'] !== '' ? floatval($_POST['price_m']) : null;
$price_l     = $_POST['price_l'] !== '' ? floatval($_POST['price_l']) : null;
$stock_raw   = $_POST['stock'] ?? '';
$stock       = ($stock_raw !== '' && is_numeric($stock_raw)) ? max(0, intval($stock_raw)) : null;
$description = trim($_POST['description'] ?? '');

// Add price_m / price_l columns if they don't exist
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS price_m DECIMAL(10,2) DEFAULT NULL");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS price_l DECIMAL(10,2) DEFAULT NULL");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT DEFAULT NULL");
} catch (Exception $e) {}

// Handle image upload
$image_url = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $allowed = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 5242880) {
        if (!file_exists('uploads')) mkdir('uploads', 0777, true);
        $filename = 'uploads/product_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filename)) {
            $image_url = $filename;
        }
    }
}

if ($product_id) {
    if ($image_url) {
        $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, price_m=?, price_l=?, description=?, image_url=?, stock=? WHERE id=?");
        $stmt->execute([$name, $category, $price, $price_m, $price_l, $description, $image_url, $stock, $product_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, price=?, price_m=?, price_l=?, description=?, stock=? WHERE id=?");
        $stmt->execute([$name, $category, $price, $price_m, $price_l, $description, $stock, $product_id]);
    }
} else {
    $img = $image_url ?? 'placeholder.jpg';
    $stmt = $pdo->prepare("INSERT INTO products (name, category, price, price_m, price_l, description, image_url, stock) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$name, $category, $price, $price_m, $price_l, $description, $img, $stock]);
}

header("Location: manage_products.php?success=1");
exit;
?>
