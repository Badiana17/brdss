<?php
require_once 'config.php';

$error = '';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("SELECT user_id, username, password, role, is_active, login_attempts, locked_until FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $error = 'Account locked. Try again later.';
            } elseif ($user['is_active'] != 1) {
                $error = 'Account deactivated.';
            } else {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0, locked_until = NULL WHERE user_id = ?");
                    $updateStmt->bind_param("i", $user['user_id']);
                    $updateStmt->execute();
                    
                    logActivity($user['user_id'], 'User logged in', 'LOGIN');
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $attempts = $user['login_attempts'] + 1;
                    $lockUntil = null;
                    
                    if ($attempts >= 5) {
                        $lockUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $error = 'Account locked for 15 minutes.';
                    } else {
                        $error = 'Invalid credentials. Attempt ' . $attempts . ' of 5.';
                    }
                    
                    $updateStmt = $conn->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE user_id = ?");
                    $updateStmt->bind_param("isi", $attempts, $lockUntil, $user['user_id']);
                    $updateStmt->execute();
                }
            }
        } else {
            $error = 'Invalid username or password.';
        }
        
        $stmt->close();
        $conn->close();
    } else {
        $error = 'Please enter username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BRDSS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        h1 { color: #2c3e50; text-align: center; margin-bottom: 0.5rem; }
        .subtitle { color: #7f8c8d; text-align: center; margin-bottom: 2rem; font-size: 0.9rem; }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🏘️ BRDSS</h1>
        <p class="subtitle">Barangay Resident Data & Social Services</p>
        
        <?php if ($error): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-primary">Login</button>
        </form>
    </div>
</body>
</html>
