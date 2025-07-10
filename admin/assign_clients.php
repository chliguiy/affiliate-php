<?php
session_start();
require_once '../config/database.php';
require_once '../includes/system_integration.php';
require_once 'includes/auth.php';

// Vérification de l'authentification admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign_client':
                $confirmateur_id = (int)$_POST['confirmateur_id'];
                $client_id = (int)$_POST['client_id'];
                
                // Utiliser le système d'intégration pour assigner le client
                $result = assignClientToConfirmateur($client_id, $confirmateur_id);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = "Client assigné avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors de l'assignation : " . $result['error'];
                }
                break;
                
            case 'remove_assignment':
                $assignment_id = (int)$_POST['assignment_id'];
                $stmt = $conn->prepare('DELETE FROM confirmateur_clients WHERE id = ?');
                if ($stmt->execute([$assignment_id])) {
                    $_SESSION['success_message'] = "Assignation supprimée avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors de la suppression.";
                }
                break;
        }
        header('Location: assign_clients.php');
        exit;
    }
}

// Récupérer la liste des confirmateurs
$stmt = $conn->query('SELECT id, nom, email FROM equipe WHERE role = "confirmateur" ORDER BY nom');
$confirmateurs = $stmt->fetchAll();

// Récupérer la liste des clients (utilisateurs non-affiliés)
$stmt = $conn->query('SELECT id, username, email, phone FROM users WHERE type = "customer" ORDER BY username');
$clients = $stmt->fetchAll();

// Récupérer les assignations actuelles
$stmt = $conn->query('
    SELECT 
        cc.id as assignment_id,
        cc.confirmateur_id,
        cc.client_id,
        cc.date_assignment,
        e.nom as confirmateur_nom,
        e.email as confirmateur_email,
        u.username as client_nom,
        u.email as client_email,
        u.phone as client_phone
    FROM confirmateur_clients cc
    JOIN equipe e ON cc.confirmateur_id = e.id
    JOIN users u ON cc.client_id = u.id
    WHERE cc.status = "active"
    ORDER BY e.nom, u.username
');
$assignments = $stmt->fetchAll();

$page_title = "Assignation des Clients";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e3e9f7 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        .card {
            border-radius: 18px !important;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            border: none;
            transition: box-shadow 0.2s;
        }
        .card:hover {
            box-shadow: 0 6px 32px rgba(0,0,0,0.13);
        }
        .card-header {
            border-radius: 18px 18px 0 0 !important;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }
        .btn-primary {
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,123,255,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-primary:hover {
            box-shadow: 0 4px 16px rgba(0,123,255,0.15);
            filter: brightness(1.08);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user-plus me-2"></i>
                        <?php echo $page_title; ?>
                    </h1>
                </div>

                <!-- Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <div class="row">
                    <!-- Formulaire d'assignation -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-user-plus me-2"></i>Assigner un client
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <input type="hidden" name="action" value="assign_client">
                                    
                                    <div class="mb-3">
                                        <label for="confirmateur_id" class="form-label">Confirmateur</label>
                                        <select class="form-select" name="confirmateur_id" required>
                                            <option value="">Choisir un confirmateur</option>
                                            <?php foreach ($confirmateurs as $confirmateur): ?>
                                                <option value="<?php echo $confirmateur['id']; ?>">
                                                    <?php echo htmlspecialchars($confirmateur['nom']); ?> (<?php echo htmlspecialchars($confirmateur['email']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="client_id" class="form-label">Client</label>
                                        <select class="form-select" name="client_id" required>
                                            <option value="">Choisir un client</option>
                                            <?php foreach ($clients as $client): ?>
                                                <option value="<?php echo $client['id']; ?>">
                                                    <?php echo htmlspecialchars($client['username']); ?> (<?php echo htmlspecialchars($client['email']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-link me-2"></i>Assigner
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Liste des assignations -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-list me-2"></i>Assignations actuelles
                            </div>
                            <div class="card-body">
                                <?php if (empty($assignments)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-info-circle text-info" style="font-size: 3rem;"></i>
                                        <h4 class="mt-3">Aucune assignation</h4>
                                        <p class="text-muted">Aucun client n'est actuellement assigné à un confirmateur.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Confirmateur</th>
                                                    <th>Client</th>
                                                    <th>Contact</th>
                                                    <th>Date d'assignation</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assignments as $assignment): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($assignment['confirmateur_nom']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($assignment['confirmateur_email']); ?></small>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($assignment['client_nom']); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($assignment['client_email']); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($assignment['client_phone']); ?>
                                                        </td>
                                                        <td>
                                                            <?php echo date('d/m/Y H:i', strtotime($assignment['date_assignment'])); ?>
                                                        </td>
                                                        <td>
                                                            <form method="post" class="d-inline" onsubmit="return confirm('Retirer cette assignation ?');">
                                                                <input type="hidden" name="action" value="remove_assignment">
                                                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['assignment_id']; ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm">
                                                                    <i class="fas fa-trash me-1"></i>
                                                                    Retirer
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-users me-2"></i>
                                    Total confirmateurs
                                </h5>
                                <h3 class="card-text"><?php echo count($confirmateurs); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-user-friends me-2"></i>
                                    Total clients
                                </h5>
                                <h3 class="card-text"><?php echo count($clients); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-link me-2"></i>
                                    Assignations actives
                                </h5>
                                <h3 class="card-text"><?php echo count($assignments); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 