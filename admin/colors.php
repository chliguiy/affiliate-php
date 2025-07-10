<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Connexion à la base de données
$conn = $database->getConnection();

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $color_code = trim($_POST['color_code']);
                $status = 'active';
                if ($name && $color_code) {
                    $stmt = $conn->prepare("INSERT INTO colors (name, color_code, status) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $color_code, $status]);
                    $_SESSION['success_message'] = "Couleur ajoutée avec succès.";
                } else {
                    $_SESSION['error_message'] = "Tous les champs sont obligatoires.";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                $color_code = trim($_POST['color_code']);
                $status = $_POST['status'] === 'active' ? 'active' : 'inactive';

                if ($id && $name && $color_code) {
                    $stmt = $conn->prepare("UPDATE colors SET name = ?, color_code = ?, status = ? WHERE id = ?");
                    $stmt->execute([$name, $color_code, $status, $id]);
                    $_SESSION['success_message'] = "Couleur mise à jour avec succès.";
                } else {
                    $_SESSION['error_message'] = "Tous les champs sont obligatoires.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                if ($id) {
                    $stmt = $conn->prepare("DELETE FROM colors WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success_message'] = "Couleur supprimée avec succès.";
                }
                break;
        }
    }
}

// Récupérer la liste des couleurs
$colors = $conn->query("SELECT * FROM colors ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Couleurs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-block;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Gestion des Couleurs</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addColorModal">
                        <i class="fas fa-plus me-2"></i>Ajouter une couleur
                    </button>
                </div>

                <!-- Liste des couleurs -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Couleur</th>
                                        <th>Code</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($colors as $color): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($color['name']); ?></td>
                                        <td>
                                            <span class="color-preview" style="background-color: <?php echo htmlspecialchars($color['color_code']); ?>"></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($color['color_code']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo (isset($color['status']) && $color['status'] === 'active') ? 'success' : 'danger'; ?>">
                                                <?php echo htmlspecialchars($color['status'] ?? ''); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick='editColor(<?php echo json_encode(["id"=>$color["id"],"name"=>$color["name"],"color_code"=>$color["color_code"],"status"=>$color["status"]]); ?>)'>
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette couleur ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $color['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'ajout de couleur -->
    <div class="modal fade" id="addColorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une couleur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Code couleur</label>
                            <input type="color" name="color_code" class="form-control form-control-color" value="#000000" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de modification de couleur -->
    <div class="modal fade" id="editColorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la couleur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_color_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" id="edit_color_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Code couleur</label>
                            <input type="color" name="color_code" id="edit_color_code" class="form-control form-control-color" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="status" id="edit_color_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function editColor(color) {
        var modal = new bootstrap.Modal(document.getElementById('editColorModal'));
        document.getElementById('edit_color_id').value = color.id;
        document.getElementById('edit_color_name').value = color.name;
        document.getElementById('edit_color_code').value = color.color_code;
        document.getElementById('edit_color_status').value = color.status;
        modal.show();
    }
    </script>
</body>
</html> 