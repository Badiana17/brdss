<?php
session_start();
require 'config.php';
require 'includes/header.php';

// Role check: Only Super Admin can manage users
if ($_SESSION['role'] !== 'Super Admin') {
    die("Access denied.");
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['username'], $hashed_password, $_POST['role']]);
        logActivity($pdo, $_SESSION['user_id'], 'Created user', 'users', $pdo->lastInsertId());
        $message = 'User created.';
    } elseif (isset($_POST['update'])) {
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, role=? WHERE user_id=?");
        $stmt->execute([$_POST['username'], $hashed_password, $_POST['role'], $_POST['id']]);
        logActivity($pdo, $_SESSION['user_id'], 'Updated user', 'users', $_POST['id']);
        $message = 'User updated.';
    } elseif (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->execute([$_POST['id']]);
        logActivity($pdo, $_SESSION['user_id'], 'Deleted user', 'users', $_POST['id']);
        $message = 'User deleted.';
    }
}

$users = $pdo->query("SELECT * FROM users")->fetchAll();
?>
<div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <div class="col-md-9">
        <h2>Users</h2>
        <?php if ($message) echo "<div class='alert alert-success'>$message</div>"; ?>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">Add User</button>
        <table class="table">
            <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo $u['user_id']; ?></td>
                    <td><?php echo $u['username']; ?></td>
                    <td><?php echo $u['role']; ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $u['user_id']; ?>, '<?php echo $u['username']; ?>', '<?php echo $u['role']; ?>')">Edit</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $u['user_id']; ?>">
                            <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Create Modal -->
        <div class="modal fade" id="createModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST">
                    <div class="modal-content">
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
                    </div>
                </form>
            </div>
        </div>
        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST">
                    <div class="modal-content">
                        <div class="modal-header"><h5>Edit User</h5></div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="editId">
                            <input type="text" name="username" id="editUsername" class="form-control mb-2" placeholder="Username" required>
                            <input type="password" name="password" id="editPassword" class="form-control mb-2" placeholder="New Password" required>
                            <select name="role" id="editRole" class="form-control mb-2" required>
                                <option value="Super Admin">Super Admin</option>
                                <option value="Admin">Admin</option>
                                <option value="Staff">Staff</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function editUser(id, username, role) {
    document.getElementById('editId').value = id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editRole').value = role;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php require 'includes/footer.php'; ?>