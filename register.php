<?php
/**
 * User Registration - BRDSS
 * Admin/SuperAdmin only - Register new users with role assignment
 */
require_once 'config.php';

/** @var \PDO $pdo */

session_start();

$error = '';
$success = '';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// Check if user is logged in and is admin (for viewing users list)
$isAdmin = false;
if (!empty($_SESSION['user_id'])) {
    $userRole = $_SESSION['role'] ?? '';
    $isAdmin = ($userRole === 'Admin' || $userRole === 'Super Admin');
}

$pageTitle = $isAdmin ? 'User Registration - BRDSS' : 'Create Account - BRDSS';

// ---------- Handle user registration ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    try {
        $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $email = trim((string)filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $full_name = sanitize(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));
        $role = $_POST['role'] ?? 'Staff';

        // Validation
        if (!$username || strlen($username) < 3) {
            throw new Exception('Username must be at least 3 characters long.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address.');
        }

        if (!$password || strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long.');
        }

        if ($password !== $password_confirm) {
            throw new Exception('Passwords do not match.');
        }

        if (strlen(trim($full_name)) < 2) {
            throw new Exception('Full name is required.');
        }

        // Validate role
        $allowedRoles = ['Staff'];
        
        // If admin, allow other roles
        if ($isAdmin) {
            $allowedRoles = ['Staff', 'Admin', 'Super Admin'];
        }
        
        if (!in_array($role, $allowedRoles, true)) {
            throw new Exception('Invalid role selected.');
        }

        // Only SuperAdmin can create SuperAdmin accounts
        if ($role === 'Super Admin' && $_SESSION['role'] !== 'Super Admin') {
            throw new Exception('Only SuperAdmin can create SuperAdmin accounts.');
        }

        // Check if username already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception('Username already exists. Please choose a different one.');
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email already registered. Please use a different one.');
        }
    
        // All validations passed, proceed to create user
        header("refresh:3;url=login.php");

        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users 
                (username, email, password, full_name, role, is_active, created_at)
            VALUES 
                (:username, :email, :password, :full_name, :role, 1, NOW())
        ");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $passwordHash,
            ':full_name' => $full_name,
            ':role' => $role
        ]);

        $userId = (int)$pdo->lastInsertId();
        
        if ($currentUserId > 0) {
            logActivity($currentUserId, "Registered new user: $username ($role)", 'CREATE', 'users', $userId);
            $success = "User '$username' registered successfully as $role.";
        } else {
            $success = "Account created successfully! You can now login.";
            // Auto-redirect after 3 seconds
            header("refresh:3;url=login.php");
        }
        
        // Clear form
        $username = '';
        $email = '';
        $first_name = '';
        $last_name = '';
        $password = '';
        $password_confirm = '';
        $role = 'Staff';
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log('User registration error: ' . $e->getMessage());
    }
}

// Fetch list of registered users (for admin view)
$users = [];
try {
    $stmt = $pdo->query("
        SELECT 
            user_id, 
            username, 
            email, 
            full_name, 
            role, 
            is_active,
            created_at,
            last_login
        FROM users
        ORDER BY created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log('Users fetch error: ' . $e->getMessage());
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo sanitize($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-4">
    <div class="mb-4">
        <h2><?php echo $isAdmin ? 'User Registration' : 'Create Account'; ?></h2>
        <p class="text-muted"><?php echo $isAdmin ? 'Register new staff members and administrators' : 'Create a new account to get started'; ?></p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success:</strong> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Registration Form -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo $isAdmin ? 'Register New User' : 'Create Your Account'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" novalidate>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">First Name *</label>
                                <input 
                                    type="text" 
                                    name="first_name" 
                                    class="form-control" 
                                    required 
                                    value="<?php echo isset($first_name) ? htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8') : ''; ?>"
                                    placeholder="John"
                                >
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name *</label>
                                <input 
                                    type="text" 
                                    name="last_name" 
                                    class="form-control" 
                                    required 
                                    value="<?php echo isset($last_name) ? htmlspecialchars($last_name, ENT_QUOTES, 'UTF-8') : ''; ?>"
                                    placeholder="Doe"
                                >
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <label class="form-label">Username *</label>
                            <input 
                                type="text" 
                                name="username" 
                                class="form-control" 
                                required 
                                minlength="3"
                                value="<?php echo isset($username) ? htmlspecialchars($username, ENT_QUOTES, 'UTF-8') : ''; ?>"
                                placeholder="johndoe"
                            >
                            <small class="text-muted">Min 3 characters, alphanumeric</small>
                        </div>

                        <div class="form-group mt-3">
                            <label class="form-label">Email *</label>
                            <input 
                                type="email" 
                                name="email" 
                                class="form-control" 
                                required 
                                value="<?php echo isset($email) ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : ''; ?>"
                                placeholder="john@example.com"
                            >
                        </div>

                        <div class="form-group mt-3">
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-select" required>
                                <option value="Staff" <?php echo ($role === 'Staff') ? 'selected' : ''; ?>>Staff</option>
                                <?php if ($isAdmin): ?>
                                    <option value="Admin" <?php echo ($role === 'Admin') ? 'selected' : ''; ?>>Administrator</option>
                                    <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                                        <option value="Super Admin" <?php echo ($role === 'Super Admin') ? 'selected' : ''; ?>>SuperAdmin</option>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">
                                <strong>Staff:</strong> Basic user<br>
                                <?php if ($isAdmin): ?>
                                    <strong>Admin:</strong> Can manage records & users<br>
                                    <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                                        <strong>SuperAdmin:</strong> Full system access
                                    <?php endif; ?>
                                <?php endif; ?>
                            </small>
                        </div>

                        <div class="form-group mt-3">
                            <label class="form-label">Password *</label>
                            <input 
                                type="password" 
                                name="password" 
                                class="form-control" 
                                required 
                                minlength="8"
                                placeholder="Enter strong password"
                                autocomplete="new-password"
                            >
                            <small class="text-muted">Min 8 characters, mix of letters, numbers, symbols</small>
                        </div>

                        <div class="form-group mt-3">
                            <label class="form-label">Confirm Password *</label>
                            <input 
                                type="password" 
                                name="password_confirm" 
                                class="form-control" 
                                required 
                                minlength="8"
                                placeholder="Re-enter password"
                                autocomplete="new-password"
                            >
                        </div>

                        <button type="submit" name="register" class="btn btn-primary w-100 mt-4">
                            Register User
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Registered Users</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><strong><?php echo sanitize($user['username']); ?></strong></td>
                                            <td><?php echo sanitize($user['full_name']); ?></td>
                                            <td><?php echo sanitize($user['email']); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                        if ($user['role'] === 'Super Admin') echo 'bg-danger';
                                                        elseif ($user['role'] === 'Admin') echo 'bg-warning text-dark';
                                                        else echo 'bg-info';
                                                    ?>
                                                ">
                                                    <?php echo sanitize($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php 
                                                        if (!empty($user['last_login'])) {
                                                            echo date('M d, Y H:i', strtotime($user['last_login']));
                                                        } else {
                                                            echo '<span class="text-muted">Never</span>';
                                                        }
                                                    ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No users found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-dismiss alerts after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.remove();
    });
}, 5000);

// Password strength indicator
document.querySelector('input[name="password"]')?.addEventListener('input', function(e) {
    const strength = {
        0: 'Very Weak',
        1: 'Weak',
        2: 'Fair',
        3: 'Good',
        4: 'Strong'
    };
    
    let score = 0;
    const pwd = this.value;
    
    if (pwd.length >= 8) score++;
    if (pwd.match(/[a-z]/) && pwd.match(/[A-Z]/)) score++;
    if (pwd.match(/\d/)) score++;
    if (pwd.match(/[^a-zA-Z\d]/)) score++;
    
    this.classList.remove('is-invalid', 'is-valid');
    if (pwd.length > 0) {
        this.classList.add(score >= 3 ? 'is-valid' : 'is-invalid');
    }
});
</script>
</body>
</html>