<?php
session_start();
require 'config.php';
require 'includes/header.php';

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

// Fetch users (exclude password)
try {
    $stmt = $pdo->query("SELECT user_id, username, role, is_active FROM users ORDER BY user_id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Fetch users error: ' . $e->getMessage());
    $users = [];
}
?>
<div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <div class="col-md-9">
        <h2>Users</h2>
        <?php if ($message): ?>
            <div class="alert <?php echo ($message === 'User created.' || $message === 'User updated.' || $message === 'User deleted.') ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">Add User</button>

        <table class="table table-striped">
            <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="5">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo (int)$u['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo $u['is_active'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick='openEditModal(<?php echo json_encode($u, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'>Edit</button>

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

        <!-- Create Modal -->
        <div class="modal fade" id="createModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" class="modal-content">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="modal-header"><h5>Add User</h5></div>
                    <div class="modal-body">
                        <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
                        <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
                        <select name="role" class="form-control mb-2" required>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Admin">Admin</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="create" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" class="modal-content">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="modal-header"><h5>Edit User</h5></div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId">
                        <input type="text" name="username" id="editUsername" class="form-control mb-2" placeholder="Username" required>
                        <input type="password" name="password" id="editPassword" class="form-control mb-2" placeholder="New Password (leave empty to keep current)">
                        <select name="role" id="editRole" class="form-control mb-2" required>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Admin">Admin</option>
                            <option value="Staff">Staff</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="update" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function openEditModal(user) {
    document.getElementById('editId').value = user.user_id || '';
    document.getElementById('editUsername').value = user.username || '';
    document.getElementById('editRole').value = user.role || '';
    document.getElementById('editPassword').value = '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require 'includes/footer.php'; ?>
