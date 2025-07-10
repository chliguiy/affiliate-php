<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once 'includes/auth.php';
require_once 'includes/AdminPermissions.php';

$database = new Database();
$pdo = $database->getConnection();

$permissions = new AdminPermissions($pdo, $_SESSION['admin_id']);

if (!$permissions->canManageStock()) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $name = $_POST['name'];
                $slug = strtolower(str_replace(' ', '-', $name));
                $description = $_POST['description'];
                $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
                $status = $_POST['status'];
                $image = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $image = uniqid('cat_', true) . '.' . $ext;
                    $uploadDir = __DIR__ . '/../uploads/categories/';
                    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
                    move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image);
                }
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, parent_id, status, image) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $parent_id, $status, $image]);
                break;

            case 'update':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $slug = strtolower(str_replace(' ', '-', $name));
                $description = $_POST['description'];
                $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
                $status = $_POST['status'];

                $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $parent_id, $status, $id]);
                break;

            case 'delete':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                break;
        }
        header('Location: categories.php');
        exit();
    }
}

// Récupération des catégories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des catégories - Administration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .content-wrapper {
            margin-left: 280px;
            padding: 2rem;
        }
        .action-buttons {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des catégories</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                <i class="fas fa-plus"></i> Nouvelle catégorie
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                            <td><?php echo htmlspecialchars($category['slug']); ?></td>
                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $category['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo $category['status']; ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <button class="btn btn-sm btn-primary edit-category" 
                                        data-category='<?php echo json_encode($category); ?>'
                                        data-bs-toggle="modal" 
                                        data-bs-target="#categoryModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-category" 
                                        data-id="<?php echo $category['id']; ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Catégorie -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gérer une catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="categoryForm" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="id" id="categoryId">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Catégorie parente</label>
                            <select class="form-control" id="parent_id" name="parent_id">
                                <option value="">Aucune</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="active">Actif</option>
                                <option value="inactive">Inactif</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Image de couverture</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Êtes-vous sûr de vouloir supprimer cette catégorie ?
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du formulaire de catégorie
        document.querySelectorAll('.edit-category').forEach(button => {
            button.addEventListener('click', function() {
                const category = JSON.parse(this.dataset.category);
                document.querySelector('#categoryForm [name="action"]').value = 'update';
                document.querySelector('#categoryId').value = category.id;
                document.querySelector('#name').value = category.name;
                document.querySelector('#description').value = category.description;
                document.querySelector('#parent_id').value = category.parent_id || '';
                document.querySelector('#status').value = category.status;
            });
        });

        // Réinitialisation du formulaire pour une nouvelle catégorie
        document.querySelector('#categoryModal').addEventListener('hidden.bs.modal', function() {
            document.querySelector('#categoryForm').reset();
            document.querySelector('#categoryForm [name="action"]').value = 'create';
            document.querySelector('#categoryId').value = '';
        });

        // Gestion de la suppression
        document.querySelectorAll('.delete-category').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelector('#deleteId').value = this.dataset.id;
            });
        });
    </script>
</body>
</html> 