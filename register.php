<?php
/**
 * User Registration - BRDSS
 * Public self-registration and Admin user creation
 */
require_once 'config.php';

/** @var \PDO $pdo */

session_start();

$error = '';
$success = '';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// Check if user is logged in and is admin
$isAdmin = false;
if (!empty($_SESSION['user_id'])) {
    $userRole = $_SESSION['role'] ?? '';
    $isAdmin = ($userRole === 'Admin' || $userRole === 'Super Admin');
}

$pageTitle = $isAdmin ? 'User Registration - BRDSS' : 'Create Account - BRDSS';

// ---------- Handle user registration ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    try {
        $first_name = trim((string)filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $last_name = trim((string)filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $email = trim((string)filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $full_name = $first_name . ' ' . $last_name;
        $role = $_POST['role'] ?? 'Staff';

        // Validation
        if (!$first_name || strlen($first_name) < 2) {
            throw new Exception('First name must be at least 2 characters long.');
        }

        if (!$last_name || strlen($last_name) < 2) {
            throw new Exception('Last name must be at least 2 characters long.');
        }

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

        // Validate role
        $allowedRoles = $isAdmin ? ['Staff', 'Admin', 'Super Admin'] : ['Staff'];

        if (!in_array($role, $allowedRoles, true)) {
            throw new Exception('Invalid role selected.');
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
            $success = "✓ User '$username' registered successfully as $role.";
        } else {
            $success = "✓ Account created successfully! Redirecting to login in 3 seconds...";
            header("refresh:3;url=login.php");
        }
        
        // Clear form
        $first_name = '';
        $last_name = '';
        $username = '';
        $email = '';
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
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --light-bg: #ecf0f1;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding: 2rem 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .register-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }

        .register-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .register-header p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin: 0;
        }

        .register-body {
            padding: 2.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.7rem;
        }

        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            padding: 0.75rem;
            font-size: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            padding: 0.85rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 1rem;
            margin-top: 1rem;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(44, 62, 80, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .alert {
            border: none;
            border-left: 4px solid;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background-color: #ffe6e6;
            border-color: var(--danger-color);
            color: #c0392b;
        }

        .alert-success {
            background-color: #e6ffe6;
            border-color: var(--success-color);
            color: #1e8449;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h5 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--accent-color);
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem 0.75rem;
        }

        .table tbody tr {
            transition: background 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .register-link {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
        }

        .register-link a {
            color: var(--accent-color);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }

        .password-strength {
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .strength-bar-fill {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }

        .strength-very-weak { background-color: var(--danger-color); width: 20%; }
        .strength-weak { background-color: #ff9800; width: 40%; }
        .strength-fair { background-color: #ffc107; width: 60%; }
        .strength-good { background-color: #4caf50; width: 80%; }
        .strength-strong { background-color: var(--success-color); width: 100%; }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <div class="register-container">
            <div class="register-header">
                <h1>🏘️ BRDSS</h1>
                <p><?php echo $isAdmin ? 'User Registration Management' : 'Create Your Account'; ?></p>
            </div>

            <div class="register-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong><i class="fas fa-exclamation-circle"></i> Error:</strong> 
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong><i class="fas fa-check-circle"></i> Success:</strong> 
                        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Registration Form -->
                    <div class="<?php echo $isAdmin ? 'col-lg-5' : 'col-lg-8 mx-auto'; ?>">
                        <div class="form-section">
                            <h5><i class="fas fa-user-plus"></i> <?php echo $isAdmin ? 'Register New User' : 'Account Information'; ?></h5>
                            
                            <form method="POST" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
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
                                    <div class="col-md-6 mb-3">
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

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-at"></i> Username *</label>
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

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-envelope"></i> Email *</label>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        class="form-control" 
                                        required 
                                        value="<?php echo isset($email) ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : ''; ?>"
                                        placeholder="john@example.com"
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-shield-alt"></i> Role *</label>
                                    <select name="role" class="form-select" required>
                                        <option value="">-- Select Role --</option>
                                        <option value="Staff" <?php echo ($role === 'Staff') ? 'selected' : ''; ?>>
                                            Staff - Basic User
                                        </option>
                                        <?php if ($isAdmin): ?>
                                            <option value="Admin" <?php echo ($role === 'Admin') ? 'selected' : ''; ?>>
                                                Admin - System Administrator
                                            </option>
                                            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                                                <option value="Super Admin" <?php echo ($role === 'Super Admin') ? 'selected' : ''; ?>>
                                                    Super Admin - Full Control
                                                </option>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-lock"></i> Password *</label>
                                    <input 
                                        type="password" 
                                        name="password" 
                                        class="form-control" 
                                        id="password"
                                        required 
                                        minlength="8"
                                        placeholder="Enter strong password"
                                        autocomplete="new-password"
                                        onkeyup="checkPasswordStrength()"
                                    >
                                    <small class="text-muted">Min 8 characters, mix of letters, numbers, symbols</small>
                                    <div class="strength-bar">
                                        <div class="strength-bar-fill" id="strengthBar"></div>
                                    </div>
                                    <div class="password-strength">
                                        Strength: <span id="strengthText" class="fw-bold">-</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-lock"></i> Confirm Password *</label>
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

                                <button type="submit" name="register" class="btn btn-register w-100">
                                    <i class="fas fa-user-check"></i> 
                                    <?php echo $isAdmin ? 'Register User' : 'Create Account'; ?>
                                </button>
                            </form>

                            <?php if (!$isAdmin): ?>
                                <div class="register-link">
                                    <p>Already have an account? <a href="login.php">Login here</a></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Users List (Admin Only) -->
                    <?php if ($isAdmin): ?>
                    <div class="col-lg-7">
                        <div class="form-section">
                            <h5><i class="fas fa-users"></i> Registered Users (<?php echo count($users); ?>)</h5>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
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
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td>
                                                        <span class="badge 
                                                            <?php 
                                                                if ($user['role'] === 'Super Admin') echo 'bg-danger';
                                                                elseif ($user['role'] === 'Admin') echo 'bg-warning text-dark';
                                                                else echo 'bg-info';
                                                            ?>
                                                        ">
                                                            <?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                            <?php echo $user['is_active'] ? '✓ Active' : '✗ Inactive'; ?>
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
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    <i class="fas fa-inbox"></i> No users found yet.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        // Password strength checker
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            const strengthLevels = ['', 'Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            const strengthClasses = ['', 'strength-very-weak', 'strength-weak', 'strength-fair', 'strength-good', 'strength-strong'];
            
            strengthBar.className = 'strength-bar-fill ' + strengthClasses[strength];
            strengthText.textContent = strengthLevels[strength] || '-';
            strengthText.style.color = strengthClasses[strength] ? 'inherit' : '#999';
        }

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.alert').forEach(alert => {
                setTimeout(() => {
                    alert.remove();
                }, 5000);
            });
        });
    </script>
</body>
</html>