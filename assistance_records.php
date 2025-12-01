<?php
/**
 * Assistance Records Management
 * Handles CRUD operations for assistance records
 */
require_once 'config.php';

/** @var \PDO $pdo */

// Fallback helpers if not provided in config.php
if (!function_exists('sanitize')) {
    function sanitize($str) {
        return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('logActivity')) {
    function logActivity(int $userId, string $action, string $type = '', string $table = '', $recordId = null) {
        error_log("Activity: user={$userId}, action={$action}, type={$type}, table={$table}, id={$recordId}");
    }
}

if (!function_exists('checkLogin')) {
    function checkLogin() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
    }
}

// Ensure user is logged in
checkLogin();

$pageTitle = 'Assistance Records - BRDSS';
$message = '';
$messageType = 'success';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// ---------- Handle POST actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        try {
            $resident_id = (int)($_POST['resident_id'] ?? 0);
            $category_id = (int)($_POST['category_id'] ?? 0);
            $assistance_type = sanitize($_POST['assistance_type'] ?? '');
            $date_given = $_POST['date_given'] ?? null;
            $amount = (isset($_POST['amount']) && $_POST['amount'] !== '') ? (float)$_POST['amount'] : null;
            $remarks = sanitize($_POST['remarks'] ?? '');

            if (!$resident_id || !$category_id || !$assistance_type || !$date_given) {
                throw new Exception('Please fill all required fields.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO assistance_records
                    (resident_id, category_id, assistance_type, amount, date_given, encoded_by, remarks)
                VALUES
                    (:resident_id, :category_id, :assistance_type, :amount, :date_given, :encoded_by, :remarks)
            ");
            $stmt->execute([
                ':resident_id' => $resident_id,
                ':category_id' => $category_id,
                ':assistance_type' => $assistance_type,
                ':amount' => $amount,
                ':date_given' => $date_given,
                ':encoded_by' => $currentUserId,
                ':remarks' => $remarks
            ]);

            $lastId = (int)$pdo->lastInsertId();
            logActivity($currentUserId, 'Created assistance record', 'CREATE', 'assistance_records', $lastId);

            $message = 'Assistance record created successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
            error_log('Assistance create error: ' . $e->getMessage());
        }
    } elseif (isset($_POST['update'])) {
        try {
            $record_id = (int)($_POST['record_id'] ?? 0);
            $resident_id = (int)($_POST['resident_id'] ?? 0);
            $category_id = (int)($_POST['category_id'] ?? 0);
            $assistance_type = sanitize($_POST['assistance_type'] ?? '');
            $date_given = $_POST['date_given'] ?? null;
            $amount = (isset($_POST['amount']) && $_POST['amount'] !== '') ? (float)$_POST['amount'] : null;
            $remarks = sanitize($_POST['remarks'] ?? '');

            if (!$record_id || !$resident_id || !$category_id || !$assistance_type || !$date_given) {
                throw new Exception('Please fill all required fields.');
            }

            $stmt = $pdo->prepare("
                UPDATE assistance_records
                SET resident_id = :resident_id,
                    category_id = :category_id,
                    assistance_type = :assistance_type,
                    amount = :amount,
                    date_given = :date_given,
                    remarks = :remarks
                WHERE record_id = :record_id
            ");
            $stmt->execute([
                ':resident_id' => $resident_id,
                ':category_id' => $category_id,
                ':assistance_type' => $assistance_type,
                ':amount' => $amount,
                ':date_given' => $date_given,
                ':remarks' => $remarks,
                ':record_id' => $record_id
            ]);

            logActivity($currentUserId, 'Updated assistance record', 'UPDATE', 'assistance_records', $record_id);

            $message = 'Assistance record updated successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
            error_log('Assistance update error: ' . $e->getMessage());
        }
    } elseif (isset($_POST['delete'])) {
        try {
            $record_id = (int)($_POST['record_id'] ?? 0);
            if (!$record_id) {
                throw new Exception('Invalid record ID.');
            }

            $stmt = $pdo->prepare("DELETE FROM assistance_records WHERE record_id = :record_id");
            $stmt->execute([':record_id' => $record_id]);

            logActivity($currentUserId, 'Deleted assistance record', 'DELETE', 'assistance_records', $record_id);

            $message = 'Assistance record deleted successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
            error_log('Assistance delete error: ' . $e->getMessage());
        }
    }
}

// ---------- Fetch data ----------
$records = [];
$residents = [];
$categories = [];

try {
    $stmt = $pdo->query("
        SELECT 
            ar.record_id, ar.resident_id, ar.category_id, ar.assistance_type, ar.amount, ar.date_given, ar.encoded_by, ar.remarks,
            r.first_name, r.last_name,
            bc.category_name,
            u.username
        FROM assistance_records ar
        JOIN residents r ON ar.resident_id = r.resident_id
        JOIN beneficiary_category bc ON ar.category_id = bc.category_id
        LEFT JOIN users u ON ar.encoded_by = u.user_id
        ORDER BY ar.date_given DESC, ar.record_id DESC
    ");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log('Records fetch error: ' . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT resident_id, first_name, last_name FROM residents WHERE status = 'Active' ORDER BY last_name, first_name");
    $residents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log('Residents fetch error: ' . $e->getMessage());
}

try {
    $categoriesSql = "SELECT category_id, category_name FROM beneficiary_category";
    $cols = $pdo->query("SHOW COLUMNS FROM beneficiary_category")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('is_active', $cols, true)) {
        $categoriesSql .= " WHERE is_active = 1";
    }
    $categoriesSql .= " ORDER BY category_name";
    $stmt = $pdo->query($categoriesSql);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log('Categories fetch error: ' . $e->getMessage());
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
        <h2>Assistance Records</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">+ Add New Record</button>
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
                            <th>ID</th>
                            <th>Resident</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>By</th>
                            <th style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo (int)$record['record_id']; ?></td>
                                    <td><?php echo sanitize($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                    <td><?php echo sanitize($record['category_name']); ?></td>
                                    <td><?php echo sanitize($record['assistance_type']); ?></td>
                                    <td><?php echo ($record['amount'] !== null && $record['amount'] !== '') ? '₱' . number_format((float)$record['amount'], 2) : '-'; ?></td>
                                    <td><?php echo !empty($record['date_given']) ? date('M d, Y', strtotime($record['date_given'])) : '-'; ?></td>
                                    <td><?php echo sanitize($record['username'] ?? 'System'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick='editRecord(<?php echo json_encode($record, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>Edit</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                            <input type="hidden" name="record_id" value="<?php echo (int)$record['record_id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Assistance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Resident *</label>
                            <select name="resident_id" class="form-select" required>
                                <option value="">Select Resident</option>
                                <?php foreach ($residents as $r): ?>
                                <option value="<?php echo (int)$r['resident_id']; ?>"><?php echo sanitize($r['first_name'] . ' ' . $r['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?php echo (int)$c['category_id']; ?>"><?php echo sanitize($c['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type *</label>
                            <input type="text" name="assistance_type" class="form-control" placeholder="e.g., Medical" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount</label>
                            <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="date_given" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="create" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Assistance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="record_id" id="editId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Resident *</label>
                            <select name="resident_id" id="editResidentId" class="form-select" required>
                                <?php foreach ($residents as $r): ?>
                                <option value="<?php echo (int)$r['resident_id']; ?>"><?php echo sanitize($r['first_name'] . ' ' . $r['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category *</label>
                            <select name="category_id" id="editCategoryId" class="form-select" required>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?php echo (int)$c['category_id']; ?>"><?php echo sanitize($c['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type *</label>
                            <input type="text" name="assistance_type" id="editAssistanceType" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount</label>
                            <input type="number" name="amount" id="editAmount" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="date_given" id="editDateGiven" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" id="editRemarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editRecord(record) {
    document.getElementById('editId').value = record.record_id || '';
    document.getElementById('editResidentId').value = record.resident_id || '';
    document.getElementById('editCategoryId').value = record.category_id || '';
    document.getElementById('editAssistanceType').value = record.assistance_type || '';
    document.getElementById('editAmount').value = record.amount || '';
    document.getElementById('editDateGiven').value = record.date_given || '';
    document.getElementById('editRemarks').value = record.remarks || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Auto-dismiss alerts after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.remove();
    });
}, 5000);
</script>
</body>
</html>