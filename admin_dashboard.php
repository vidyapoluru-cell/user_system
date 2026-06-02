<?php
require_once 'config.php';
require_admin();

$admin = $_SESSION['user'];

// === HANDLE DELETE USER ===
if(isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$_POST['delete_id']]);
    header('Location: admin_dashboard.php');
    exit;
}

// === HANDLE CHANGE ROLE ===
if(isset($_POST['change_role_id'], $_POST['new_role'])) {
    $new_role = in_array($_POST['new_role'], ['user','admin']) ? $_POST['new_role'] : 'user';
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$new_role, $_POST['change_role_id']]);
    header('Location: admin_dashboard.php');
    exit;
}

// === HANDLE ADD/EDIT USER ===
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $id = intval($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = in_array($_POST['role'] ?? 'user', ['user','admin']) ? $_POST['role'] : 'user';
    $password = $_POST['password'] ?? '';
    $errors = [];
    
    if(!$username || !$email) {
        $errors[] = 'Username and email required.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    
    if(empty($errors)) {
        if($id) { // EDIT
            $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?");
            $stmt->execute([$username,$email,$full_name,$role,$id]);
            
            if($password) {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash,$id]);
            }
        } else { // ADD
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username,$email]);
            if(!$stmt->fetch()) {
                $hash = password_hash($password ?: bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username,email,password,full_name,role) VALUES (?,?,?,?,?)");
                $stmt->execute([$username,$email,$hash,$full_name,$role]);
            }
        }
        header('Location: admin_dashboard.php');
        exit;
    }
}

// === SEARCH & PAGINATION ===
$search = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page-1)*$limit;

$params = [];
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM users";
if($search) {
    $sql .= " WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like];
}
$sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$total = (int)$pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$total_pages = (int)ceil($total / $limit);

// === GET TOTAL USERS ===
$total_users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_admins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$total_regular = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

// === GET EDIT USER DATA ===
$edit_user = null;
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit_user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .navbar { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; padding: 15px 30px; 
            display: flex; justify-content: space-between; align-items: center; 
        }
        .navbar h1 { font-size: 24px; }
        .navbar a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; margin-left: 10px; }
        .navbar a:hover { background: rgba(255,255,255,0.2); }
        .logout { background: #dc3545; }
        .container { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { 
            background: white; padding: 20px; border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; 
        }
        .stat-card h3 { font-size: 36px; margin-bottom: 5px; }
        .stat-card p { color: #666; font-size: 14px; }
        .stat-total { color: #667eea; }
        .stat-admin { color: #ffc107; }
        .stat-user { color: #28a745; }
        .card { 
            background: white; padding: 25px; border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; 
        }
        .card h2 { color: #333; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group { margin: 10px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 14px; }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;
        }
        button { 
            padding: 12px 25px; background: #667eea; color: white; border: none; 
            border-radius: 4px; font-size: 14px; cursor: pointer; 
        }
        button:hover { background: #5568d3; }
        .btn-cancel { background: #6c757d; }
        .btn-cancel:hover { background: #5a6268; }
        .search-box { display: flex; gap: 10px; margin: 15px 0; }
        .search-box input { flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; }
        tr:hover { background: #f8f9fa; }
        .badge { 
            padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; 
        }
        .badge-admin { background: #ffc107; color: #333; }
        .badge-user { background: #28a745; color: white; }
        .action-btn { 
            padding: 6px 12px; margin: 2px; text-decoration: none; border-radius: 4px; 
            font-size: 12px; display: inline-block; border: none; cursor: pointer; 
        }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-delete { background: #dc3545; color: white; }
        .pagination { margin: 20px 0; text-align: center; }
        .pagination a { 
            padding: 8px 15px; margin: 2px; background: #667eea; color: white; 
            text-decoration: none; border-radius: 4px; 
        }
        .pagination a:hover { background: #5568d3; }
        .pagination a.active { background: #764ba2; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🛡️ Admin Dashboard</h1>
        <div>
            <span>Welcome, <?php echo e($admin['username']); ?></span>
            <a href="index.php">User View</a>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <!-- STATISTICS -->
        <div class="stats">
            <div class="stat-card">
                <h3 class="stat-total"><?php echo $total_users; ?></h3>
                <p>Total User Accounts</p>
            </div>
            <div class="stat-card">
                <h3 class="stat-admin"><?php echo $total_admins; ?></h3>
                <p>Admins</p>
            </div>
            <div class="stat-card">
                <h3 class="stat-user"><?php echo $total_regular; ?></h3>
                <p>Regular Users</p>
            </div>
        </div>
        
        <!-- ADD/EDIT USER FORM -->
        <div class="card">
            <h2><?php echo $edit_user ? '✏️ Edit User' :➕ Add New User'; ?></h2>
            
            <?php if(isset($errors) && count($errors)): ?>
                <?php foreach($errors as $err): ?>
                    <div class="error"><?php echo e($err); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $edit_user['id'] ?? ''; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required 
                               value="<?php echo e($edit_user['username'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required 
                               value="<?php echo e($edit_user['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" 
                               value="<?php echo e($edit_user['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role">
                            <option value="user" <?php echo ($edit_user['role'] ?? '') === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo ($edit_user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password <?php echo $edit_user ? '(leave blank to keep current)' : '*'; ?></label>
                    <input type="password" name="password" <?php echo $edit_user ? '' : 'required'; ?>>
                </div>
                
                <button type="submit" name="save_user">
                    <?php echo $edit_user ? '💾 Update User' : '➕ Add User'; ?>
                </button>
                
                <?php if($edit_user): ?>
                    <button type="button" class="btn-cancel" onclick="window.location='admin_dashboard.php'">Cancel</button>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- SEARCH -->
        <div class="card">
            <h2>🔍 Search Users</h2>
            <form class="search-box" method="GET">
                <input type="text" name="q" placeholder="Search by username, email, or name..." 
                       value="<?php echo e($search); ?>">
                <button type="submit">Search</button>
                <?php if($search): ?>
                    <a href="admin_dashboard.php" class="action-btn btn-cancel">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- USERS TABLE -->
        <div class="card">
            <h2>👥 All Registered Users (<?php echo count($users); ?>)</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo e($u['username']); ?></td>
                        <td><?php echo e($u['email']); ?></td>
                        <td><?php echo e($u['full_name'] ?: '-'); ?></td>
                        <td>
                            <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                <?php echo strtoupper($u['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <a href="?edit=<?php echo $u['id']; ?>" class="action-btn btn-edit">Edit</a>
                            
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="change_role_id" value="<?php echo $u['id']; ?>">
                                <select name="new_role" onchange="this.form.submit()" style="padding: 6px;">
                                    <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </form>
                            
                            <?php if($u['role'] !== 'admin'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('⚠️ Delete this user?')">
                                <input type="hidden" name="delete_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="action-btn btn-delete">Delete</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- PAGINATION -->
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&q='.urlencode($search) : ''; ?>" 
                       class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>