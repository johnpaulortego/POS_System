<?php
require 'db.php';
$id = intval($_GET['id'] ?? 0);
$format = $_GET['format'] ?? 'html';

// Fetch order
$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$orderStmt->execute([$id]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

// Fetch items
$stmt = $pdo->prepare("SELECT oi.quantity, oi.price_at_sale, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'json') {
    header('Content-Type: application/json');
    $result = [];
    foreach ($items as $i) {
        $result[] = [
            'name'  => $i['name'],
            'qty'   => (int)$i['quantity'],
            'price' => (float)$i['price_at_sale']
        ];
    }
    echo json_encode([
        'order' => $order,
        'items' => $result,
        'order_number' => isset($order['order_number']) && $order['order_number'] ? $order['order_number'] : str_pad($order['id'], 6, '0', STR_PAD_LEFT)
    ]);
    exit;
}

// Fallback HTML
$total = 0;
foreach ($items as $i) $total += $i['price_at_sale'] * $i['quantity'];

if (empty($items)) {
    echo '<div style="text-align:center;color:#8B8B8B;padding:20px 0;">No items found.</div>';
    exit;
}
foreach ($items as $i):
    $line = $i['price_at_sale'] * $i['quantity'];
?>
<div class="modal-item">
    <div>
        <div class="modal-item-name"><?= htmlspecialchars($i['name']) ?></div>
        <div style="font-size:12px;color:#8B8B8B;"><?= $i['quantity'] ?>x @ ₱<?= number_format($i['price_at_sale'],2) ?></div>
    </div>
    <div class="modal-item-price">₱<?= number_format($line,2) ?></div>
</div>
<?php endforeach; ?>
<div style="display:flex;justify-content:space-between;margin-top:16px;padding-top:14px;border-top:2px solid #BEBEBE;font-weight:800;font-size:15px;">
    <span>Total</span>
    <span style="color:#F28B50;">₱<?= number_format($total,2) ?></span>
</div>
