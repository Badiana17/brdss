<?php
session_start();
require 'config.php';

/** @var \PDO $pdo */

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Flash message
if (!empty($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
} else {
    $message = '';
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF
        $postToken = filter_input(INPUT_POST, 'csrf_token', FILTER_UNSAFE_RAW) ?? '';
        if (!hash_equals($_SESSION['csrf_token'], (string)$postToken)) {
            throw new RuntimeException('Invalid request token.');
        }

        $resident_id = isset($_POST['resident_id']) ? (int)$_POST['resident_id'] : 0;
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

        if (isset($_POST['assign'])) {
            if ($resident_id <= 0 || $category_id <= 0) {
                throw new RuntimeException('Invalid resident or category.');
            }
            // Check if already assigned
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM resident_beneficiary WHERE resident_id = :resident_id AND category_id = :category_id");
            $stmt->execute([':resident_id' => $resident_id, ':category_id' => $category_id]);
            if ($stmt->fetchColumn() > 0) {
            throw new RuntimeException('Resident is already assigned to this category.');
            }
            $stmt = $pdo->prepare("INSERT INTO resident_beneficiary (resident_id, category_id, is_active, date_classified) VALUES (:resident_id, :category_id, 1, NOW())");
            $stmt->execute([':resident_id' => $resident_id, ':category_id' => $category_id]);
            $newId = (int)$pdo->lastInsertId();
            if (function_exists('logActivity')) {
                try { logActivity((int)$_SESSION['user_id'], 'Assigned beneficiary category to resident', 'resident_beneficiary', $newId); } catch (Throwable $e) { error_log($e->getMessage()); }
            }
            $_SESSION['flash_message'] = 'Category assigned to resident.';
            header('Location: resident_beneficiary.php');
            exit;
        }

        if (isset($_POST['unassign'])) {
            if ($resident_id <= 0 || $category_id <= 0) {
                throw new RuntimeException('Invalid resident or category.');
            }
            $stmt = $pdo->prepare("DELETE FROM resident_beneficiary WHERE resident_id = :resident_id AND category_id = :category_id");
            $stmt->execute([':resident_id' => $resident_id, ':category_id' => $category_id]);
            if (function_exists('logActivity')) {
                try { logActivity((int)$_SESSION['user_id'], 'Unassigned beneficiary category from resident', 'resident_beneficiary', $resident_id); } catch (Throwable $e) { error_log($e->getMessage()); }
            }
            $_SESSION['flash_message'] = 'Category unassigned from resident.';
            header('Location: resident_beneficiary.php');
            exit;
        }
    }
} catch (Throwable $e) {
    error_log('beneficiary error: ' . $e->getMessage());
    $message = $e->getMessage();
}

// Fetch residents with their beneficiary categories
try {
    $stmt = $pdo->query("
        SELECT
            r.resident_id,
            r.first_name,
            r.middle_name,
            r.last_name,
            r.status,
            GROUP_CONCAT(bc.category_name SEPARATOR ', ') as categories
        FROM residents r
        LEFT JOIN resident_beneficiary rb ON r.resident_id = rb.resident_id AND rb.is_active = 1
        LEFT JOIN beneficiary_category bc ON rb.category_id = bc.category_id
        WHERE r.deleted_at IS NULL
        GROUP BY r.resident_id, r.first_name, r.middle_name, r.last_name, r.status
        ORDER BY r.last_name, r.first_name
    ");
    $residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Fetch residents error: ' . $e->getMessage());
    $residents = [];
}

// Fetch all active categories for assignment
try {
    $stmt = $pdo->query("SELECT category_id, category_name FROM beneficiary_category WHERE is_active = 1 ORDER BY category_name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Fetch categories error: ' . $e->getMessage());
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Beneficiaries - BRDSS</title>
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
            <h2>🎯 Resident Beneficiaries</h2>
            <p class="text-muted mb-4">Assign and manage beneficiary categories for residents</p>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo (strpos($message, 'assigned') !== false || strpos($message, 'unassigned') !== false) ? 'success' : 'danger'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <table class="table">
            <thead><tr><th>ID</th><th>Resident Name</th><th>Status</th><th>Beneficiary Categories</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($residents)): ?>
                    <tr><td colspan="5">No residents found.</td></tr>
                <?php else: ?>
                    <?php foreach ($residents as $r): ?>
                        <tr>
                            <td><?php echo (int)$r['resident_id']; ?></td>
                            <td><?php echo htmlspecialchars($r['first_name'] . ' ' . ($r['middle_name'] ? $r['middle_name'] . ' ' : '') . $r['last_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($r['categories'] ?: 'None', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="assignCategory(<?php echo (int)$r['resident_id']; ?>, '<?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name'], ENT_QUOTES, 'UTF-8'); ?>')">Assign Category</button>
                                <?php if ($r['categories']): ?>
                                    <button class="btn btn-sm btn-warning" onclick="unassignCategory(<?php echo (int)$r['resident_id']; ?>, '<?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name'], ENT_QUOTES, 'UTF-8'); ?>')">Unassign Category</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

        <!-- Assign Modal -->
        <div class="modal fade" id="assignModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="resident_id" id="assignResidentId">
                    <div class="modal-content">
                        <div class="modal-header"><h5>Assign Beneficiary Category</h5></div>
                        <div class="modal-body">
                            <p>Assigning category to: <strong id="assignResidentName"></strong></p>
                            <select name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo (int)$c['category_id']; ?>"><?php echo htmlspecialchars($c['category_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="assign" class="btn btn-primary">Assign</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Unassign Modal -->
        <div class="modal fade" id="unassignModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="resident_id" id="unassignResidentId">
                    <div class="modal-content">
                        <div class="modal-header"><h5>Unassign Beneficiary Category</h5></div>
                        <div class="modal-body">
                            <p>Unassigning category from: <strong id="unassignResidentName"></strong></p>
                            <select name="category_id" id="unassignCategoryId" class="form-control" required>
                                <option value="">Select Category to Unassign</option>
                                <!-- Categories will be populated by JavaScript -->
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="unassign" class="btn btn-danger">Unassign</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function assignCategory(residentId, residentName) {
    document.getElementById('assignResidentId').value = residentId;
    document.getElementById('assignResidentName').textContent = residentName;
    new bootstrap.Modal(document.getElementById('assignModal')).show();
}

// Store resident categories data
const residentCategories = {};
<?php foreach ($residents as $r): ?>
    <?php if ($r['categories']): ?>
        residentCategories[<?php echo (int)$r['resident_id']; ?>] = [
            <?php
            // Parse categories string to extract IDs and names
            $catParts = explode(', ', $r['categories']);
            $catArray = [];
            foreach ($catParts as $catStr) {
                if (preg_match('/^(.+) \((\d+)\)$/', $catStr, $matches)) {
                    $catArray[] = "{id: " . (int)$matches[2] . ", name: '" . addslashes($matches[1]) . "'}";
                }
            }
            echo implode(', ', $catArray);
            ?>
        ];
    <?php endif; ?>
<?php endforeach; ?>

function unassignCategory(residentId, residentName) {
    document.getElementById('unassignResidentId').value = residentId;
    document.getElementById('unassignResidentName').textContent = residentName;

    const select = document.getElementById('unassignCategoryId');
    select.innerHTML = '<option value="">Select Category to Unassign</option>';
    
    const categories = residentCategories[residentId] || [];
    categories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name;
        select.appendChild(option);
    });

    new bootstrap.Modal(document.getElementById('unassignModal')).show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>