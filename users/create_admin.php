<?php
require_once "../config/auth.php";
require_role(["super_admin"]);
require_once "../config/db.php";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$username_session = $_SESSION["username"] ?? "Super Admin";
$dash = "../dashboard/super.php";

$error = "";
$first_name = "";
$last_name = "";
$username = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = trim($_POST["first_name"] ?? "");
    $last_name  = trim($_POST["last_name"] ?? "");
    $username   = trim($_POST["username"] ?? "");
    $password   = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($first_name === "" || $last_name === "" || $username === "" || $password === "" || $confirm_password === "") {
        $error = "All fields are required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $error = "Username must be at least 3 characters and alphanumeric or underscore only.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (
        !preg_match('/[A-Za-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W_]/', $password)
    ) {
        $error = "Password must contain letters, numbers, and symbols.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $error = "Username already exists.";
        } else {
            $full_name = $first_name . " " . $last_name;
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role = "admin_staff";
            $is_active = 1;

            $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $username, $password_hash, $full_name, $role, $is_active);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php?success=" . urlencode("Admin account created successfully."));
                exit;
            } else {
                $stmt->close();
                $error = "Failed to create admin account.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | Create Admin</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">

  <style>
    .layout { display:flex; height:100vh; }
    .side{
      width: 280px;
      background: linear-gradient(180deg, #1B2B3A 0%, #243B53 100%);
      color:#fff;
      position: sticky;
      top:0;
      height:100vh;
      overflow-y:auto;
    }
    .side a{ color: rgba(255,255,255,.85); text-decoration:none; }
    .side a:hover{ color:#fff; }
    .side .nav-link{ border-radius:10px; padding:10px 12px; }
    .side .nav-link.active, .side .nav-link:hover{ background: rgba(255,255,255,.10); }

    .content{ flex:1; height:100vh; overflow-y:auto; background:#f6f7fb; }
    .topbar{ background:#fff; border-bottom:1px solid rgba(0,0,0,.06); position:sticky; top:0; z-index:5; }

    .card-box{ border-radius:10px; border:1px solid rgba(0,0,0,.06); }
    .btn-soft{ border-radius:10px; border:1px solid rgba(0,0,0,.10); background:#fff; }
    .muted{ color:#6b7280; }
    .section-title { font-size: 1.1rem; font-weight: 700; }
  </style>
</head>

<body>
<div class="layout">
  <!-- SIDEBAR -->
  <aside class="side p-3">
    <div class="d-flex align-items-center gap-2 mb-3">
      <div class="brdss-logo"></div>
      <div>
        <div class="fw-bold">BRDSS</div>
        <div class="small opacity-75"><?= h($username_session) ?></div>
      </div>
    </div>

    <div class="small opacity-75 mb-2">MAIN MENU</div>
    <nav class="nav flex-column gap-1">
      <a class="nav-link" href="<?= h($dash) ?>"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a>
      <a class="nav-link" href="../residents/index.php"><i class="bi bi-people-fill me-2"></i>Residents</a>
      <a class="nav-link" href="../beneficiaries/index.php"><i class="bi bi-box-seam-fill me-2"></i>Aid Distribution</a>
      <a class="nav-link" href="../backups/index.php"><i class="bi bi-database-fill-gear me-2"></i>Backups</a>
      <a class="nav-link active" href="../users/index.php"><i class="bi bi-shield-lock-fill me-2"></i>Users</a>
    </nav>

    <hr class="border-light opacity-25 my-3">

    <a class="btn btn-outline-light w-100" href="../auth/logout.php">
      <i class="bi bi-box-arrow-right me-2"></i>Logout
    </a>
  </aside>

  <!-- CONTENT -->
  <main class="content">
    <div class="topbar px-4 py-3 d-flex align-items-center justify-content-between">
      <div>
        <div class="fw-bold fs-4">Create Admin Account</div>
        <div class="small muted">Register a new authorized administrator account. Super Admin only.</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-soft" href="index.php">
          <i class="bi bi-arrow-left me-1"></i>Back to Users
        </a>
      </div>
    </div>

    <div class="container-fluid px-4 py-4">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-7">
          <div class="card card-box shadow-sm">
            <div class="card-body p-4">

              <div class="section-title mb-1">Account Information</div>
              <div class="small muted mb-4">Fill out the required details to create a new admin account.</div>

              <?php if ($error !== ""): ?>
                <div class="alert alert-danger py-2"><?= h($error) ?></div>
              <?php endif; ?>

              <form method="POST" action="">
                <div class="row g-3">
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">First Name *</label>
                    <input type="text" name="first_name" class="form-control" placeholder="Juan" value="<?= h($first_name) ?>" required>
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" placeholder="Dela Cruz" value="<?= h($last_name) ?>" required>
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-semibold">Username *</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter your username" value="<?= h($username) ?>" required>
                    <div class="form-text">Min 3 characters, alphanumeric or underscore only.</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-semibold">Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter strong password" required>
                    <div class="form-text">Min 8 characters, mix of letters, numbers, and symbols.</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-semibold">Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                  </div>

                  <div class="col-12 pt-2 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-person-check-fill me-1"></i>Create Account
                    </button>
                    <a href="index.php" class="btn btn-soft">
                      Cancel
                    </a>
                  </div>
                </div>
              </form>

            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>