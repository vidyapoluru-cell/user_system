<?php
require_once 'config.php';

// Redirect if already logged in
if(is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');

    if(!$username || !$email || !$password) {
        $errors[] = 'Username, email and password required.';
    }
    if($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username,email,password,full_name) VALUES (?,?,?,?)");
            $stmt->execute([$username,$email,$hash,$full_name]);
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Register - User Account</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;
        }
        button {
            width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 4px;
            font-size: 16px; cursor: pointer; margin-top: 10px;
        }
        button:hover { background: #218838; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .link { text-align: center; margin-top: 15px; color: #666; }
        .link a { color: #007bff; text-decoration: none; }
        .link a:hover { text-decoration: underline; }
        .admin-link { text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; }
        .admin-link a { color: #ffc107; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create Account</h2>
        
        <?php foreach($errors as $err): ?>
            <div class="error"><?php echo e($err); ?></div>
        <?php endforeach; ?>
        
        <?php if($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo e($_POST['full_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Username *</label>
                <input type="text" name="username" required value="<?php echo e($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm" required>
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <div class="link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        
        <div class="admin-link">
            <a href="admin_login.php">Admin Login →</a>
        </div>
    </div>
</body>
</html>