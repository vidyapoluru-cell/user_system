<?php
require_once 'config.php';

// Redirect if already logged in
if(is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(!$username || !$password) {
        $errors[] = 'Username and password required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'user'");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Invalid credentials or not a user account.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login - User Account</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;
        }
        button {
            width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px;
            font-size: 16px; cursor: pointer; margin-top: 10px;
        }
        button:hover { background: #0056b3; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .link { text-align: center; margin-top: 15px; color: #666; }
        .link a { color: #28a745; text-decoration: none; }
        .link a:hover { text-decoration: underline; }
        .admin-link { text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; }
        .admin-link a { color: #ffc107; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Login</h2>
        
        <?php foreach($errors as $err): ?>
            <div class="error"><?php echo e($err); ?></div>
        <?php endforeach; ?>
        
        <?php if(isset($_GET['registered'])): ?>
            <div class="error" style="background: #d4edda; color: #155724;">Registration successful! Please login.</div>
        <?php endif; ?>
        
        <?php if(isset($_GET['logout'])): ?>
            <div class="success" style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px;">Logged out successfully!</div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required value="<?php echo e($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">Login as User</button>
        </form>
        
        <div class="link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
        
        <div class="admin-link">
            <a href="admin_login.php">Admin Login →</a>
        </div>
    </div>
</body>
</html>