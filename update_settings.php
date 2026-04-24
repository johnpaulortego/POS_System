<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = null;
$success = false;

// Handle profile picture upload
$profile_pic = null;
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_image']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed) && $_FILES['profile_image']['size'] <= 2097152) { // 2MB max
        $new_filename = 'uploads/profile_' . $user_id . '_' . time() . '.' . $ext;
        
        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $new_filename)) {
            $profile_pic = $new_filename;
        } else {
            $error = "Failed to upload image";
        }
    } else {
        $error = "Invalid file type or size too large (max 2MB)";
    }
}

// Update user information
try {
    if ($profile_pic) {
        // Check if profile_pic column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'profile_pic'");
        $stmt->execute();
        $columnExists = $stmt->fetch();
        
        if ($columnExists) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, profile_pic = ? WHERE id = ?");
            $stmt->execute([$_POST['username'], $profile_pic, $user_id]);
        } else {
            // Column doesn't exist, just update username
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$_POST['username'], $user_id]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->execute([$_POST['username'], $user_id]);
    }
    
    // Update session username
    $_SESSION['username'] = $_POST['username'];
    $success = true;
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Redirect back with status
if ($success) {
    header("Location: settings.php?success=profile_updated");
} else {
    header("Location: settings.php?error=" . urlencode($error));
}
exit;
?>
