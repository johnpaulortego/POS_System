<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['cart'])) {
    echo json_encode(['success' => false, 'error' => 'Empty cart']);
    exit;
}

// Calculate total server-side from cart items for accuracy
$calculatedTotal = 0;
foreach ($data['cart'] as $item) {
    if (!isset($item['qty']) || $item['qty'] < 1) continue;
    if (!isset($item['price']) || $item['price'] <= 0) continue;
    $calculatedTotal += floatval($item['price']) * intval($item['qty']);
}

$clientTotal = isset($data['total']) ? floatval($data['total']) : 0;
$total = $clientTotal > 0 ? $clientTotal : $calculatedTotal;

if ($total <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid total amount']);
    exit;
}

try {
    // Add order_number column if it doesn't exist
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS order_number INT DEFAULT NULL");
    // Add stock column if it doesn't exist
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS stock INT DEFAULT NULL");

    $orderNumber = isset($data['order_number']) ? intval($data['order_number']) : rand(100000, 999999);

    $pdo->beginTransaction();

    // Stock validation and decrement for each cart item
    foreach ($data['cart'] as $item) {
        if (!isset($item['qty']) || $item['qty'] < 1) continue;
        if (!isset($item['price']) || $item['price'] <= 0) continue;

        $qty = intval($item['qty']);
        $productId = intval($item['id']);

        // Lock the row and read current stock
        $stockStmt = $pdo->prepare("SELECT stock, name FROM products WHERE id = ? FOR UPDATE");
        $stockStmt->execute([$productId]);
        $product = $stockStmt->fetch(PDO::FETCH_ASSOC);

        if ($product && $product['stock'] !== null) {
            $currentStock = intval($product['stock']);
            if ($currentStock < $qty) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'error' => 'Insufficient stock for ' . $product['name'] . ' (only ' . $currentStock . ' left)'
                ]);
                exit;
            }
            // Decrement stock
            $decrStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $decrStmt->execute([$qty, $productId, $qty]);
        }
        // NULL stock = unlimited, skip decrement
    }

    // Insert order
    $stmt = $pdo->prepare("INSERT INTO orders (cashier_id, total_amount, order_number, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
    $stmt->execute([$_SESSION['user_id'], $total, $orderNumber]);
    $oid = $pdo->lastInsertId();

    // Insert order items
    $stmtI = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_sale) VALUES (?, ?, ?, ?)");
    foreach ($data['cart'] as $item) {
        if (!isset($item['qty']) || $item['qty'] < 1) continue;
        if (!isset($item['price']) || $item['price'] <= 0) continue;
        $stmtI->execute([$oid, intval($item['id']), intval($item['qty']), floatval($item['price'])]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'order_id' => $oid, 'total' => $total]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
