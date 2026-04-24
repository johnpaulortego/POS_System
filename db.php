<?php
$host = 'localhost';
$db   = 'purr_coffee_pos';
$user = 'root'; 
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Sync MySQL timezone with PHP timezone
    $offset = (new DateTime())->format('P');
    $pdo->exec("SET time_zone = '$offset'");
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
