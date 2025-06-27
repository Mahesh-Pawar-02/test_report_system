<?php
require_once 'includes/config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$success = '';
$error = '';
$edit_user = null;
// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    try {
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$username, $email, $password, $role]);
        $success = 'User "' . htmlspecialchars($username) . '" added successfully.';
        header('Location: users.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
// Delete user
if (isset($_POST['delete_user']) && !empty($_POST['delete_id'])) {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$_POST['delete_id']]);
    $success = 'User deleted successfully.';
    header('Location: users.php');
    exit;
}
// Edit user
if (isset($_POST['edit_user']) && !empty($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $edit_user = $pdo->query("SELECT * FROM users WHERE id = " . (int)$edit_id)->fetch(PDO::FETCH_ASSOC);
}
// Update user
if (isset($_POST['update_user']) && !empty($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    try {
        if ($password) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET username=?, email=?, password=?, role=? WHERE id=?');
            $stmt->execute([$username, $email, $hashed, $role, $user_id]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET username=?, email=?, role=? WHERE id=?');
            $stmt->execute([$username, $email, $role, $user_id]);
        }
        $success = 'User updated successfully.';
        header('Location: users.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
// Fetch users from DB
$stmt = $pdo->query('SELECT * FROM users');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Test Report System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .table thead th { vertical-align: middle; }
        .action-btns .btn { margin-right: 0.25rem; }
        .card-header { background: #f59e0b; color: #fff; font-weight: 600; font-size: 1.1rem; }
        .search-box { max-width: 220px; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="mb-4">
            <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Master Data User</span>
                <div>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus"></i> Add</button>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-end mb-2">
                    <input type="text" class="form-control search-box" placeholder="Search...">
                </div>
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th style="width:100px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $i => $user): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td class="action-btns">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="edit_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="edit_user" class="btn btn-warning btn-sm" title="Edit"><i class="bi bi-pencil"></i></button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this user?');"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>Showing 1 to <?php echo count($users); ?> of <?php echo count($users); ?> entries</div>
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select">
                                    <option value="User">User</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Edit User Modal -->
        <?php if ($edit_user): ?>
        <div class="modal fade show" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-modal="true" style="display:block; background:rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                            <a href="users.php" class="btn-close"></a>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password <small>(leave blank to keep unchanged)</small></label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select">
                                    <option value="User" <?php if($edit_user['role']==='User') echo 'selected'; ?>>User</option>
                                    <option value="Admin" <?php if($edit_user['role']==='Admin') echo 'selected'; ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>document.body.classList.add('modal-open');</script>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 