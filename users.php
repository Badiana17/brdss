<?php
session_start();
require 'config.php';

/** @var \PDO $pdo */

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Role check: Only Super Admin can manage users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin') {
    http_response_code(403);
    die('Access denied.');
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF
        $postToken = filter_input(INPUT_POST, 'csrf_token', FILTER_UNSAFE_RAW) ?? '';
        if (!hash_equals($_SESSION['csrf_token'], (string)$postToken)) {
            throw new RuntimeException('Invalid request token.');
        }

        $allowedRoles = ['Super Admin', 'Admin', 'Staff'];

        if (isset($_POST['create'])) {
            $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $password = $_POST['password'] ?? '';
            $role = trim((string)filter_input(INPUT_POST, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

            if ($username === '' || $password === '' || !in_array($role, $allowedRoles, true)) {
                throw new RuntimeException('Invalid input for creating user.');
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");
            $stmt->execute([':username' => $username, ':password' => $hashed, ':role' => $role]);
            $newId = (int)$pdo->lastInsertId();

            if (function_exists('logActivity')) {
                try { logActivity((int)$_SESSION['user_id'], 'Created user', 'users', $newId); } catch (Throwable $e) { error_log($e->getMessage()); }
            }

            $message = 'User created.';

        } elseif (isset($_POST['update'])) {

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $password = $_POST['password'] ?? '';
            $role = trim((string)filter_input(INPUT_POST, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

            if ($id <= 0 || $username === '' || !in_array($role, $allowedRoles, true)) {
                throw new RuntimeException('Invalid input for update.');
            }

            if ($password !== '') {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = :username, password = :password, role = :role WHERE user_id = :id");
                $stmt->execute([':username' => $username, ':password' => $hashed, ':role' => $role, ':id' => $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = :username, role = :role WHERE user_id = :id");
                $stmt->execute([':username' => $username, ':role' => $role, ':id' => $id]);
            }

            if (function_exists('logActivity')) {
                try { logActivity((int)$_SESSION['user_id'], 'Updated user', 'users', $id); } catch (Throwable $e) { error_log($e->getMessage()); }
            }

            $message = 'User updated.';

        } elseif (isset($_POST['delete'])) {

            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                throw new RuntimeException('Invalid user id.');
            }
            if ($id === (int)$_SESSION['user_id']) {
                throw new RuntimeException('You cannot delete your own account.');
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id");
            $stmt->execute([':id' => $id]);

            if (function_exists('logActivity')) {
                try { logActivity((int)$_SESSION['user_id'], 'Deleted user', 'users', $id); } catch (Throwable $e) { error_log($e->getMessage()); }
            }

            $message = 'User deleted.';
        }
    }
} catch (Throwable $e) {
    error_log('Users operation error: ' . $e->getMessage());
    $message = $e->getMessage();
}

// Fetch users
try {
    $stmt = $pdo->query("SELECT user_id, username, role, is_active FROM users ORDER BY user_id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Fetch users error: ' . $e->getMessage());
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - BRDSS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
        }
        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-wrapper">
            <h2>👤 Users Management</h2>
            <p class="text-muted mb-4">Manage system users and their roles</p>

            <?php if ($message): ?>
                <div class="alert <?php echo ($message === 'User created.' || $message === 'User updated.' || $message === 'User deleted.') ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">➕ Add User</button>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo (int)$u['user_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $u['role'] === 'Super Admin' ? 'danger' : ($u['role'] === 'Admin' ? 'primary' : 'secondary'); 
                                        ?>">
                                            <?php echo htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $u['is_active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $u['is_active'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning"
                                            onclick='openEditModal(<?php echo json_encode($u, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'
                                        >Edit</button>

                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="id" value="<?php echo (int)$u['user_id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-danger"<?php echo ((int)$u['user_id'] === (int)$_SESSION['user_id']) ? ' disabled' : ''; ?>>Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Create Modal -->
            <div class="modal fade" id="createModal" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" class="modal-content">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role *</label>
                                <select name="role" class="form-select" required>
                                    <option value="Super Admin">Super Admin</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Staff">Staff</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="create" class="btn btn-primary">Save User</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" class="modal-content">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="editId">
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" id="editUsername" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" id="editPassword" class="form-control" placeholder="Leave blank to keep current password">
                                <small class="text-muted">Only fill this if you want to change the password</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role *</label>
                                <select name="role" id="editRole" class="form-select" required>
                                    <option value="Super Admin">Super Admin</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Staff">Staff</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update" class="btn btn-primary">Update User</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-dismiss alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    function openEditModal(user) {
        document.getElementById('editId').value = user.user_id || '';
        document.getElementById('editUsername').value = user.username || '';
        document.getElementById('editRole').value = user.role || '';
        document.getElementById('editPassword').value = '';
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
    </script>
</body>
</html>