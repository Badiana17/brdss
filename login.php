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

// If already logged in, redirect to dashboard
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $password = $_POST['password'] ?? '';
    
    // Check for logout message
if (isset($_GET['logout'])) {
    $success = 'You have been logged out successfully.';
}

    if ($username !== '' && $password !== '') {
        try {
            // Fetch user from database
            $stmt = $pdo->prepare("
                SELECT user_id, username, password, role, is_active, login_attempts, locked_until 
                FROM users 
                WHERE username = ? 
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Check if account is locked
                if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
                    $error = 'Account locked. Try again later.';
                } elseif ($user['is_active'] != 1) {
                    $error = 'Account deactivated. Contact administrator.';
                } elseif (password_verify($password, $user['password'])) {
                    // Successful login
                    $_SESSION['user_id'] = (int)$user['user_id'];
                    $_SESSION['username'] = (string)$user['username'];
                    $_SESSION['role'] = (string)$user['role'];
                    
                    // Update last login and reset attempts
                    $updateStmt = $pdo->prepare("
                        UPDATE users 
                        SET last_login = NOW(), login_attempts = 0, locked_until = NULL 
                        WHERE user_id = ?
                    ");
                    $updateStmt->execute([(int)$user['user_id']]);
                    
                    // Log activity
                    try {
                        logActivity((int)$user['user_id'], 'User logged in', 'LOGIN');
                    } catch (Throwable $e) {
                        error_log('Login logActivity error: ' . $e->getMessage());
                    }
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    // Failed password - increment attempts
                    $attempts = (int)$user['login_attempts'] + 1;
                    
                    if ($attempts >= 5) {
                        // Lock account for 15 minutes
                        $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $updateStmt = $pdo->prepare("
                            UPDATE users 
                            SET login_attempts = ?, locked_until = ? 
                            WHERE user_id = ?
                        ");
                        $updateStmt->execute([$attempts, $lockUntil, (int)$user['user_id']]);
                        $error = 'Account locked for 15 minutes after 5 failed attempts.';
                    } else {
                        // Show attempt counter
                        $remaining = 5 - $attempts;
                        $updateStmt = $pdo->prepare("
                            UPDATE users 
                            SET login_attempts = ? 
                            WHERE user_id = ?
                        ");
                        $updateStmt->execute([$attempts, (int)$user['user_id']]);
                        $error = 'Invalid credentials. ' . $remaining . ' attempt(s) remaining.';
                    }
                }
            } else {
                // Generic error to prevent user enumeration
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
    <title>Login - BRDSS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        .login-header p {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin: 0;
        }
        .alert-danger {
            border-left: 4px solid #dc3545;
        }
        .form-control {
            border: 1px solid #ddd;
            padding: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3d8f 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .register-section {
            border-top: 1px solid #e0e0e0;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            text-align: center;
        }
        .register-section p {
            color: #666;
            font-size: 0.95rem;
            margin: 0;
        }
        .register-section a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .register-section a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🏘️ BRDSS</h1>
            <p>Barangay Resident Data & Social Services</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" novalidate>
            <div class="form-group mb-3">
                <label for="username" class="form-label">Username</label>
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
            
            <div class="form-group mb-4">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    required 
                    autocomplete="current-password"
                    placeholder="Enter your password"
                >
            </div>
            
            <button type="submit" class="btn btn-primary w-100 mb-3">
                Login
            </button>
        </form>

        <div class="register-section">
            <p>Don't have an account? <a href="register.php">Click here to register</a></p>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">
                © 2025 Barangay Resident Data & Social Services
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>