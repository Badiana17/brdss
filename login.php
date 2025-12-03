<?php
/**
 * Login - BRDSS
 * User authentication with account lockout protection
 */
require_once 'config.php';

/** @var \PDO $pdo */

session_start();

$pageTitle = 'Login - BRDSS';
$error = '';
$success = '';

// If already logged in, redirect to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $password = $_POST['password'] ?? '';
    
    if ($username !== '' && $password !== '') {
        try {
            $stmt = $pdo->prepare("
                SELECT user_id, username, password, role, is_active, login_attempts, locked_until 
                FROM users 
                WHERE username = ? 
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                    $error = 'Account locked. Try again later.';
                } elseif ($user['is_active'] != 1) {
                    $error = 'Account deactivated. Contact administrator.';
                } elseif (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = (int)$user['user_id'];
                    $_SESSION['username'] = (string)$user['username'];
                    $_SESSION['role'] = (string)$user['role'];
                    
                    $updateStmt = $pdo->prepare("
                        UPDATE users 
                        SET last_login = NOW(), login_attempts = 0, locked_until = NULL 
                        WHERE user_id = ?
                    ");
                    $updateStmt->execute([(int)$user['user_id']]);
                    
                    try {
                        logActivity((int)$user['user_id'], 'User logged in', 'LOGIN');
                    } catch (Throwable $e) {
                        error_log('Login logActivity error: ' . $e->getMessage());
                    }
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $attempts = (int)$user['login_attempts'] + 1;
                    
                    if ($attempts >= 5) {
                        $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $updateStmt = $pdo->prepare("
                            UPDATE users 
                            SET login_attempts = ?, locked_until = ? 
                            WHERE user_id = ?
                        ");
                        $updateStmt->execute([$attempts, $lockUntil, (int)$user['user_id']]);
                        $error = 'Account locked for 15 minutes after 5 failed attempts.';
                    } else {
                        $remaining = 5 - $attempts;
                        $updateStmt = $pdo->prepare("
                            UPDATE users 
                            SET login_attempts = ? 
                            WHERE user_id = ?
                        ");
                        $updateStmt->execute([$attempts, (int)$user['user_id']]);
                        $error = "Invalid credentials. $remaining attempt(s) remaining.";
                    }
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Server error. Please try again later.';
            error_log('Login error: ' . $e->getMessage());
        }
    } else {
        $error = 'Please enter username and password.';
    }
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
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-wrapper {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-container {
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

        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .login-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .login-header p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin: 0;
        }

        .login-body {
            padding: 2.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.7rem;
            display: block;
        }

        .form-control {
            border: 2px solid #e0e0e0;
            padding: 0.85rem;
            font-size: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            padding: 0.9rem;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 1rem;
            font-size: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(44, 62, 80, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-login:active {
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

        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0e0e0;
        }

        .divider span {
            margin: 0 1rem;
            color: #999;
            font-size: 0.9rem;
        }

        .register-section {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
        }

        .register-section p {
            color: #666;
            font-size: 0.95rem;
            margin: 0;
        }

        .register-section a {
            color: var(--accent-color);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .register-section a:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }

        .login-footer {
            text-align: center;
            padding: 1.5rem 2rem;
            background: #f8f9fa;
            color: #999;
            font-size: 0.85rem;
            border-top: 1px solid #e0e0e0;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--accent-color);
            cursor: pointer;
            font-size: 1rem;
            padding: 0.5rem;
        }

        .password-toggle-btn:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <h1>🏘️ BRDSS</h1>
                <p>Barangay Resident Database & Services System</p>
            </div>

            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>✓ Success:</strong> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i> Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control" 
                            required 
                            autofocus 
                            autocomplete="username"
                            value="<?php echo isset($username) ? htmlspecialchars($username, ENT_QUOTES, 'UTF-8') : ''; ?>"
                            placeholder="Enter your username"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <div class="password-toggle">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                required 
                                autocomplete="current-password"
                                placeholder="Enter your password"
                            >
                            <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>

                <div class="register-section">
                    <p>Don't have an account? <a href="register.php">Create one here</a></p>
                </div>
            </div>

            <div class="login-footer">
                © 2025 Barangay Resident Database & Services System
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle-btn i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }

        // Auto-dismiss alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.remove();
            }, 5000);
        });
    </script>
</body>
</html>