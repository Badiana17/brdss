<?php
require_once "../config/auth.php";
require_role(["super_admin"]);
require_once "../config/db.php";

function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$username = $_SESSION["username"] ?? "User";
$dash     = "../dashboard/super.php";
$flashError = "";

// Check user count
$q = $conn->query("SELECT COUNT(*) AS c FROM users");
$userCount = $q->fetch_assoc()["c"];
if ($userCount >= 2) {
    header("Location: index.php?error=" . urlencode("Maximum user limit reached. Delete the existing Admin first to add a new one."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newUsername = trim($_POST["username"] ?? "");
    $fullName    = trim($_POST["full_name"] ?? "");
    $newPassword = $_POST["new_password"] ?? "";
    $newRole     = "admin_staff"; // Enforced
    $isActive    = isset($_POST["is_active"]) ? 1 : 0;

    if ($newUsername === "" || $newPassword === "") {
        $flashError = "Please fill in all required fields.";
    } elseif (strlen($newPassword) < 8) {
        $flashError = "Password must be at least 8 characters.";
    } else {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $ins = $conn->prepare("INSERT INTO users (username, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?)");
        $ins->bind_param("ssssi", $newUsername, $passwordHash, $fullName, $newRole, $isActive);
        
        if ($ins->execute()) {
            header("Location: index.php?success=" . urlencode("Admin user added successfully."));
            exit;
        } else {
            if ($conn->errno === 1062) {
                $flashError = "Username already exists.";
            } else {
                $flashError = "Failed to create user.";
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
  <title>BRDSS | Add User</title>

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

    .content{ flex:1; height:100vh; overflow-y:auto; background:#f6f7fb; position:relative; }
    .topbar{ background:#fff; border-bottom: 1px solid rgba(0,0,0,.06); position: sticky; top: 0; z-index: 5; }

    .btn-soft{
      border-radius:10px;
      border:1px solid rgba(0,0,0,.10);
      background:#fff;
    }

    .overlay-wrap{
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.28);
      display:flex;
      align-items:center;
      justify-content:center;
      z-index: 1050;
      padding: 20px;
    }

    .floating-card{
      width: 100%;
      max-width: 760px;
      background:#fff;
      border-radius: 16px;
      box-shadow: 0 18px 45px rgba(0,0,0,.18);
      overflow:hidden;
      border: 1px solid rgba(0,0,0,.08);
    }

    .floating-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding: 18px 22px;
      border-bottom: 1px solid rgba(0,0,0,.08);
    }

    .floating-title{
      font-size: 1.25rem;
      font-weight: 800;
      color:#1f2937;
      margin:0;
    }

    .floating-body{
      padding: 22px;
    }

    .floating-close{
      border:0;
      background:transparent;
      font-size: 2rem;
      line-height:1;
      color:#8a8f98;
      text-decoration:none;
    }

    .floating-close:hover{
      color:#374151;
    }

    .muted{
      color:#6b7280;
    }
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
        <div class="small opacity-75"><?= h($username) ?></div>
      </div>
    </div>

    <div class="small opacity-75 mb-2">MAIN MENU</div>
    <nav class="nav flex-column gap-1">
      <a class="nav-link" href="<?= h($dash) ?>"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a>
      <a class="nav-link" href="../residents/index.php"><i class="bi bi-people-fill me-2"></i>Residents</a>
      <a class="nav-link" href="../aid/index.php"><i class="bi bi-box-seam-fill me-2"></i>Aid Distribution</a>
      <a class="nav-link" href="../backups/index.php"><i class="bi bi-database-fill-gear me-2"></i>Backups</a>
      <a class="nav-link active" href="index.php"><i class="bi bi-shield-lock-fill me-2"></i>Users</a>
    </nav>

    <hr class="border-light opacity-25 my-3">

    <a class="btn btn-outline-light w-100" href="../auth/logout.php">
      <i class="bi bi-box-arrow-right me-2"></i>Logout
    </a>

    <div class="small opacity-50 mt-3">
      Works offline via LAN (XAMPP + MySQL)
    </div>
  </aside>

  <!-- CONTENT -->
  <main class="content">
    <div class="topbar px-4 py-3 d-flex align-items-center justify-content-between">
      <div>
        <div class="fw-bold fs-4">User Management</div>
        <div class="small muted">Manage administrator accounts and system access. Super Admin only.</div>
      </div>
      <div>
        <a class="btn btn-soft" href="index.php">
          <i class="bi bi-arrow-left me-1"></i>Back to Users
        </a>
      </div>
    </div>

    <!-- FLOATING CREATE UI -->
    <div class="overlay-wrap">
      <div class="floating-card">
        <div class="floating-header">
          <h5 class="floating-title">Add User (Admin)</h5>
          <a href="index.php" class="floating-close" aria-label="Close">&times;</a>
        </div>

        <form method="post" action="">
          <div class="floating-body">
            <?php if ($flashError !== ""): ?>
              <div class="alert alert-danger py-2 mb-3"><?= h($flashError) ?></div>
            <?php endif; ?>

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold">Username</label>
                <input type="text" name="username" class="form-control" required>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold">Full Name</label>
                <input type="text" name="full_name" class="form-control">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold">Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="8">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold">Active</label>
                <select name="is_active_select" class="form-select" onchange="document.getElementById('is_active_hidden').value = this.value">
                  <option value="1" selected>Yes</option>
                  <option value="0">No</option>
                </select>
                <input type="hidden" id="is_active_hidden" name="is_active" value="1">
              </div>

            </div>
          </div>

          <div class="px-4 pb-4 d-flex justify-content-end gap-2">
            <a href="index.php" class="btn btn-soft">Cancel</a>
            <button type="submit" class="btn brdss-btn">
              <i class="bi bi-person-plus-fill me-1"></i>Create Admin
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>
</body>
</html>
