<?php
session_start();

$host = '127.0.0.1';
$db   = 'user_system';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('DB connection failed: ' . $e->getMessage());
}

// Helper functions
function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function is_logged_in() { return isset($_SESSION['user']); }
function is_admin() { return is_logged_in() && ($_SESSION['user']['role'] === 'admin'); }

function require_login() { 
    if(!is_logged_in()) { 
        header('Location: login.php'); 
        exit; 
    } 
}

function require_admin() { 
    if(!is_admin()) { 
        header('Location: login.php'); 
        exit; 
    } 
}

// Create default admin if no users exist
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
if($stmt->fetchColumn() == 0) {
    $hash = password_hash('Admin@123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (username,email,password,full_name,role) VALUES ('admin','admin@example.com','$hash','Site Admin','admin')");
}
?>