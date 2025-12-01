<?php
/**
 * Residents Management - BRDSS
 * CRUD operations for managing residents
 */
require_once 'config.php';

/** @var \PDO $pdo */

session_start();

$pageTitle = 'Residents - BRDSS';

// Require login
checkLogin();

$error = '';
$success = '';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF
        $postToken = filter_input(INPUT_POST, 'csrf_token', FILTER_UNSAFE_RAW) ?? '';
        if (!hash_equals($_SESSION['csrf_token'], (string)$postToken)) {
            throw new Exception('Invalid request token.');
        }

        // Collect & sanitize inputs
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $first_name = trim((string)filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $middle_name = trim((string)filter_input(INPUT_POST, 'middle_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $last_name = trim((string)filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $suffix = trim((string)filter_input(INPUT_POST, 'suffix', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $birthdate = trim((string)filter_input(INPUT_POST, 'birthdate', FILTER_UNSAFE_RAW));
        $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
        $gender = trim((string)filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $civil_status = trim((string)filter_input(INPUT_POST, 'civil_status', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $contact_no = trim((string)filter_input(INPUT_POST, 'contact_no', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $email = trim((string)filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $address = trim((string)filter_input(INPUT_POST, 'address', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $purok = trim((string)filter_input(INPUT_POST, 'purok', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $occupation = trim((string)filter_input(INPUT_POST, 'occupation', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $monthly_income = trim((string)filter_input(INPUT_POST, 'monthly_income', FILTER_SANITIZE_NUMBER_FLOAT));
        $status = trim((string)filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $remarks = trim((string)filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if (isset($_POST['create'])) {
            // Basic validation
            if ($first_name === '' || $last_name === '') {
                throw new Exception('First and last name are required.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO residents 
                (first_name, middle_name, last_name, suffix, birthdate, age, gender, civil_status, contact_no, email, address, purok, occupation, monthly_income, status, remarks) 
                VALUES 
                (:first_name, :middle_name, :last_name, :suffix, :birthdate, :age, :gender, :civil_status, :contact_no, :email, :address, :purok, :occupation, :monthly_income, :status, :remarks)
            ");
            $stmt->execute([
                ':first_name' => $first_name,
                ':middle_name' => $middle_name ?: null,
                ':last_name' => $last_name,
                ':suffix' => $suffix ?: null,
                ':birthdate' => $birthdate ?: null,
                ':age' => $age,
                ':gender' => $gender ?: null,
                ':civil_status' => $civil_status ?: null,
                ':contact_no' => $contact_no,
                ':email' => $email,
                ':address' => $address,
                ':purok' => $purok,
                ':occupation' => $occupation,
                ':monthly_income' => $monthly_income ?: null,
                ':status' => $status ?: 'Active',
                ':remarks' => $remarks
            ]);
            
            $newId = (int)$pdo->lastInsertId();
            logActivity((int)$_SESSION['user_id'], 'Created resident: ' . $first_name . ' ' . $last_name, 'CREATE', 'residents', $newId);
            
            $success = 'Resident created successfully.';
        } elseif (isset($_POST['update'])) {
            if ($id <= 0 || $first_name === '' || $last_name === '') {
                throw new Exception('Invalid input for update.');
            }

            $stmt = $pdo->prepare("
                UPDATE residents 
                SET first_name = :first_name, middle_name = :middle_name, last_name = :last_name, suffix = :suffix,
                    birthdate = :birthdate, age = :age, gender = :gender, civil_status = :civil_status, 
                    contact_no = :contact_no, email = :email, address = :address, purok = :purok,
                    occupation = :occupation, monthly_income = :monthly_income, status = :status, remarks = :remarks
                WHERE resident_id = :id
            ");
            $stmt->execute([
                ':first_name' => $first_name,
                ':middle_name' => $middle_name ?: null,
                ':last_name' => $last_name,
                ':suffix' => $suffix ?: null,
                ':birthdate' => $birthdate ?: null,
                ':age' => $age,
                ':gender' => $gender ?: null,
                ':civil_status' => $civil_status ?: null,
                ':contact_no' => $contact_no,
                ':email' => $email,
                ':address' => $address,
                ':purok' => $purok,
                ':occupation' => $occupation,
                ':monthly_income' => $monthly_income ?: null,
                ':status' => $status,
                ':remarks' => $remarks,
                ':id' => $id
            ]);

            logActivity((int)$_SESSION['user_id'], 'Updated resident: ' . $first_name . ' ' . $last_name, 'UPDATE', 'residents', $id);
            $success = 'Resident updated successfully.';
        } elseif (isset($_POST['delete'])) {
            if ($id <= 0) {
                throw new Exception('Invalid resident ID.');
            }

            // Get resident name before deletion for logging
            $stmt = $pdo->prepare("SELECT first_name, last_name FROM residents WHERE resident_id = :id");
            $stmt->execute([':id' => $id]);
            $resident = $stmt->fetch();
            
            $stmt = $pdo->prepare("DELETE FROM residents WHERE resident_id = :id");
            $stmt->execute([':id' => $id]);

            if ($resident) {
                logActivity((int)$_SESSION['user_id'], 'Deleted resident: ' . $resident['first_name'] . ' ' . $resident['last_name'], 'DELETE', 'residents', $id);
            }
            
            $success = 'Resident deleted successfully.';
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log('Resident operation error: ' . $e->getMessage());
}

// Fetch residents
$residents = [];
try {
    $stmt = $pdo->query("
        SELECT resident_id, first_name, middle_name, last_name, suffix, birthdate, age, gender, 
               civil_status, contact_no, email, address, purok, occupation, monthly_income, status, remarks
        FROM residents 
        WHERE deleted_at IS NULL
        ORDER BY resident_id DESC
    ");
    $residents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log('Fetch residents error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; }
        .sidebar { width: 250px; background: #f8f9fa; min-height: 100vh; padding: 20px; }
        .sidebar a { display: block; padding: 10px; margin: 5px 0; color: #333; text-decoration: none; border-radius: 5px; }
        .sidebar a:hover { background: #e9ecef; }
        .sidebar a.active { background: #0d6efd; color: white; }
        .main-content { flex: 1; padding: 30px; }
    </style>
</head>
<body>
<div class="sidebar">
    <h4>Menu</h4>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="residents.php" class="active">👥 Residents</a>
    <a href="resident_beneficiary.php">🎯 Beneficiaries</a>
    <a href="assistance_records.php">💰 Assistance</a>
    <a href="beneficiary_category.php">📂 Categories</a>
    <a href="users.php">👤 Users</a>
    <a href="activity_log.php">📝 Activity Log</a>
    <a href="backup_history.php">💾 Backups</a>
    <hr>
    <a href="logout.php">🚪 Logout</a>
</div>

<div class="main-content">
    <h2>👥 Residents Management</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success:</strong> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">
        ➕ Add Resident
    </button>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($residents)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No residents found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($residents as $r): ?>
                        <tr>
                            <td><?php echo (int)$r['resident_id']; ?></td>
                            <td>
                                <strong><?php echo sanitize($r['first_name'] . ' ' . ($r['middle_name'] ? $r['middle_name'] . ' ' : '') . $r['last_name']); ?></strong>
                            </td>
                            <td><?php echo $r['age'] !== null ? (int)$r['age'] : '-'; ?></td>
                            <td><?php echo sanitize($r['gender']); ?></td>
                            <td><?php echo sanitize($r['contact_no']); ?></td>
                            <td><?php echo sanitize($r['address']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $r['status'] === 'Active' ? 'success' : ($r['status'] === 'Inactive' ? 'secondary' : 'warning'); ?>">
                                    <?php echo sanitize($r['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick='openEditModal(<?php echo json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>)'>
                                    ✏️ Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteResident(<?php echo (int)$r['resident_id']; ?>)">
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Resident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" required placeholder="John">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" placeholder="M.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" required placeholder="Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Suffix</label>
                        <input type="text" name="suffix" class="form-control" placeholder="Jr., Sr., III">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Birthdate</label>
                                <input type="date" name="birthdate" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Age</label>
                                <input type="number" name="age" class="form-control" min="0" placeholder="30">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Civil Status</label>
                                <select name="civil_status" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Separated">Separated</option>
                                    <option value="Divorced">Divorced</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Contact No.</label>
                                <input type="text" name="contact_no" class="form-control" placeholder="09XX-XXX-XXXX">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="name@example.com">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Street address">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Purok</label>
                                <input type="text" name="purok" class="form-control" placeholder="Purok No.">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Occupation</label>
                                <input type="text" name="occupation" class="form-control" placeholder="Job title">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Monthly Income</label>
                                <input type="number" name="monthly_income" class="form-control" min="0" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Deceased">Deceased</option>
                                    <option value="Moved">Moved</option>
                                    <option value="Archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Additional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create" class="btn btn-primary">Save Resident</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Resident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" id="edit_middle_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Suffix</label>
                        <input type="text" name="suffix" id="edit_suffix" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Birthdate</label>
                                <input type="date" name="birthdate" id="edit_birthdate" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Age</label>
                                <input type="number" name="age" id="edit_age" class="form-control" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" id="edit_gender" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Civil Status</label>
                                <select name="civil_status" id="edit_civil_status" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Separated">Separated</option>
                                    <option value="Divorced">Divorced</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Contact No.</label>
                                <input type="text" name="contact_no" id="edit_contact_no" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" id="edit_address" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Purok</label>
                                <input type="text" name="purok" id="edit_purok" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Occupation</label>
                                <input type="text" name="occupation" id="edit_occupation" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Monthly Income</label>
                                <input type="number" name="monthly_income" id="edit_monthly_income" class="form-control" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Deceased">Deceased</option>
                                    <option value="Moved">Moved</option>
                                    <option value="Archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" id="edit_remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-primary">Update Resident</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="id" id="delete_id">
        <button type="submit" name="delete"></button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-dismiss alerts after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

function openEditModal(resident) {
    document.getElementById('edit_id').value = resident.resident_id || '';
    document.getElementById('edit_first_name').value = resident.first_name || '';
    document.getElementById('edit_middle_name').value = resident.middle_name || '';
    document.getElementById('edit_last_name').value = resident.last_name || '';
    document.getElementById('edit_suffix').value = resident.suffix || '';
    document.getElementById('edit_birthdate').value = resident.birthdate || '';
    document.getElementById('edit_age').value = resident.age || '';
    document.getElementById('edit_gender').value = resident.gender || '';
    document.getElementById('edit_civil_status').value = resident.civil_status || '';
    document.getElementById('edit_contact_no').value = resident.contact_no || '';
    document.getElementById('edit_email').value = resident.email || '';
    document.getElementById('edit_address').value = resident.address || '';
    document.getElementById('edit_purok').value = resident.purok || '';
    document.getElementById('edit_occupation').value = resident.occupation || '';
    document.getElementById('edit_monthly_income').value = resident.monthly_income || '';
    document.getElementById('edit_status').value = resident.status || 'Active';
    document.getElementById('edit_remarks').value = resident.remarks || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteResident(id) {
    if (confirm('Are you sure you want to delete this resident? This action cannot be undone.')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>
</body>
</html>
