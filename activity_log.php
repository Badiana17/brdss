<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require 'config.php';

/** @var \PDO $pdo */

$pageTitle = 'Activity Log - BRDSS';

// Record that the current user viewed the activity log
if (function_exists('logActivity')) {
    try {
        logActivity((int)($_SESSION['user_id'] ?? 0), 'Viewed activity log', 'VIEW', 'activity_log', null);
    } catch (Throwable $e) {
        error_log('logActivity error: ' . $e->getMessage());
    }
}

try {
    // Use the `timestamp` column present in the schema
    $stmt = $pdo->query("SELECT l.*, u.username FROM activity_log l JOIN users u ON l.user_id = u.user_id ORDER BY l.timestamp DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('Activity log query error: ' . $e->getMessage());
    $logs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 2rem;
        }
        .content-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-wrapper">
            <h2>📝 Activity Log</h2>
            <p class="text-muted mb-4">Track all system activities and user actions</p>
            
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date &amp; Time</th>
                                    <th>User</th>
                                    <th>Activity</th>
                                    <th>Action Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($logs)): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo !empty($log['timestamp']) ? date('M d, Y h:i A', strtotime($log['timestamp'])) : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($log['username'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($log['activity'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($log['action_type'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No logs found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>