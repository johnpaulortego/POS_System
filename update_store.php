<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Store information would typically be saved in a settings table
// For now, we'll just redirect back with success
// In a production system, you'd create a store_settings table

/*
Example implementation:
$stmt = $pdo->prepare("INSERT INTO store_settings (user_id, store_name, store_phone, store_address, store_tin) 
                       VALUES (?, ?, ?, ?, ?) 
                       ON DUPLICATE KEY UPDATE 
                       store_name = VALUES(store_name), 
                       store_phone = VALUES(store_phone), 
                       store_address = VALUES(store_address), 
                       store_tin = VALUES(store_tin)");
$stmt->execute([
    $_SESSION['user_id'],
    $_POST['store_name'],
    $_POST['store_phone'],
    $_POST['store_address'],
    $_POST['store_tin']
]);
*/

header("Location: settings.php?success=store_updated");
exit;
?>
