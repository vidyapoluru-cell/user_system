<?php
require_once 'config.php';

// Redirect if already admin logged in
if(is_admin()) {
    header('Location: admin_dashboard.php');
    exit;
}

$errors = [];

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(!$username || !$password) {
        $errors[] = 'Username and password required.';
    } else {
        // Only accept admin role
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'admin'");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $errors[] = 'Invalid admin credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex; justify-content: center; align-items: center; min-height: 100vh; 
        }
        .container { 
            background: white; padding: 40px; border-radius: 10px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 420px; 
        }
        h2 { text-align: center; margin-bottom: 10px; color: #333; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; font-size: 14px; }
        .admin-badge { 
            background: #ffc107; color: #333; padding: 5px 15px; border-radius: 20px; 
            display: inline-block; margin-bottom: 20px; font-size: 12px; font-weight: bold;
        }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 4px; font-size: 14px;
            transition: border 0.3s;
        }
        input:focus { outline: none; border-color: #667eea; }
        button {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; border-radius: 4px; font-size: 16px; font-weight: bold;
            cursor: pointer; margin-top: 10px; transition: transform 0.2s;
        }
        button:hover { transform: translateY(-2px); }
        .error { 
            background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; 
            margin: 15px 0; border-left: 4px solid #dc3545;
        }
        .link { text-align: center; margin-top: 20px; color: #666; }
        .link a { color: #667eea; text-decoration: none; font-weight: bold; }
        .link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <center>
            <span class="admin-badge">🔐 ADMIN AREA</span>
        </center>
        <h2>Admin Login</h2>
        <p class="subtitle">Authorized personnel only</p>
        
        <?php foreach($errors as $err): ?>
            <div class="error"><?php echo e($err); ?></div>
        <?php endforeach; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Admin Username or Email</label>
                <input type="text" name="username" required value="<?php echo e($_POST['username'] ?? ''); ?>" placeholder="Enter admin username">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter admin password">
            </div>
            
            <button type="submit">🔑 Login as Admin</button>
        </form>
        
        <div class="link">
            <a href="login.php">← User Login</a> | 
            <a href="register.php">User Registration</a>
        </div>
    </div>
</body>
</html>