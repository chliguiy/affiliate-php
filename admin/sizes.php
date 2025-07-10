<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once 'includes/auth.php';
require_once 'includes/AdminPermissions.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connexion à la base de données
$database = new Database();
$conn = $database->getConnection();

$permissions = new AdminPermissions($conn, $_SESSION['admin_id']);

if (!$permissions->canManageStock()) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                
                $stmt = $conn->prepare("INSERT INTO sizes (name) VALUES (?)");
                if ($stmt->execute([$name])) {
                    $_SESSION['success_message'] = "Taille ajoutée avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors de l'ajout de la taille.";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE sizes SET name = ?, status = ? WHERE id = ?");
                if ($stmt->execute([$name, $status, $id])) {
                    $_SESSION['success_message'] = "Taille mise à jour avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors de la mise à jour de la taille.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                $stmt = $conn->prepare("DELETE FROM sizes WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $_SESSION['success_message'] = "Taille supprimée avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors de la suppression de la taille.";
                }
                break;
        }
    }
}

// Récupérer la liste des tailles
$sizes = $conn->query("SELECT * FROM sizes ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tailles - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                    <h2>Gestion des Tailles</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSizeModal">
                        <i class="fas fa-plus me-2"></i>Ajouter une taille
                    </button>
                </div>

                <!-- Liste des tailles -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sizes as $size): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($size['name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $size['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo $size['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="editSize(<?php echo htmlspecialchars(json_encode($size)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteSize(<?php echo $size['id']; ?>)">
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
            </div>
        </div>
    </div>

    <!-- Modal d'ajout de taille -->
    <div class="modal fade" id="addSizeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une taille</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" class="form-control" required>
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

    <!-- Modal de modification de taille -->
    <div class="modal fade" id="editSizeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la taille</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_size_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" id="edit_size_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="status" id="edit_size_status" class="form-select">
                                <option value="active">Actif</option>
                                <option value="inactive">Inactif</option>
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
    function editSize(size) {
        document.getElementById('edit_size_id').value = size.id;
        document.getElementById('edit_size_name').value = size.name;
        document.getElementById('edit_size_status').value = size.status;
        
        new bootstrap.Modal(document.getElementById('editSizeModal')).show();
    }

    function deleteSize(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette taille ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html> 