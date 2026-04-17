<?php
// auth/login.php
session_start();
if (isset($_SESSION["user_id"])) {
  header("Location: ../dashboard/index.php");
  exit;
}

$error = $_GET["error"] ?? "";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="brdss-bg">
  <div class="min-vh-100 d-flex align-items-center justify-content-center p-3">
    <div class="card brdss-card shadow-lg border-0">
      <div class="card-header brdss-header text-white text-center border-0 py-4">
        <div class="brdss-logo mx-auto mb-2"></div>
        <h1 class="h4 mb-0 fw-bold">BRDSS</h1>
        <div class="small opacity-75">Barangay Resident Database Services System</div>
      </div>

      <div class="card-body p-4 p-md-5">
        <?php if ($error): ?>
          <div class="alert alert-danger py-2 small mb-3">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="process_login.php" novalidate>
          <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <input name="username" class="form-control form-control-lg" placeholder="Enter your username" required>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" name="password" class="form-control form-control-lg" placeholder="Enter your password" required>
          </div>

          <button class="btn btn-lg w-100 brdss-btn fw-semibold" type="submit">
            Login
          </button>

          <div class="text-center mt-4 small text-muted">
            © <?= date("Y") ?> Barangay Resident Database Services System
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>