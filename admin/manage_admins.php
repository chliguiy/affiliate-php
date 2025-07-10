<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php'; // Assurez-vous que ce chemin est correct
require_once 'includes/auth.php';
require_once 'includes/AdminLogger.php';
require_once 'includes/AdminPermissions.php';

// Connexion à la base de données
$database = new Database();
$pdo = $database->getConnection(); // Assurez-vous d'assigner la connexion à $pdo ici

// Vérifier si l'admin a les permissions nécessaires
$permissions = new AdminPermissions($pdo, $_SESSION['admin_id']);
if (!$permissions->canManageAdmins()) {
    header('Location: dashboard.php');
    exit;
}

$logger = new AdminLogger($pdo, $_SESSION['admin_id']);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['full_name'], $_POST['role'])) {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO admins (username, email, password, full_name, role, permissions)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $_POST['username'],
                            $_POST['email'],
                            password_hash($_POST['password'], PASSWORD_DEFAULT),
                            $_POST['full_name'],
                            $_POST['role'],
                            json_encode($_POST['permissions'] ?? [])
                        ]);
                        
                        $logger->log('create', 'admin', $pdo->lastInsertId(), [
                            'username' => $_POST['username'],
                            'role' => $_POST['role']
                        ]);
                        
                        $success = "Administrateur ajouté avec succès.";
                    } catch (PDOException $e) {
                        $error = "Erreur lors de l'ajout de l'administrateur.";
                    }
                }
                break;

            case 'edit':
                if (isset($_POST['id'], $_POST['username'], $_POST['email'], $_POST['full_name'], $_POST['role'])) {
                    try {
                        $updates = [
                            'username' => $_POST['username'],
                            'email' => $_POST['email'],
                            'full_name' => $_POST['full_name'],
                            'role' => $_POST['role'],
                            'permissions' => json_encode($_POST['permissions'] ?? [])
                        ];
                        
                        if (!empty($_POST['password'])) {
                            $updates['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        }
                        
                        $sql = "UPDATE admins SET ";
                        $sql .= implode(', ', array_map(function($key) { return "$key = ?"; }, array_keys($updates)));
                        $sql .= " WHERE id = ?";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([...array_values($updates), $_POST['id']]);
                        
                        $logger->log('update', 'admin', $_POST['id'], [
                            'username' => $_POST['username'],
                            'role' => $_POST['role']
                        ]);
                        
                        $success = "Administrateur modifié avec succès.";
                    } catch (PDOException $e) {
                        $error = "Erreur lors de la modification de l'administrateur.";
                    }
                }
                break;

            case 'delete':
                if (isset($_POST['id'])) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ? AND id != ?");
                        $stmt->execute([$_POST['id'], $_SESSION['admin_id']]);
                        
                        if ($stmt->rowCount() > 0) {
                            $logger->log('delete', 'admin', $_POST['id']);
                            $success = "Administrateur supprimé avec succès.";
                        } else {
                            $error = "Impossible de supprimer votre propre compte.";
                        }
                    } catch (PDOException $e) {
                        $error = "Erreur lors de la suppression de l'administrateur.";
                    }
                }
                break;
        }
    }
}

// Récupérer la liste des administrateurs
$stmt = $pdo->prepare('SELECT id, username, full_name, email, role, permissions, created_at FROM admins ORDER BY id DESC');
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Définir les permissions disponibles
$available_permissions = [
    'canManageAdmins' => 'Gestion des Admins',
    'canManageUsers' => 'Gestion des Utilisateurs & Affiliés',
    'canManageOrders' => 'Gestion des Commandes',
    'canManageStock' => 'Gestion du Stock (Produits, Catégories, etc.)',
    'canViewReports' => 'Voir les Rapports de Ventes',
    'canViewLogs' => 'Voir les Logs',
    'canViewDashboard' => 'Accès au Tableau de Bord'
];

// Inclure l'en-tête et la connexion à la base de données
include_once '../includes/header.php';
include_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Admins</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .admin-table th, .admin-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .admin-table th {
            background-color: #f5f5f5;
        }
        .admin-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .permissions-group {
            margin-top: 10px;
        }
        .permissions-group label {
            display: inline-block;
            margin-right: 15px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des Admins</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            Ajouter un Admin
        </button>
    </div>
    <div class="mb-3">
        <input type="text" id="searchInput" class="form-control" placeholder="Rechercher un admin...">
    </div>
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Date de création</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="adminsTableBody">
        <?php foreach ($admins as $admin): ?>
            <tr>
                <td><?= htmlspecialchars($admin['id']) ?></td>
                <td><?= htmlspecialchars($admin['full_name']) ?></td>
                <td><?= htmlspecialchars($admin['email']) ?></td>
                <td><?= htmlspecialchars($admin['role']) ?></td>
                <td><?= htmlspecialchars($admin['created_at']) ?></td>
                <td>
                    <button type="button" class="btn btn-sm btn-warning edit-btn" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editAdminModal"
                            data-id="<?= $admin['id'] ?>"
                            data-username="<?= htmlspecialchars($admin['username'] ?? '') ?>"
                            data-full_name="<?= htmlspecialchars($admin['full_name'] ?? '') ?>"
                            data-email="<?= htmlspecialchars($admin['email'] ?? '') ?>"
                            data-role="<?= htmlspecialchars($admin['role'] ?? '') ?>"
                            data-permissions='<?= htmlspecialchars($admin['permissions'] ?? '[]', ENT_QUOTES, 'UTF-8') ?>'>
                        Modifier
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-btn" 
                            data-bs-toggle="modal" 
                            data-bs-target="#deleteAdminModal" 
                            data-id="<?= $admin['id'] ?>">
                        Supprimer
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">Ajouter un Administrateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="manage_admins.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nom Complet</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Rôle</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="viewer">Viewer</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div class="permissions-group">
                            <?php foreach ($available_permissions as $perm => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $perm ?>" id="perm_<?= $perm ?>">
                                    <label class="form-check-label" for="perm_<?= $perm ?>"><?= $label ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAdminModalLabel">Modifier l'Administrateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="manage_admins.php" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_admin_id">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Nom Complet</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Nouveau Mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Rôle</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="viewer">Viewer</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div id="edit_permissions" class="permissions-group">
                            <?php foreach ($available_permissions as $perm => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $perm ?>" id="edit_perm_<?= $perm ?>">
                                    <label class="form-check-label" for="edit_perm_<?= $perm ?>"><?= $label ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Admin Modal -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-labelledby="deleteAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAdminModalLabel">Supprimer l'Administrateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cet administrateur ? Cette action est irréversible.</p>
                <form action="manage_admins.php" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_admin_id">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Edit Admin Modal
    const editAdminModal = document.getElementById('editAdminModal');
    editAdminModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const username = button.getAttribute('data-username');
        const fullName = button.getAttribute('data-full_name');
        const email = button.getAttribute('data-email');
        const role = button.getAttribute('data-role');
        const permissions = JSON.parse(button.getAttribute('data-permissions'));

        const modal = this;
        modal.querySelector('#edit_admin_id').value = id;
        modal.querySelector('#edit_username').value = username;
        modal.querySelector('#edit_full_name').value = fullName;
        modal.querySelector('#edit_email').value = email;
        modal.querySelector('#edit_role').value = role;

        // Reset all checkboxes
        modal.querySelectorAll('#edit_permissions input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.checked = false;
        });
        
        // Check permissions
        if (permissions) {
            permissions.forEach(function(permission) {
                const checkbox = modal.querySelector('#edit_perm_' + permission);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }
    });

    // Delete Admin Modal
    const deleteAdminModal = document.getElementById('deleteAdminModal');
    deleteAdminModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        this.querySelector('#delete_admin_id').value = id;
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('adminsTableBody');
    const rows = tableBody.getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        for (let i = 0; i < rows.length; i++) {
            let cells = rows[i].getElementsByTagName('td');
            let found = false;
            for (let j = 0; j < cells.length; j++) {
                if (cells[j]) {
                    if (cells[j].innerHTML.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            if (found) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    });
});
</script>
</body>
</html>
<?php
include_once '../includes/footer.php';
?> 