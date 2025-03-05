<?php
ob_start();
require_once '../config/database.php';

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);

                // Check if category exists
                $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$name]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = "Category already exists!";
                } else {
                    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    if ($stmt->execute([$name, $description])) {
                        $_SESSION['success'] = "Category added successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to add category!";
                    }
                }
                break;

            case 'edit':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);

                // Check if category exists (excluding current)
                $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                $stmt->execute([$name, $id]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = "Category name already exists!";
                } else {
                    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                    if ($stmt->execute([$name, $description, $id])) {
                        $_SESSION['success'] = "Category updated successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to update category!";
                    }
                }
                break;

            case 'delete':
                $id = $_POST['id'];

                // Check if category has documents
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM documents WHERE category_id = ?");
                $stmt->execute([$id]);
                $count = $stmt->fetch()['count'];

                if ($count > 0) {
                    $_SESSION['error'] = "Cannot delete category with associated documents!";
                } else {
                    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $_SESSION['success'] = "Category deleted successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to delete category!";
                    }
                }
                break;
        }

        header('Location: categories.php');
        exit();
    }
}

// Get all categories with document counts
$stmt = $conn->prepare("
    SELECT c.*, COUNT(d.id) as document_count
    FROM categories c
    LEFT JOIN documents d ON c.id = d.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
");
$stmt->execute();
$categories = $stmt->fetchAll();

// Start output buffering for the content
ob_start();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Category Management</h1>
    <button class="btn btn-primary admin-btn" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fas fa-plus me-2"></i>Add New Category
    </button>
</div>

<!-- Categories Table -->
<div class="card admin-table">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo $category['id']; ?></td>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td><?php echo htmlspecialchars($category['description']); ?></td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo $category['document_count']; ?> documents
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editCategoryModal"
                                    data-id="<?php echo $category['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                    data-description="<?php echo htmlspecialchars($category['description']); ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($category['document_count'] == 0): ?>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="return confirmDelete('Are you sure you want to delete this category?')"
                                    form="deleteCategoryForm<?php echo $category['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            <form id="deleteCategoryForm<?php echo $category['id']; ?>"
                                  action="categories.php"
                                  method="POST"
                                  class="d-none">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade admin-modal" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="categories.php" method="POST" class="admin-form">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary admin-btn">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade admin-modal" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="categories.php" method="POST" class="admin-form">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary admin-btn">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle Edit Modal Data
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editCategoryModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const description = button.getAttribute('data-description');

            editModal.querySelector('#edit_id').value = id;
            editModal.querySelector('#edit_name').value = name;
            editModal.querySelector('#edit_description').value = description;
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require_once 'includes/admin_layout.php';
?>