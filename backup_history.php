<?php
/**
 * Backup History - BRDSS
 * Database backup management with history tracking
 */
require_once 'config.php';

/** @var \PDO $pdo */

checkLogin();

$pageTitle = 'Backup History - BRDSS';
$message = '';
$messageType = 'success';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// Security: Check if user has admin role (adjust as needed)
$userRole = $_SESSION['role'] ?? '';
if ($userRole !== 'admin' && $userRole !== 'super_admin') {
    die('Access denied. Admin privileges required.');
}

// ---------- Handle backup creation ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    try {
        // Generate backup filename
        $backupDir = __DIR__ . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backupPath = $backupDir . '/' . $backupFile;
        
        // Get database credentials from environment or config
        $dbHost = getenv('DB_HOST') ?? 'localhost';
        $dbUser = getenv('DB_USER') ?? 'root';
        $dbPass = getenv('DB_PASS') ?? '';
        $dbName = getenv('DB_NAME') ?? 'brdss_db';
        
        // Use mysqldump to create backup
        $command = sprintf(
            "mysqldump --user=%s --password=%s --host=%s %s > %s 2>/dev/null",
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbHost),
            escapeshellarg($dbName),
            escapeshellarg($backupPath)
        );
        
        $output = null;
        $returnVar = null;
        exec($command, $output, $returnVar);
        
        if ($returnVar === 0 && file_exists($backupPath)) {
            // Record backup in database
            $stmt = $pdo->prepare("
                INSERT INTO backup_history (user_id, file_location, file_size, remarks, backup_date)
                VALUES (:user_id, :file_location, :file_size, :remarks, NOW())
            ");
            $fileSize = filesize($backupPath);
            $stmt->execute([
                ':user_id' => $currentUserId,
                ':file_location' => $backupFile,
                ':file_size' => $fileSize,
                ':remarks' => sanitize($_POST['remarks'] ?? 'Manual backup')
            ]);
            
            $backupId = (int)$pdo->lastInsertId();
            logActivity($currentUserId, 'Created database backup', 'CREATE', 'backup_history', $backupId);
            
            $message = 'Backup created successfully (' . number_format($fileSize / 1024 / 1024, 2) . ' MB).';
            $messageType = 'success';
        } else {
            throw new Exception('Backup creation failed. Check mysqldump availability.');
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
        error_log('Backup creation error: ' . $e->getMessage());
    }
}

// ---------- Handle backup download ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_backup'])) {
    try {
        $backupId = (int)($_POST['backup_id'] ?? 0);
        
        // Fetch backup record
        $stmt = $pdo->prepare("SELECT file_location FROM backup_history WHERE backup_id = ? LIMIT 1");
        $stmt->execute([$backupId]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$backup) {
            throw new Exception('Backup not found.');
        }
        
        $backupPath = __DIR__ . '/backups/' . basename($backup['file_location']);
        
        // Validate file exists and is within backups directory
        if (!file_exists($backupPath) || realpath($backupPath) === false) {
            throw new Exception('Backup file not found on server.');
        }
        
        // Serve file for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($backupPath) . '"');
        header('Content-Length: ' . filesize($backupPath));
        readfile($backupPath);
        exit;
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
        error_log('Backup download error: ' . $e->getMessage());
    }
}

// ---------- Handle backup deletion ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    try {
        $backupId = (int)($_POST['backup_id'] ?? 0);
        
        // Fetch backup record
        $stmt = $pdo->prepare("SELECT file_location FROM backup_history WHERE backup_id = ? LIMIT 1");
        $stmt->execute([$backupId]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$backup) {
            throw new Exception('Backup not found.');
        }
        
        $backupPath = __DIR__ . '/backups/' . basename($backup['file_location']);
        
        // Delete file if exists
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
        
        // Delete record from database
        $stmt = $pdo->prepare("DELETE FROM backup_history WHERE backup_id = ?");
        $stmt->execute([$backupId]);
        
        logActivity($currentUserId, 'Deleted backup', 'DELETE', 'backup_history', $backupId);
        
        $message = 'Backup deleted successfully.';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
        error_log('Backup deletion error: ' . $e->getMessage());
    }
}

// ---------- Fetch backups ----------
$backups = [];
try {
    $stmt = $pdo->query("
        SELECT 
            bh.backup_id,
            bh.user_id,
            bh.file_location,
            bh.file_size,
            bh.remarks,
            bh.backup_date,
            u.username
        FROM backup_history bh
        LEFT JOIN users u ON bh.user_id = u.user_id
        ORDER BY bh.backup_date DESC
    ");
    $backups = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log('Backup fetch error: ' . $e->getMessage());
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo sanitize($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Backup History</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createBackupModal">
            + Create Backup
        </button>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo sanitize($messageType); ?> alert-dismissible fade show" role="alert">
            <?php echo sanitize($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Backup Date</th>
                            <th>Filename</th>
                            <th>Size</th>
                            <th>By</th>
                            <th>Remarks</th>
                            <th style="width:200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($backups)): ?>
                            <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td><?php echo !empty($backup['backup_date']) ? date('M d, Y h:i A', strtotime($backup['backup_date'])) : '-'; ?></td>
                                    <td><?php echo sanitize($backup['file_location']); ?></td>
                                    <td><?php echo number_format($backup['file_size'] / 1024 / 1024, 2); ?> MB</td>
                                    <td><?php echo sanitize($backup['username'] ?? 'System'); ?></td>
                                    <td><?php echo sanitize($backup['remarks']); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="backup_id" value="<?php echo (int)$backup['backup_id']; ?>">
                                            <button type="submit" name="download_backup" class="btn btn-sm btn-info" title="Download backup">
                                                Download
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this backup permanently?');">
                                            <input type="hidden" name="backup_id" value="<?php echo (int)$backup['backup_id']; ?>">
                                            <button type="submit" name="delete_backup" class="btn btn-sm btn-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No backups found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 text-muted small">
        <strong>Note:</strong> Backups are stored in the <code>/backups</code> directory. Ensure this directory has proper permissions (755) and is backed up separately.
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Create Backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Note:</strong> This will create a complete database backup. This may take a few moments.
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Remarks (Optional)</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="e.g., Pre-maintenance backup"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_backup" class="btn btn-success">Create Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-dismiss alerts after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.remove();
    });
}, 5000);
</script>
</body>
</html>