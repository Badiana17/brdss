<?php
session_start();
require 'config.php';
require 'includes/header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        $stmt = $pdo->prepare("INSERT INTO residents (first_name, middle_name, last_name, birthdate, age, gender, contact_no, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $_POST['birthdate'], $_POST['age'], $_POST['gender'], $_POST['contact_no'], $_POST['address'], $_POST['status']]);
        logActivity($pdo, $_SESSION['user_id'], 'Created resident', 'residents', $pdo->lastInsertId());
        $message = 'Resident created.';
    } elseif (isset($_POST['update'])) {
        $stmt = $pdo->prepare("UPDATE residents SET first_name=?, middle_name=?, last_name=?, birthdate=?, age=?, gender=?, contact_no=?, address=?, status=? WHERE resident_id=?");
        $stmt->execute([$_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $_POST['birthdate'], $_POST['age'], $_POST['gender'], $_POST['contact_no'], $_POST['address'], $_POST['status'], $_POST['id']]);
        logActivity($pdo, $_SESSION['user_id'], 'Updated resident', 'residents', $_POST['id']);
        $message = 'Resident updated.';
    } elseif (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM residents WHERE resident_id=?");
        $stmt->execute([$_POST['id']]);
        logActivity($pdo, $_SESSION['user_id'], 'Deleted resident', 'residents', $_POST['id']);
        $message = 'Resident deleted.';
    }
}

$residents = $pdo->query("SELECT * FROM residents")->fetchAll();
?>
<div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <div class="col-md-9">
        <h2>Residents</h2>
        <?php if ($message) echo "<div class='alert alert-success'>$message</div>"; ?>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">Add Resident</button>
        <table class="table">
            <thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($residents as $r): ?>
                <tr>
                    <td><?php echo $r['resident_id']; ?></td>
                    <td><?php echo $r['first_name'] . ' ' . $r['last_name']; ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editResident(<?php echo $r['resident_id']; ?>)">Edit</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $r['resident_id']; ?>">
                            <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Modals for Create/Edit (similar for other tables) -->
        <div class="modal fade" id="createModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST">
                    <div class="modal-content">
                        <div class="modal-header"><h5>Add Resident</h5></div>
                        <div class="modal-body">
                            <!-- Form fields: first_name, middle_name, last_name, birthdate, age, gender, contact_no, address, status -->
                            <input type="text" name="first_name" class="form-control mb-2" placeholder="First Name" required>
                            <!-- Add other fields similarly -->
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="create" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function editResident(id) {
    // Fetch and populate edit modal (use AJAX or pre-load)
}
</script>
<?php require 'includes/footer.php'; ?>