<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$order_id = $_POST['order_id'] ?? null;
$status = $_POST['status'] ?? 'pending';

// Validate status
$allowed_statuses = ['pending', 'completed', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    $status = 'pending';
}

if ($order_id) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
}

// Return JSON if called via fetch, redirect otherwise
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header("Location: admin_orders.php?success=status_updated");
}
exit;
?>
