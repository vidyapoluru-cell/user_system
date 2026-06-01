<?php
require_once 'config.php';
require_login();
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        .logout { background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Welcome, <?php echo e($user['full_name'] ?: $user['username']); ?>!</h1>
    <p>Your role: <strong><?php echo e($user['role']); ?></strong></p>
    <p>Email: <?php echo e($user['email']); ?></p>
    <p>Member since: <?php echo e($user['created_at']); ?></p>
    
    <?php if($user['role'] === 'admin'): ?>
        <p><a href="admin.php">Go to Admin Panel</a></p>
    <?php endif; ?>
    
    <br>
    <a href="auth.php?action=logout" class="logout">Logout</a>
</body>
</html>