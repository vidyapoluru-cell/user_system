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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .navbar { 
            background: #007bff; color: white; padding: 15px 30px; 
            display: flex; justify-content: space-between; align-items: center; 
        }
        .navbar h1 { font-size: 24px; }
        .navbar a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; }
        .navbar a:hover { background: rgba(255,255,255,0.2); }
        .logout { background: #dc3545; }
        .container { max-width: 1000px; margin: 30px auto; padding: 20px; }
        .card { 
            background: white; padding: 30px; border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; 
        }
        .card h2 { color: #333; margin-bottom: 20px; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .info-row { display: flex; padding: 12px 0; border-bottom: 1px solid #eee; }
        .info-label { font-weight: bold; width: 150px; color: #555; }
        .info-value { color: #333; }
        .badge { 
            background: #28a745; color: white; padding: 5px 15px; 
            border-radius: 20px; font-size: 12px; font-weight: bold; 
        }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; 
            color: white; text-decoration: none; border-radius: 4px; margin-top: 15px; 
        }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>👤 User Dashboard</h1>
        <div>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Welcome, <?php echo e($user['full_name'] ?: $user['username']); ?>! 👋</h2>
            
            <div class="info-row">
                <div class="info-label">Username:</div>
                <div class="info-value"><?php echo e($user['username']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><?php echo e($user['email']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Full Name:</div>
                <div class="info-value"><?php echo e($user['full_name'] ?: 'Not provided'); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Role:</div>
                <div class="info-value"><span class="badge">USER</span></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Member Since:</div>
                <div class="info-value"><?php echo e($user['created_at']); ?></div>
            </div>
        </div>
    </div>
</body>
</html>