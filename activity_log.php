<?php
session_start();
require 'config.php';
require 'includes/header.php';

$logs = $pdo->query("SELECT l.*, u.username FROM activity_log l JOIN users u ON l.user_id = u.user_id ORDER BY l.created_at DESC")->fetchAll();
?>
<div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <div class="col-md-9">
        <h2>Activity Log</h2>
        <table class="table">
            <thead><tr><th>User</th><th>Activity</th><th>Table</th><th>Time</th></tr></thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo $log['username']; ?></td>
                    <td><?php echo $log['activity']; ?></td>
                    <td><?php echo $log['table_affected']; ?></td>
                    <td><?php echo $log['created_at']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require 'includes/footer.php'; ?>