<?php
require_once "../config/auth.php";
require_role(["super_admin"]);
require_once "../includes/role_guard.php";
require_once "../config/db.php";

function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$username_session = $_SESSION["username"] ?? "Super Admin";
$dash = "../dashboard/super.php";

$flashSuccess = trim($_GET["success"] ?? "");
$flashError   = trim($_GET["error"] ?? "");

/* Delete user */
if (isset($_GET["delete"])) {
    $delete_id = (int) $_GET["delete"];

    $stmt = $conn->prepare("SELECT user_id, role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($target) {
        if ($target["role"] === "super_admin") {
            header("Location: index.php?error=" . urlencode("Super Admin account cannot be deleted."));
            exit;
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $delete_id);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php?success=" . urlencode("User account deleted successfully."));
                exit;
            } else {
                $stmt->close();
                header("Location: index.php?error=" . urlencode("Failed to delete user account."));
                exit;
            }
        }
    } else {
        header("Location: index.php?error=" . urlencode("User account not found."));
        exit;
    }
}

/* Load users */
$users = [];
$sql = "SELECT user_id, username, full_name, role, is_active, created_at
        FROM users
        ORDER BY created_at DESC";
$res = $conn->query($sql);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BRDSS | User Management</title>

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

    .table thead th{
      background:#f3f4f6;
      font-weight:700;
      font-size:.85rem;
      white-space:nowrap;
    }
    .table td{
      vertical-align:middle;
      white-space:nowrap;
    }
    .table-hscroll{ overflow-x:auto; }

    .badge-role-super{
      background:#e8f0fe;
      color:#1a73e8;
      border:1px solid rgba(26,115,232,.25);
    }
    .badge-role-admin{
      background:#f3e8ff;
      color:#7c3aed;
      border:1px solid rgba(124,58,237,.25);
    }
    .badge-active{
      background:#e6f4ea;
      color:#137333;
      border:1px solid rgba(19,115,51,.25);
    }
    .badge-inactive{
      background:#fdecec;
      color:#b42318;
      border:1px solid rgba(180,35,24,.25);
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
        <div class="small opacity-75"><?= h($username_session) ?></div>
      </div>
    </div>

    <div class="small opacity-75 mb-2">MAIN MENU</div>
    <nav class="nav flex-column gap-1">
      <a class="nav-link" href="<?= h($dash) ?>"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a>
      <a class="nav-link" href="../residents/index.php"><i class="bi bi-people-fill me-2"></i>Residents</a>
      <a class="nav-link" href="../aid/index.php"><i class="bi bi-box-seam-fill me-2"></i>Aid Distribution</a>
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
        <div class="fw-bold fs-4">User Management</div>
        <div class="small muted">Manage administrator accounts and system access. Super Admin only.</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-soft" href="create_admin.php">
          <i class="bi bi-person-plus-fill me-1"></i>Add User
        </a>
      </div>
    </div>

    <div class="container-fluid px-4 py-4">
      <div class="row justify-content-center">
        <div class="col-12">
          <div class="card card-box shadow-sm">
            <div class="card-body p-4">

              <div class="section-title mb-1">Authorized User Accounts</div>
              <div class="small muted mb-4">View, manage, and control administrator access within the system.</div>

              <?php if ($flashSuccess !== ""): ?>
                <div class="alert alert-success py-2"><?= h($flashSuccess) ?></div>
              <?php endif; ?>

              <?php if ($flashError !== ""): ?>
                <div class="alert alert-danger py-2"><?= h($flashError) ?></div>
              <?php endif; ?>

              <div class="table-hscroll">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Full Name</th>
                      <th>Username</th>
                      <th>Role</th>
                      <th>Status</th>
                      <th>Created At</th>
                      <th style="width: 180px;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (count($users) === 0): ?>
                      <tr>
                        <td colspan="7" class="text-center py-4 muted">No user accounts found.</td>
                      </tr>
                    <?php else: ?>
                      <?php $i = 1; ?>
                      <?php foreach ($users as $u): ?>
                        <tr>
                          <td><?= $i++ ?></td>
                          <td><?= h($u["full_name"] ?: "N/A") ?></td>
                          <td><?= h($u["username"]) ?></td>
                          <td>
                            <?php if ($u["role"] === "super_admin"): ?>
                              <span class="badge badge-role-super">Super Admin</span>
                            <?php else: ?>
                              <span class="badge badge-role-admin">Admin</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ((int)$u["is_active"] === 1): ?>
                              <span class="badge badge-active">Active</span>
                            <?php else: ?>
                              <span class="badge badge-inactive">Inactive</span>
                            <?php endif; ?>
                          </td>
                          <td><?= !empty($u["created_at"]) ? date("F d, Y h:i A", strtotime($u["created_at"])) : "—" ?></td>
                          <td>
                            <div class="d-flex gap-2 flex-wrap">
                              <a href="edit_user.php?id=<?= (int)$u["user_id"] ?>" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil-square me-1"></i>Edit
                              </a>

                              <?php if ($u["role"] === "super_admin"): ?>
                                <button type="button" class="btn btn-secondary btn-sm" disabled>
                                  <i class="bi bi-trash-fill me-1"></i>Delete
                                </button>
                              <?php else: ?>
                                <a href="index.php?delete=<?= (int)$u["user_id"] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this user account?');">
                                  <i class="bi bi-trash-fill me-1"></i>Delete
                                </a>
                              <?php endif; ?>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <div class="small muted mt-4">
                Note: The Super Admin account is protected and cannot be deleted.
              </div>

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