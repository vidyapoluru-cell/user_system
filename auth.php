<?php
require_once 'config.php';
$action = $_GET['action'] ?? 'login';
$errors = [];
$success = '';

// REGISTER
if($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');

    if(!$username || !$email || !$password) $errors[] = 'Username, email and password required.';
    if($password !== $confirm) $errors[] = 'Passwords do not match.';
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if($stmt->fetch()) $errors[] = 'Username or email already taken.';
        else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username,email,password,full_name) VALUES (?,?,?,?)");
            $stmt->execute([$username,$email,$hash,$full_name]);
            $success = 'Registration successful! <a href="?action=login">Login here</a>';
        }
    }
}

// LOGIN
if($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(!$username || !$password) $errors[] = 'Username and password required.';
    else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            
            if($user['role'] === 'admin') header('Location: admin.php');
            else header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Invalid credentials.';
        }
    }
}

// LOGOUT
if($action === 'logout') {
    session_unset();
    session_destroy();
    header('Location: auth.php?action=login');
    exit;
}

// Show page
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($action); ?></title>
    <style>
        body { font-family: Arial; max-width: 400px; margin: 50px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; margin: 10px 0; }
        .success { color: green; margin: 10px 0; }
        .link { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <h2><?php echo ucfirst($action); ?></h2>
    
    <?php foreach($errors as $err): ?>
        <div class="error"><?php echo e($err); ?></div>
    <?php endforeach; ?>
    
    <?php if($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if($action === 'register'): ?>
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo e($_POST['full_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required value="<?php echo e($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm" required>
            </div>
            <button type="submit">Register</button>
        </form>
        <div class="link">Already have an account? <a href="?action=login">Login</a></div>
    
    <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="username" required value="<?php echo e($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <div class="link">
            <a href="?action=register">Create account</a> | 
            <a href="?action=logout">Logout</a>
        </div>
        <?php if(isset($_GET['registered'])): ?>
            <div class="success">Registration successful! Login now.</div>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>