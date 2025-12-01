<?php
/**
 * Dashboard - BRDSS
 * Displays key statistics and recent activity
 */
require_once 'config.php';

/** @var \PDO $pdo */

checkLogin();

$pageTitle = 'Dashboard - BRDSS';
$message = '';
$messageType = 'success';

// Helper function to fetch count from database
function fetchCount(\PDO $pdo, string $sql): int {
    try {
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        return $result ? (int)$result[0] : 0;
    } catch (Exception $e) {
        error_log('Count query error: ' . $e->getMessage());
        return 0;
    }
}

// Fetch statistics
$totalResidents = fetchCount($pdo, "SELECT COUNT(*) FROM residents WHERE status = 'Active'");
$totalBeneficiaries = fetchCount($pdo, "SELECT COUNT(DISTINCT resident_id) FROM resident_beneficiary WHERE is_active = 1");
$totalAssistance = fetchCount($pdo, "SELECT COUNT(*) FROM assistance_records WHERE YEAR(date_given) = YEAR(CURDATE())");
$totalCategories = fetchCount($pdo, "SELECT COUNT(*) FROM beneficiary_category WHERE is_active = 1");

// Fetch recent activity
$activityLogs = [];
try {
    $stmt = $pdo->query("
        SELECT 
            al.log_id, 
            al.user_id, 
            al.activity, 
            al.action_type, 
            al.timestamp,
            u.username
        FROM activity_log al
        JOIN users u ON al.user_id = u.user_id
        ORDER BY al.timestamp DESC
        LIMIT 15
    ");
    $activityLogs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log('Activity log fetch error: ' . $e->getMessage());
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo sanitize($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 1rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }
        .activity-table {
            margin-top: 2rem;
        }
    </style>
</head>
<body class="bg-light">
<div class="container my-4">
    <div class="mb-4">
        <h2 class="mb-1">Dashboard</h2>
        <p class="text-muted small">Welcome back, <?php echo sanitize($_SESSION['username'] ?? 'User'); ?>!</p>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo sanitize($messageType); ?> alert-dismissible fade show" role="alert">
            <?php echo sanitize($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Active Residents</h3>
            <div class="stat-number"><?php echo number_format($totalResidents); ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Total Beneficiaries</h3>
            <div class="stat-number"><?php echo number_format($totalBeneficiaries); ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Assistance This Year</h3>
            <div class="stat-number"><?php echo number_format($totalAssistance); ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Active Categories</h3>
            <div class="stat-number"><?php echo number_format($totalCategories); ?></div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card shadow-sm activity-table">
        <div class="card-header bg-light">
            <h5 class="mb-0">Recent Activity</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Activity</th>
                            <th>Action Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($activityLogs)): ?>
                            <?php foreach ($activityLogs as $log): ?>
                                <tr>
                                    <td><?php echo !empty($log['timestamp']) ? date('M d, Y h:i A', strtotime($log['timestamp'])) : '-'; ?></td>
                                    <td><?php echo sanitize($log['username'] ?? 'Unknown'); ?></td>
                                    <td><?php echo sanitize($log['activity'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo sanitize($log['action_type'] ?? 'N/A'); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No activity yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="mt-4 text-center">
        <a href="residents.php" class="btn btn-primary btn-sm me-2">Manage Residents</a>
        <a href="assistance_records.php" class="btn btn-primary btn-sm me-2">Assistance Records</a>
        <a href="beneficiary_category.php" class="btn btn-primary btn-sm">Categories</a>
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