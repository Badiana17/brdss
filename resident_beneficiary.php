<?php
session_start();
require 'config.php';
require 'includes/header.php';

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

        // Helper to fetch sanitized inputs
        $name = trim((string)filter_input(INPUT_POST, 'category_name', FILTER_UNSAFE_RAW));
        $description = trim((string)filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW));
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if (isset($_POST['create'])) {
            if ($name === '') {
                throw new RuntimeException('Category name is required.');
            }
            $stmt = $pdo->prepare("INSERT INTO beneficiary_category (category_name, description) VALUES (:name, :description)");
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            $stmt->execute();
            $newId = (int)$pdo->lastInsertId();
            if (function_exists('logActivity')) {
                // call signature used elsewhere: logActivity($user_id, $activity, $table, $row_id)
                try { logActivity((int)$_SESSION['user_id'], 'Created beneficiary category', 'beneficiary_category', $newId); } catch (Throwable $e) { error_log($e->getMessage()); }
            }
            $_SESSION['flash_message'] = 'Category created.';
            header('Location: resident_beneficiary.php');
            exit;
        }

        if (isset($_POST['update'])) {
            if ($id <= 0 || $name === '') {
                throw new RuntimeException('Invalid input for update.');
            }
            $stmt = $pdo->prepare("UPDATE beneficiary_category SET category_name = :name, description = :description WHERE category_id = :id");
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if (function_exists('logActivity')) {
                try { logActivity((int)$_SESSION['user_id'], 'Updated beneficiary category', 'beneficiary_category', $id); } catch (Throwable $e) { error_log($e->getMessage()); }
            }
            $_SESSION['flash_message'] = 'Category updated.';
            header('Location: resident_beneficiary.php');
            exit;
        }

        if (isset($_POST['delete'])) {
            if ($id <= 0) {
                throw new RuntimeException('Invalid category id.');
            }
            $stmt = $pdo->prepare("DELETE FROM beneficiary_category WHERE category_id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if (function_exists('logActivity')) {
                try { logActivity((int)$_SESSION['user_id'], 'Deleted beneficiary category', 'beneficiary_category', $id); } catch (Throwable $e) { error_log($e->getMessage()); }
            }
            $_SESSION['flash_message'] = 'Category deleted.';
            header('Location: resident_beneficiary.php');
            exit;
        }
    }
} catch (Throwable $e) {
    error_log('beneficiary error: ' . $e->getMessage());
    $message = $e->getMessage();
}

// Fetch categories
try {
    $stmt = $pdo->query("SELECT category_id, category_name, description FROM beneficiary_category ORDER BY category_id DESC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Fetch categories error: ' . $e->getMessage());
    $categories = [];
}
?>
<div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <div class="col-md-9">
        <h2>Beneficiary Categories</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo ($message === 'Category created.' || $message === 'Category updated.' || $message === 'Category deleted.') ? 'success' : 'danger'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">Add Category</button>

        <table class="table">
            <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="4">No categories found.</td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><?php echo (int)$c['category_id']; ?></td>
                            <td><?php echo htmlspecialchars($c['category_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($c['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editCategory(<?php echo json_encode((int)$c['category_id']); ?>, <?php echo json_encode($c['category_name']); ?>, <?php echo json_encode($c['description']); ?>)">Edit</button>

                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$c['category_id']; ?>">
                                    <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Create Modal -->
        <div class="modal fade" id="createModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="modal-content">
                        <div class="modal-header"><h5>Add Category</h5></div>
                        <div class="modal-body">
                            <input type="text" name="category_name" class="form-control mb-2" placeholder="Category Name" required>
                            <textarea name="description" class="form-control mb-2" placeholder="Description"></textarea>
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="modal-content">
                        <div class="modal-header"><h5>Edit Category</h5></div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="editId">
                            <input type="text" name="category_name" id="editCategoryName" class="form-control mb-2" placeholder="Category Name" required>
                            <textarea name="description" id="editDescription" class="form-control mb-2" placeholder="Description"></textarea>
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
function editCategory(id, name, description) {
    document.getElementById('editId').value = id;
    document.getElementById('editCategoryName').value = name;
    document.getElementById('editDescription').value = description;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require 'includes/footer.php'; ?>
