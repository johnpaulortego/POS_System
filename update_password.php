<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Validate inputs
if (empty($_POST['old_pass']) || empty($_POST['new_pass']) || empty($_POST['confirm_pass'])) {
    header("Location: settings.php?error=empty");
    exit;
}

if ($_POST['new_pass'] !== $_POST['confirm_pass']) {
    header("Location: settings.php?error=mismatch");
    exit;
}

// Get current user
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Verify old password
$hashMatch = password_verify($_POST['old_pass'], $user['password']);
$plainMatch = ($user['password'] === $_POST['old_pass']);

if (!$hashMatch && !$plainMatch) {
    header("Location: settings.php?error=wrong_password");
    exit;
}

// Update password with hash
$new_password_hash = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$new_password_hash, $user_id]);

header("Location: settings.php?success=password_updated");
exit;
?>
