<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$product_id = $_GET['id'] ?? null;

if ($product_id) {
    // Delete the product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    
    header("Location: manage_products.php?success=deleted");
} else {
    header("Location: manage_products.php?error=invalid_id");
}
exit;
?>
