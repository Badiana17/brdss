<?php
/**
 * Beneficiary Category Management - BRDSS
 * Handles CRUD operations for beneficiary categories
 */
require_once 'config.php';

/** @var \PDO $pdo */

checkLogin();

$pageTitle = 'Beneficiary Categories - BRDSS';
$message = '';
$messageType = 'success';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// ---------- Handle POST actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        try {
            $category_name = sanitize($_POST['category_name'] ?? '');
            $description = sanitize($_POST['description'] ?? '');

            if (!$category_name) {
                throw new Exception('Category name is required.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO beneficiary_category (category_name, description, is_active)
                VALUES (:category_name, :description, 1)
            ");
            $stmt->execute([
                ':category_name' => $category_name,
                ':description' => $description
            ]);

            $categoryId = (int)$pdo->lastInsertId();
            logActivity($currentUserId, 'Created beneficiary category', 'CREATE', 'beneficiary_category', $categoryId);

            $message = 'Category created successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
            error_log('Category create error: ' . $e->getMessage());
        }
    } elseif (isset($_POST['update'])) {
        try {
            $category_id = (int)($_POST['category_id'] ?? 0);
            $category_name = sanitize($_POST['category_name'] ?? '');
            $description = sanitize($_POST['description'] ?? '');

            if (!$category_id || !$category_name) {
                throw new Exception('Category ID and name are required.');
            }

            $stmt = $pdo->prepare("
                UPDATE beneficiary_category
                SET category_name = :category_name,
                    description = :description
                WHERE category_id = :category_id
            ");
            $stmt->execute([
                ':category_name' => $category_name,
                ':description' => $description,
                ':category_id' => $category_id
            ]);

            logActivity($currentUserId, 'Updated beneficiary category', 'UPDATE', 'beneficiary_category', $category_id);

            $message = 'Category updated successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
            error_log('Category update error: ' . $e->getMessage());
        }
    } elseif (isset($_POST['delete'])) {
        try {
            $category_id = (int)($_POST['category_id'] ?? 0);

            if (!$category_id) {
                throw new Exception('Invalid category ID.');
            }

            $stmt = $pdo->prepare("DELETE FROM beneficiary_category WHERE category_id = :category_id");
            $stmt->execute([':category_id' => $category_id]);

            logActivity($currentUserId, 'Deleted beneficiary category', 'DELETE', 'beneficiary_category', $category_id);

            $message = 'Category deleted successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
            error_log('Category delete error: ' . $e->getMessage());
        }
    }
}

// ---------- Fetch categories ----------
$categories = [];
try {
    $stmt = $pdo->query("
        SELECT category_id, category_name, description, is_active
        FROM beneficiary_category
        ORDER BY category_name ASC
    ");
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
            <h2>📋 Beneficiary Categories</h2>
            <p class="text-muted mb-4">Manage assistance program categories</p>

            <div class="mb-3">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">+ Add New Category</button>
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
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th style="width:150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo (int)$category['category_id']; ?></td>
                                    <td><?php echo sanitize($category['category_name']); ?></td>
                                    <td><?php echo sanitize($category['description']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $category['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick='editCategory(<?php echo json_encode($category, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>Edit</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete?');">
                                            <input type="hidden" name="category_id" value="<?php echo (int)$category['category_id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No categories found.</td>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Beneficiary Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="category_name" class="form-control" placeholder="e.g., Medical Assistance" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe this category..."></textarea>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Beneficiary Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="editId">
                    <div class="form-group mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="category_name" id="editCategoryName" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
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
/**
 * Populate and open the edit modal with category data
 */
function editCategory(category) {
    try {
        document.getElementById('editId').value = category.category_id || '';
        document.getElementById('editCategoryName').value = category.category_name || '';
        document.getElementById('editDescription').value = category.description || '';
        
        new bootstrap.Modal(document.getElementById('editModal')).show();
    } catch (err) {
        console.error('Edit category error:', err);
        alert('Unable to open edit form.');
    }
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