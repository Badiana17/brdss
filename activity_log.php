<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require 'config.php';
require 'includes/header.php';

/** @var \PDO $pdo */

try {
    $stmt = $pdo->query("SELECT l.*, u.username FROM activity_log l JOIN users u ON l.user_id = u.user_id ORDER BY l.created_at DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Activity log query error: ' . $e->getMessage());
    $logs = [];
}
?>
<div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <div class="col-md-9">
        <h2>Activity Log</h2>
        <table class="table">
            <thead><tr><th>User</th><th>Activity</th><th>Table</th><th>Time</th></tr></thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr><td colspan="4">No logs found.</td></tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($log['activity'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($log['table_affected'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($log['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
