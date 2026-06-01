<?php
require_once 'config.php';
require_admin();

// Handle DELETE
if(isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$_POST['delete_id']]);
}

// Handle CHANGE ROLE
if(isset($_POST['change_role_id'], $_POST['new_role'])) {
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$_POST['new_role'], $_POST['change_role_id']]);
}

// Handle ADD/EDIT USER
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $id = $_POST['id'] ?? 0;
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = in_array($_POST['role'] ?? 'user', ['user','admin']) ? $_POST['role'] : 'user';
    $password = $_POST['password'] ?? '';
    
    if($id) { // Edit
        $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?");
        $stmt->execute([$username,$email,$full_name,$role,$id]);
        if($password) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash,$id]);
        }
    } else { // Add
        if($password) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username,email,password,full_name,role) VALUES (?,?,?,?,?)");
            $stmt->execute([$username,$email,$hash,$full_name,$role]);
        }
    }
    header('Location: admin.php');
    exit;
}

// SEARCH & PAGINATION
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page-1)*$limit;

$params = [];
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM users";
if($search) {
    $sql .= " WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ?";
    $like = "%$search%";
    $params = [$like,$like,$like];
}
$sql .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$total = (int)$pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$total_pages = (int)ceil($total/$limit);

// Get edit user data
$edit_user = null;
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_user = $stmt->fetch();
}

// Get total users
$total_users = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <style>
        body { font-family: Arial; max-width: 1200px; margin: 20px auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #007bff; color: white; }
        .btn { padding: 5px 10px; color: white; text-decoration: none; border-radius: 3px; margin: 2px; }
        .btn-edit { background: #ffc107; color: black; }
        .btn-delete { background: #dc3545; }
        .btn-role { background: #28a745; }
        .form-group { margin: 10px 0; }
        input, select { padding: 8px; width: 200px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        .logout { background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .search-box { margin: 15px 0; }
        .total { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>Admin Panel</h1>
    <p>Welcome, <strong><?php echo e($user['full_name'] ?: $user['username']); ?></strong> (Admin)</p>
    <a href="index.php">Dashboard</a> | <a href="auth.php?action=logout" class="logout">Logout</a>
    
    <div class="total">
        <strong>Total User Accounts: <?php echo $total_users; ?></strong>
    </div>
    
    <!-- ADD/EDIT USER FORM -->
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
        <h3><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $edit_user['id'] ?? ''; ?>">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required value="<?php echo e($edit_user['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required value="<?php echo e($edit_user['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" value="<?php echo e($edit_user['full_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Password<?php echo $edit_user ? ' (leave blank to keep current)' : ' (required)'; ?>:</label>
                <input type="password" name="password" <?php echo $edit_user ? '' : 'required'; ?>>
            </div>
            <div class="form-group">
                <label>Role:</label>
                <select name="role">
                    <option value="user" <?php echo ($edit_user['role'] ?? '') === 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo ($edit_user['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <button type="submit" name="save_user"><?php echo $edit_user ? 'Update User' : 'Add User'; ?></button>
            <?php if($edit_user): ?>
                <a href="admin.php">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- SEARCH -->
    <div class="search-box">
        <form method="GET">
            <input type="text" name="q" placeholder="Search users..." value="<?php echo e($search); ?>">
            <button type="submit">Search</button>
            <?php if($search): ?>
                <a href="admin.php">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- USERS TABLE -->
    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Full Name</th>
            <th>Role</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
        <?php foreach($users as $u): ?>
        <tr>
            <td><?php echo $u['id']; ?></td>
            <td><?php echo e($u['username']); ?></td>
            <td><?php echo e($u['email']); ?></td>
            <td><?php echo e($u['full_name'] ?: '-'); ?></td>
            <td><?php echo e($u['role']); ?></td>
            <td><?php echo e($u['created_at']); ?></td>
            <td>
                <a href="?edit=<?php echo $u['id']; ?>" class="btn btn-edit">Edit</a>
                
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="change_role_id" value="<?php echo $u['id']; ?>">
                    <select name="new_role" onchange="this.form.submit()">
                        <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </form>
                
                <?php if($u['role'] !== 'admin'): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?')">
                    <input type="hidden" name="delete_id" value="<?php echo $u['id']; ?>">
                    <button type="submit" class="btn btn-delete">Delete</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <!-- PAGINATION -->
    <?php if($total_pages > 1): ?>
    <div>
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?><?php echo $search ? '&q='.urlencode($search) : ''; ?>" 
               style="padding: 5px 10px; margin: 2px; <?php echo $i === $page ? 'background: #007bff; color: white;' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</body>
</html>