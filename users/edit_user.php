<?php
require_once "../config/auth.php";
require_role(["super_admin"]);
require_once "../config/db.php";

function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$username = $_SESSION["username"] ?? "User";
$role     = $_SESSION["role"] ?? "super_admin";
$dash     = "../dashboard/super.php";

$userId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($userId <= 0) {
  header("Location: index.php?error=" . urlencode("Invalid user ID."));
  exit;
}

/* Load target user */
$stmt = $conn->prepare("
  SELECT user_id, username, role, is_active, created_at
  FROM users
  WHERE user_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
  header("Location: index.php?error=" . urlencode("User not found."));
  exit;
}

$isProtectedSuperAdmin = ($user["role"] === "super_admin" && $user["username"] === "superadmin");

/* Handle update */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $newUsername = trim($_POST["username"] ?? "");
  $newRole     = trim($_POST["role"] ?? "");
  $isActive    = isset($_POST["is_active"]) ? 1 : 0;
  $newPassword = $_POST["new_password"] ?? "";

  if ($newUsername === "" || $newRole === "") {
    header("Location: edit_user.php?id={$userId}&error=" . urlencode("Please fill in all required fields."));
    exit;
  }

  $allowedRoles = ["super_admin", "admin_staff"];
  if (!in_array($newRole, $allowedRoles, true)) {
    header("Location: edit_user.php?id={$userId}&error=" . urlencode("Invalid role selected."));
    exit;
  }

  if ($isProtectedSuperAdmin) {
    $newRole = "super_admin";
    $isActive = 1;
  }

  /* Optional password update */
  if ($newPassword !== "") {
    if (strlen($newPassword) < 8) {
      header("Location: edit_user.php?id={$userId}&error=" . urlencode("New password must be at least 8 characters."));
      exit;
    }

    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $upd = $conn->prepare("
      UPDATE users
      SET username = ?, role = ?, is_active = ?, password_hash = ?
      WHERE user_id = ?
    ");
    $upd->bind_param("ssisi", $newUsername, $newRole, $isActive, $passwordHash, $userId);
  } else {
    $upd = $conn->prepare("
      UPDATE users
      SET username = ?, role = ?, is_active = ?
      WHERE user_id = ?
    ");
    $upd->bind_param("ssii", $newUsername, $newRole, $isActive, $userId);
  }

  if ($upd->execute()) {
    $upd->close();
    header("Location: index.php?success=" . urlencode("User updated successfully."));
    exit;
  }

  $upd->close();
  header("Location: edit_user.php?id={$userId}&error=" . urlencode("Failed to update user."));
  exit;
}

$flashError = trim($_GET["error"] ?? "");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | Edit User</title>

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

    <!-- FLOATING EDIT UI -->
    <div class="overlay-wrap">
      <div class="floating-card">
        <div class="floating-header">
          <h5 class="floating-title">Edit User</h5>
          <a href="index.php" class="floating-close" aria-label="Close">&times;</a>
        </div>

        <form method="post" action="">
          <div class="floating-body">
            <?php if ($flashError !== ""): ?>
              <div class="alert alert-danger py-2 mb-3"><?= h($flashError) ?></div>
            <?php endif; ?>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label small fw-semibold">Username</label>
                <input
                  type="text"
                  name="username"
                  class="form-control"
                  required
                  value="<?= h($user["username"]) ?>"
                >
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold">Role</label>
                <select
                  name="role"
                  class="form-select"
                  <?= $isProtectedSuperAdmin ? "disabled" : "" ?>
                  required
                >
                  <option value="admin_staff" <?= $user["role"] === "admin_staff" ? "selected" : "" ?>>Admin Staff</option>
                  <option value="super_admin" <?= $user["role"] === "super_admin" ? "selected" : "" ?>>Super Admin</option>
                </select>
                <?php if ($isProtectedSuperAdmin): ?>
                  <input type="hidden" name="role" value="super_admin">
                <?php endif; ?>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold">Active</label>
                <select
                  name="is_active_select"
                  class="form-select"
                  onchange="document.getElementById('is_active_hidden').value = this.value"
                  <?= $isProtectedSuperAdmin ? "disabled" : "" ?>
                >
                  <option value="1" <?= (int)$user["is_active"] === 1 ? "selected" : "" ?>>Yes</option>
                  <option value="0" <?= (int)$user["is_active"] !== 1 ? "selected" : "" ?>>No</option>
                </select>
                <input type="hidden" id="is_active_hidden" name="is_active" value="<?= (int)$user["is_active"] === 1 ? "1" : "0" ?>">
                <?php if ($isProtectedSuperAdmin): ?>
                  <input type="hidden" name="is_active" value="1">
                <?php endif; ?>
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">New Password (optional)</label>
                <input
                  type="password"
                  name="new_password"
                  class="form-control"
                  placeholder="Leave blank if you do not want to change the password"
                >
              </div>

              <div class="col-12">
                <label class="form-label small fw-semibold">Created At</label>
                <input
                  type="text"
                  class="form-control"
                  value="<?= h($user["created_at"] ?? "") ?>"
                  disabled
                >
              </div>
            </div>
          </div>

          <div class="px-4 pb-4 d-flex justify-content-end gap-2">
            <a href="index.php" class="btn btn-soft">Cancel</a>
            <button type="submit" class="btn brdss-btn">
              <i class="bi bi-check2-circle me-1"></i>Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<script>
document.querySelector('select[name="is_active_select"]')?.addEventListener('change', function () {
  const hidden = document.getElementById('is_active_hidden');
  if (hidden) hidden.value = this.value === '1' ? '1' : '0';
});
</script>
</body>
</html>