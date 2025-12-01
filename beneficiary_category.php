<?php
session_start();
require 'config.php';
require 'includes/header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        $stmt = $pdo->prepare("INSERT INTO beneficiary_category (category_name, description) VALUES (?, ?)");
        $stmt->execute([$_POST['category_name'], $_POST['description']]);
        logActivity($pdo, $_SESSION['user_id'], 'Created beneficiary category', 'beneficiary_category', $pdo->lastInsertId());
        $message = 'Category created.';
    } elseif (isset($_POST['update'])) {
        $stmt = $pdo->prepare("UPDATE beneficiary_category SET category_name=?, description=? WHERE category_id=?");
        $stmt->execute([$_POST['category_name'], $_POST['description'], $_POST['id']]);
        logActivity($pdo, $_SESSION['user_id'], 'Updated beneficiary category', 'beneficiary_category', $_POST['id']);
        $message = 'Category updated.';
    } elseif (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM beneficiary_category WHERE category_id=?");
        $stmt->execute([$_POST['id']]);
        logActivity($pdo, $_SESSION['user_id'], 'Deleted beneficiary category', 'beneficiary_category', $_POST['id']);
        $message = 'Category deleted.';
    }
}

$categories = $pdo->query("SELECT * FROM beneficiary_category")->fetchAll();
?>
<div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <div class="col-md-9">
        <h2>Beneficiary Categories</h2>
        <?php if ($message) echo "<div class='alert alert-success'>$message</div>"; ?>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createModal">Add Category</button>
        <table class="table">
            <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?php echo $c['category_id']; ?></td>
                    <td><?php echo $c['category_name']; ?></td>
                    <td><?php echo $c['description']; ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editCategory(<?php echo $c['category_id']; ?>, '<?php echo $c['category_name']; ?>', '<?php echo $c['description']; ?>')">Edit</button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $c['category_id']; ?>">
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
                        <div class="modal-header"><h5>Add Category</h5></div>
                        <div class="modal-body">
                            <input type="text" name="category_name" class="form-control mb-2" placeholder="Category Name" required>
                            <textarea name="description" class="form-control mb