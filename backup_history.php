<?php
session_start();
require 'config.php';
require 'includes/header.php';

if (isset($_POST['backup'])) {
    $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $command = "mysqldump --user=root --password= --host=localhost brdss_db > $backup_file";
    exec($command);
    $stmt = $pdo->prepare("INSERT INTO backup_history (user_id, file_location, remarks) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $backup_file, 'Manual backup']);
    logActivity($pdo, $_SESSION['user_id'], 'Created backup', 'backup_history', $pdo->lastInsertId());
    $message = 'Backup created.';
}

$backups = $pdo->query("SELECT * FROM backup_history ORDER BY backup_date DESC")->fetchAll();
?>
<div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <div class="col-md-9">
        <h2>Backup History</h2>
        <?php if (isset($message)) echo "<div class='alert alert-success'>$message</div>"; ?>
        <form method="POST">
            <button type="submit" name="backup" class="btn btn-primary">Create Backup</button>
        </form>
        <table class="table mt-3">
            <thead><tr><th>Date</th><th>File</th><th>Remarks</th></tr></thead>
            <tbody>
                <?php foreach ($backups as $b): ?>
                <tr>
                    <td><?php echo $b['backup_date']; ?></td>
                    <td><?php echo $b['file_location']; ?></td>
                    <td><?php echo $b['remarks']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require 'includes/footer.php'; ?>