<?php
session_start();
require 'config.php';
require 'includes/header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        $stmt = $pdo->prepare("INSERT INTO assistance_records (resident_id, category_id, assistance_type, date_given, encoded_by, remarks) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['resident_id'], $_POST['category_id'], $_POST['assistance_type'], $_POST['date_given'], $_SESSION['user_id'], $_POST['remarks']]);
        logActivity($pdo, $_SESSION['user_id'], 'Created assistance record', 'assistance_records', $pdo->lastInsertId());
        $message = 'Assistance record created.';
    } elseif (isset($_POST['update'])) {
        $stmt = $pdo->prepare("UPDATE assistance_records SET resident_id=?, category_id=?, assistance_type=?, date_given=?, remarks=? WHERE record_id=?");
        $stmt->execute([$_POST['resident_id'], $_POST['category_id'], $_POST['assistance_type'], $_POST['date_given'], $_POST['remarks'], $_POST['id']]);
        logActivity($pdo, $_SESSION['user_id'], 'Updated assistance record', 'assistance_records', $_POST['id']);
        $message = 'Assistance record updated.';
    } elseif (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM assistance_records WHERE record_id=?");
        $stmt->execute([$_POST['id']]);
        logActivity($pdo, $_SESSION['user_id'], 'Deleted assistance record', 'assistance_records', $_POST['id']);
        $message = 'Assistance record deleted.';
    }
}

$records = $pdo->query("SELECT ar.*, r.first_name, r.last_name, bc.category_name FROM assistance_records ar JOIN residents r ON ar.resident_id = r.resident_id JOIN beneficiary_category bc ON ar.category_id = bc.category_id")->fetchAll();
$residents = $pdo->query("SELECT resident_id, first_name, last_name FROM residents")->fetchAll();
$categories = $pdo->query("SELECT category_id, category_name FROM beneficiary_category")->fetchAll();
?>
<div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <div class="col-md-9">
        <h2>Assistance Records</h2>
        <?php if ($message) echo "<div class='alert alert-success'>$message</div>"; ?>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">Add Record</button>
        <table class="table">
            <thead><tr><th>ID</th><th>Resident</th><th>Category</th><th>Type</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><?php echo $r['record_id']; ?></td>
                    <td><?php echo $r['first_name'] . ' ' . $r['last_name']; ?></td>
                    <td><?php echo $r['category_name']; ?></td>
                    <td><?php echo $r['assistance_type']; ?></td>
                    <td><?php echo $r['date_given']; ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editRecord(<?php echo $r['record_id']; ?>, <?php echo $r['resident_id']; ?>, <?php echo $r['category_id']; ?>, '<?php echo $r['assistance_type']; ?>', '<?php echo $r['date_given']; ?>', '<?php echo $r['remarks']; ?>')">Edit</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $r['record_id']; ?>">
                            <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Create Modal -->
        <div class="modal fade" id="createModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST">
                    <div class="modal-content">
                        <div class="modal-header"><h5>Add Assistance Record</h5></div>
                        <div class="modal-body">
                            <select name="resident_id" class="form-control mb-2" required>
                                <option value="">Select Resident</option>
                                <?php foreach ($residents as $res): ?>
                                <option value="<?php echo $res['resident_id']; ?>"><?php echo $res['first_name'] . ' ' . $res['last_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="category_id" class="form-control mb-2" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="assistance_type" class="form-control mb-2" placeholder="Assistance Type" required>
                            <input type="date" name="date_given" class="form-control mb-2" required>
                            <textarea name="remarks" class="form-control mb-2" placeholder="Remarks"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="create" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST">
                    <div class="modal-content">
                        <div class="modal-header"><h5>Edit Assistance Record</h5></div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="editId">
                            <select name="resident_id" id="editResidentId" class="form-control mb-2" required>
                                <?php foreach ($residents as $res): ?>
                                <option value="<?php echo $res['resident_id']; ?>"><?php echo $res['first_name'] . ' ' . $res['last_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="category_id" id="editCategoryId" class="form-control mb-2" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="assistance_type" id="editAssistanceType" class="form-control mb-2" placeholder="Assistance Type" required>
                            <input type="date" name="date_given" id="editDateGiven" class="form-control mb-2" required>
                            <textarea name="remarks" id="editRemarks" class="form-control mb-2" placeholder="Remarks"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function editRecord(id, residentId, categoryId, assistanceType, dateGiven, remarks) {
    document.getElementById('editId').value = id;
    document.getElementById('editResidentId').value = residentId;
    document.getElementById('editCategoryId').value = categoryId;
    document.getElementById('editAssistanceType').value = assistanceType;
    document.getElementById('editDateGiven').value = dateGiven;
    document.getElementById('editRemarks').value = remarks;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php require 'includes/footer.php'; ?>