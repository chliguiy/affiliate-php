<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once 'includes/auth.php';
require_once 'includes/AdminPermissions.php';

// Connexion à la base de données
$database = new Database();
$conn = $database->getConnection();

$permissions = new AdminPermissions($conn, $_SESSION['admin_id']);

if (!$permissions->canManageUsers()) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_status':
                $id = $_POST['user_id'];
                // Récupérer le statut actuel
                $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $current_status = $stmt->fetchColumn();
                // Basculer le statut
                $new_status = ($current_status === 'active') ? 'inactive' : 'active';
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $id]);
                break;
            
            case 'edit_user':
                if (isset($_POST['user_id'], $_POST['email'])) {
                    $id = $_POST['user_id'];
                    $email = $_POST['email'];

                    if (!empty($_POST['password'])) {
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
                        $stmt->execute([$email, $password, $id]);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                        $stmt->execute([$email, $id]);
                    }
                }
                break;
            
            case 'delete':
                $id = $_POST['user_id'];
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                break;
        }
        header('Location: users.php');
        exit;
    }
}

// Filtres
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Récupération des utilisateurs
$sql = "SELECT id, username, full_name, email, address, bank_name, rib, type, status, created_at FROM users";
$conditions = [];
$params = [];

if (!empty($filter_type)) {
    $conditions[] = "type = ?";
    $params[] = $filter_type;
}
if (!empty($filter_status)) {
    $conditions[] = "status = ?";
    $params[] = $filter_status;
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Récupération des confirmateurs (table equipe)
$confirmateurs = [];
try {
    $stmtConf = $conn->prepare("SELECT id, nom AS username, email, 'confirmateur' AS type, 'active' AS status, NOW() AS created_at FROM equipe WHERE role = 'confirmateur'");
    $stmtConf->execute();
    $confirmateurs = $stmtConf->fetchAll();
} catch (Exception $e) {
    // ignorer si la table n'existe pas
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Gestion des Utilisateurs</h2>
                </div>

                <!-- Filtres -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Type d'utilisateur</label>
                                <select name="type" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="affiliate" <?php if ($filter_type === 'affiliate') echo 'selected'; ?>>Affilié</option>
                                    <option value="admin" <?php if ($filter_type === 'admin') echo 'selected'; ?>>Admin</option>
                                    <option value="customer" <?php if ($filter_type === 'customer') echo 'selected'; ?>>Client</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="active" <?php if ($filter_status === 'active') echo 'selected'; ?>>Actif</option>
                                    <option value="inactive" <?php if ($filter_status === 'inactive') echo 'selected'; ?>>Inactif</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">Filtrer</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des utilisateurs -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Date d'inscription</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo isset($user['type']) && $user['type'] === 'admin' ? 'danger' : ( ($user['type'] === 'affiliate') ? 'info' : ( ($user['type'] === 'customer') ? 'primary' : 'secondary' ) ); ?>">
                                                <?php echo isset($user['type']) ? $user['type'] : 'N/A'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($user['status']) {
                                                    'active' => 'success',
                                                    'pending' => 'warning',
                                                    'rejected' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo $user['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-<?php echo ($user['status'] === 'active') ? 'success' : 'secondary'; ?>" 
                                                        title="<?php echo ($user['status'] === 'active') ? 'Désactiver' : 'Activer'; ?>">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </form>
                                            
                                            <button type="button" class="btn btn-sm btn-warning edit-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUserModal"
                                                    data-id="<?php echo $user['id']; ?>"
                                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                    title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-info" title="Voir détails" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#userModal"
                                                    data-user-details='<?php echo json_encode($user); ?>'>
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if (!isset($user['type']) || $user['type'] !== 'admin'): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <!-- Affichage des confirmateurs -->
                                    <?php foreach ($confirmateurs as $conf): ?>
                                    <tr>
                                        <td><?php echo $conf['id']; ?></td>
                                        <td><?php echo htmlspecialchars($conf['username']); ?></td>
                                        <td><?php echo htmlspecialchars($conf['email']); ?></td>
                                        <td><span class="badge bg-secondary">confirmateur</span></td>
                                        <td><span class="badge bg-success">active</span></td>
                                        <td><?php echo date('d/m/Y H:i'); ?></td>
                                        <td><!-- Pas d'actions pour confirmateur ici --></td>
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

    <!-- User Details Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetailsBody">
                    <!-- Les détails seront injectés ici par JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Modifier l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const userModal = document.getElementById('userModal');
        if(userModal) {
            userModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const userDetails = JSON.parse(button.getAttribute('data-user-details'));
                const modalBody = document.getElementById('userDetailsBody');

                modalBody.innerHTML = `
                    <p><strong>ID:</strong> ${userDetails.id}</p>
                    <p><strong>Nom d'utilisateur:</strong> ${userDetails.username || 'N/A'}</p>
                    <p><strong>Nom Complet:</strong> ${userDetails.full_name || 'N/A'}</p>
                    <p><strong>Email:</strong> ${userDetails.email || 'N/A'}</p>
                    <p><strong>Adresse:</strong> ${userDetails.address || 'N/A'}</p>
                    <p><strong>Nom de la banque:</strong> ${userDetails.bank_name || 'N/A'}</p>
                    <p><strong>RIB:</strong> ${userDetails.rib || 'N/A'}</p>
                    <p><strong>Type:</strong> ${userDetails.type || 'N/A'}</p>
                    <p><strong>Status:</strong> ${userDetails.status || 'N/A'}</p>
                    <p><strong>Date d'inscription:</strong> ${new Date(userDetails.created_at).toLocaleString('fr-FR')}</p>
                `;
            });
        }

        const editUserModal = document.getElementById('editUserModal');
        if (editUserModal) {
            editUserModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-id');
                const userEmail = button.getAttribute('data-email');

                const modal = this;
                modal.querySelector('#edit_user_id').value = userId;
                modal.querySelector('#edit_email').value = userEmail;
                modal.querySelector('#edit_password').value = ''; // Clear password field
            });
        }
    });
    </script>
</body>
</html> 