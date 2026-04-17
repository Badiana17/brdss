<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/db.php";
require_once "../includes/role_guard.php";
requireRole(["super_admin"]);

$success = $_SESSION['backup_success'] ?? '';
$error   = $_SESSION['backup_error'] ?? '';
unset($_SESSION['backup_success'], $_SESSION['backup_error']);

$result = $conn->query("SELECT * FROM backup_history ORDER BY backup_date DESC, backup_id DESC");

function formatFileSize($bytes) {
    $bytes = (int)$bytes;

    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return $bytes . ' byte';
    } else {
        return '0 bytes';
    }
}

$username = $_SESSION["username"] ?? "Super Admin";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BRDSS | Backup History</title>

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
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .side a{ color: rgba(255,255,255,.85); text-decoration:none; }
        .side a:hover{ color:#fff; }

        .side .nav-link{
            border-radius: 8px;
            padding: 10px 12px;
        }

        .side .nav-link.active,
        .side .nav-link:hover{
            background: rgba(255,255,255,.10);
        }

        .content{
            flex:1;
            background:#f6f7fb;
            height:100vh;
            overflow-y:auto;
        }

        .topbar{
            background:#fff;
            border-bottom: 1px solid rgba(0,0,0,.06);
        }

        .badge-soft{
            background: rgba(47,93,138,.12);
            color: #2F5D8A;
            border: 1px solid rgba(47,93,138,.18);
        }

        .stat-card {
            border-radius: 8px;
        }

        .backup-actions-card {
            border-radius: 8px;
        }

        .backup-btn{
            border-radius: 4px;
            padding: 6px 12px;
            font-size: .80rem;
            text-decoration: none;
            display: inline-block;
        }

        .backup-btn-download{
            background: #2F5D8A;
            color: #fff;
            border: 1px solid #2F5D8A;
        }

        .backup-btn-download:hover{
            background: #274e74;
            border-color: #274e74;
            color:#fff;
        }

        .backup-btn-delete{
            background: #8b1e2d;
            color: #fff;
            border: 1px solid #8b1e2d;
        }

        .backup-btn-delete:hover{
            background: #741927;
            border-color: #741927;
            color:#fff;
        }

        .location-pill{
            background: rgba(47,93,138,.12);
            color: #2F5D8A;
            border: 1px solid rgba(47,93,138,.18);
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 600;
            display: inline-block;
        }

        .table thead th{
            font-size: .92rem;
            white-space: nowrap;
        }

        .table tbody td{
            font-size: .92rem;
            vertical-align: middle;
        }

        .create-backup-btn{
            border-radius: 3px;
            padding: 10px 14px;
        }
    </style>
</head>

<body>
<div class="layout">
    <aside class="side p-3">
        <div class="d-flex align-items-center gap-2 mb-3">
            <div class="brdss-logo"></div>
            <div>
                <div class="fw-bold">BRDSS</div>
                <div class="small opacity-75"><?= htmlspecialchars($_SESSION["username"] ?? "User") ?></div>
            </div>
        </div>

        <div class="small opacity-75 mb-2">MAIN MENU</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link" href="../dashboard/super.php"><i class="bi bi-grid-1x2-fill me-2"></i>Dashboard</a>
            <a class="nav-link" href="../residents/index.php"><i class="bi bi-people-fill me-2"></i>Residents</a>
            <a class="nav-link" href="../aid/index.php"><i class="bi bi-box-seam-fill me-2"></i>Aid Distribution</a>
            <a class="nav-link active" href="../backups/index.php"><i class="bi bi-database-fill-gear me-2"></i>Backups</a>
            <a class="nav-link" href="../users/index.php"><i class="bi bi-shield-lock-fill me-2"></i>Users</a>
        </nav>

        <hr class="border-light opacity-25 my-3">

        <a class="btn btn-outline-light w-100" href="../auth/logout.php">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
        </a>

        <div class="small opacity-50 mt-3">
            Works offline via LAN (XAMPP + MySQL)
        </div>
    </aside>

    <main class="content">
        <div class="topbar px-4 py-3 d-flex align-items-center justify-content-between">
            <div>
                <div class="fw-bold">Backup History</div>
                <div class="small text-muted">Manage database archives and recovery files.</div>
            </div>
            <span class="badge badge-soft rounded-pill px-3 py-2">
                Role: <?= htmlspecialchars($_SESSION["role"] ?? "super_admin") ?>
            </span>
        </div>

        <div class="container-fluid px-4 py-4">
            <div class="card stat-card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div class="fw-bold">Backup Records</div>
                        <a class="btn brdss-btn create-backup-btn" href="create_backup.php">
                            <i class="bi bi-plus-lg me-1"></i>Create Backup
                        </a>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>File Name</th>
                                    <th>Size</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= (int)$row['backup_id'] ?></td>
                                            <td><?= htmlspecialchars($row['file_name']) ?></td>
                                            <td><?= formatFileSize($row['file_size']) ?></td>
                                            <td>
                                                <span class="location-pill">
                                                    <?= htmlspecialchars($row['file_location']) ?>
                                                </span>
                                            </td>
                                            <td><?= date("M d, Y H:i", strtotime($row['backup_date'])) ?></td>
                                            <td>
                                                <div class="d-flex flex-column gap-1 align-items-start">
                                                    <a class="backup-btn backup-btn-download" href="download_backup.php?id=<?= (int)$row['backup_id'] ?>">Download</a>
                                                    <a class="backup-btn backup-btn-delete" href="delete_backup.php?id=<?= (int)$row['backup_id'] ?>" onclick="return confirm('Delete this backup?')">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No backup records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>