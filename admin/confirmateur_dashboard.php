<?php
// Dashboard avancé d'un confirmateur
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de confirmateur invalide.');
}
$confirmateur_id = (int)$_GET['id'];

require_once '../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Régler un paiement (statut -> paye)
if (isset($_POST['regler_paiement']) && isset($_POST['paiement_id'])) {
    $paiement_id = (int)$_POST['paiement_id'];
    $stmt = $pdo->prepare('UPDATE confirmateur_paiements SET statut = "paye" WHERE id = ? AND confirmateur_id = ?');
    $stmt->execute([$paiement_id, $confirmateur_id]);
    header('Location: confirmateur_dashboard.php?id=' . $confirmateur_id);
    exit;
}

// Infos du confirmateur
$stmt = $pdo->prepare('SELECT * FROM equipe WHERE id = ? AND role = "confirmateur"');
$stmt->execute([$confirmateur_id]);
$confirmateur = $stmt->fetch();
if (!$confirmateur) {
    die('Confirmateur introuvable.');
}

// Récupérer les emails des clients assignés à ce confirmateur
$stmt = $pdo->prepare("SELECT u.email FROM confirmateur_clients cc JOIN users u ON cc.client_id = u.id WHERE cc.confirmateur_id = ? AND cc.status = 'active'");
$stmt->execute([$confirmateur_id]);
$client_emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

$dernieres_commandes = [];
if (count($client_emails) > 0) {
    $in = str_repeat('?,', count($client_emails) - 1) . '?';
    $sql = "SELECT * FROM orders WHERE customer_email IN ($in) ORDER BY created_at DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($client_emails);
    $dernieres_commandes = $stmt->fetchAll();
}

// Filtrer les stats aussi
$stats = [
    'total' => 0,
    'livrees' => 0,
    'non_livrees' => 0,
    'confirmees' => 0,
    'non_confirmees' => 0
];
if (count($client_emails) > 0) {
    $in = str_repeat('?,', count($client_emails) - 1) . '?';
    $sql = "SELECT status, COUNT(*) as nb FROM orders WHERE customer_email IN ($in) GROUP BY status";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($client_emails);
    foreach ($stmt->fetchAll() as $row) {
        $stats['total'] += $row['nb'];
        if ($row['status'] === 'delivered') $stats['livrees'] = $row['nb'];
        elseif ($row['status'] === 'shipped') $stats['confirmees'] = $row['nb'];
        elseif ($row['status'] === 'pending') $stats['non_confirmees'] = $row['nb'];
        elseif ($row['status'] === 'processing') $stats['non_livrees'] = $row['nb'];
    }
}

// Gains
$gain_total = $stats['livrees'] * 8;
$stmt = $pdo->prepare("SELECT SUM(montant) FROM confirmateur_paiements WHERE confirmateur_id = ? AND statut = 'paye'");
$stmt->execute([$confirmateur_id]);
$total_paye = (float)$stmt->fetchColumn();
if (!$total_paye) $total_paye = 0;
$non_paye = $gain_total - $total_paye;
if ($non_paye < 0) $non_paye = 0;

// Historique paiements
$stmt = $pdo->prepare("SELECT * FROM confirmateur_paiements WHERE confirmateur_id = ? ORDER BY date_paiement DESC");
$stmt->execute([$confirmateur_id]);
$hist_paiements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Confirmateur - <?php echo htmlspecialchars($confirmateur['nom']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e3e9f7 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        .dashboard-header {
            background: linear-gradient(90deg, #007bff 0%, #28a745 100%);
            color: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 32px 32px 24px 32px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .dashboard-header i {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .card {
            border-radius: 18px !important;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            border: none;
        }
        .card-header {
            border-radius: 18px 18px 0 0 !important;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }
        .card-body {
            font-size: 1.05rem;
        }
        .stat-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 0 10px 0;
        }
        .stat-card i {
            font-size: 2rem;
            opacity: 0.7;
        }
        .progress {
            background: #e9ecef;
            border-radius: 16px;
            height: 32px;
        }
        .progress-bar {
            font-size: 1.2rem;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: width 0.6s;
        }
        .table {
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .table thead {
            background: #f1f5fa;
            font-weight: 600;
        }
        .table tbody tr:hover {
            background: #f6fafd;
        }
        .btn-primary, .btn-success, .btn-info {
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0,123,255,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .btn-primary:hover, .btn-success:hover, .btn-info:hover {
            box-shadow: 0 4px 16px rgba(0,123,255,0.15);
            filter: brightness(1.08);
        }
        .badge {
            font-size: 1rem;
            border-radius: 8px;
            padding: 0.5em 0.8em;
        }
        @media (max-width: 991px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 24px 12px 16px 12px;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <div class="col-md-9 col-lg-10 p-4">
            <div class="dashboard-header mb-4">
                <i class="fas fa-user-tie"></i>
                <div>
                    <h2 class="mb-1">Dashboard de <?php echo htmlspecialchars($confirmateur['nom']); ?></h2>
                    <div style="font-size:1.1rem;opacity:0.8;">Bienvenue sur votre espace confirmateur</div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">Informations personnelles</div>
                        <div class="card-body">
                            <p><strong>Nom :</strong> <?php echo htmlspecialchars($confirmateur['nom']); ?></p>
                            <p><strong>Email :</strong> <?php echo htmlspecialchars($confirmateur['email']); ?></p>
                            <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($confirmateur['telephone']); ?></p>
                            <p><strong>Adresse :</strong> <?php echo htmlspecialchars($confirmateur['adresse']); ?></p>
                            <p><strong>RIB :</strong> <?php echo htmlspecialchars($confirmateur['rib']); ?></p>
                            <p><strong>Date d'ajout :</strong> <?php echo htmlspecialchars($confirmateur['date_ajout']); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="card text-bg-success mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">Commandes livrées</h6>
                                    <p class="card-text fs-4"><?php echo $stats['livrees']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card text-bg-warning mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">Commandes non livrées</h6>
                                    <p class="card-text fs-4"><?php echo $stats['non_livrees']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card text-bg-primary mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">Commandes confirmées</h6>
                                    <p class="card-text fs-4"><?php echo $stats['confirmees']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card text-bg-danger mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">Commandes non confirmées</h6>
                                    <p class="card-text fs-4"><?php echo $stats['non_confirmees']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card text-bg-info mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">Montant total gagné</h6>
                                    <p class="card-text fs-4"><?php echo $gain_total; ?> DH</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card text-bg-secondary mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">Montant non payé</h6>
                                    <p class="card-text fs-5"><?php echo $non_paye; ?> DH</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card text-bg-success mb-2">
                                <div class="card-body">
                                    <h6 class="card-title">Montant total payé</h6>
                                    <p class="card-text fs-5"><?php echo $total_paye; ?> DH</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mt-4 mb-4">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-user-plus me-2"></i>Assigner un client à ce confirmateur
                </div>
                <div class="card-body">
                    <?php
                    // Traitement de l'assignation
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_client'])) {
                        $client_id = (int)$_POST['client_id'];
                        $stmt = $pdo->prepare('INSERT IGNORE INTO confirmateur_clients (confirmateur_id, client_id) VALUES (?, ?)');
                        if ($stmt->execute([$confirmateur_id, $client_id])) {
                            echo '<div class="alert alert-success mb-2">Client assigné avec succès.</div>';
                        } else {
                            echo '<div class="alert alert-danger mb-2">Erreur lors de l\'assignation.</div>';
                        }
                    }
                    // Dans la gestion du formulaire, ajouter la suppression d'un client assigné
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_assignment'])) {
                        $assignment_id = (int)$_POST['assignment_id'];
                        $stmt = $pdo->prepare('DELETE FROM confirmateur_clients WHERE confirmateur_id = ? AND client_id = ?');
                        if ($stmt->execute([$confirmateur_id, $assignment_id])) {
                            echo '<div class="alert alert-success mb-2">Assignation supprimée avec succès.</div>';
                        } else {
                            echo '<div class="alert alert-danger mb-2">Erreur lors de la suppression.</div>';
                        }
                    }
                    // Liste des clients non encore assignés à ce confirmateur
                    $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE type IN ("affiliate", "customer") AND id NOT IN (SELECT client_id FROM confirmateur_clients WHERE confirmateur_id = ?) ORDER BY username');
                    $stmt->execute([$confirmateur_id]);
                    $clients = $stmt->fetchAll();
                    ?>
                    <form method="post" class="row g-2 align-items-end">
                        <input type="hidden" name="assign_client" value="1">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label">Client à assigner</label>
                            <select class="form-select" name="client_id" required>
                                <option value="">Choisir un client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['username']); ?> (<?php echo htmlspecialchars($client['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-link me-2"></i>Assigner
                            </button>
                        </div>
                    </form>
                    <hr>
                    <h6>Clients déjà assignés :</h6>
                    <ul class="list-group">
                        <?php
                        $stmt = $pdo->prepare('SELECT u.id, u.username, u.email FROM confirmateur_clients cc JOIN users u ON cc.client_id = u.id WHERE cc.confirmateur_id = ? AND cc.status = "active"');
                        $stmt->execute([$confirmateur_id]);
                        $assigned = $stmt->fetchAll();
                        if (count($assigned) === 0) {
                            echo '<li class="list-group-item text-muted">Aucun client assigné.</li>';
                        } else {
                            foreach ($assigned as $a) {
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">'
                                    . htmlspecialchars($a['username']) . ' (' . htmlspecialchars($a['email']) . ')'
                                    . '<form method="post" style="display:inline; margin-left:10px;">
                                        <input type="hidden" name="remove_assignment" value="1">
                                        <input type="hidden" name="assignment_id" value="' . $a['id'] . '">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Retirer ce client ?\')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>'
                                    . '</li>';
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
            <!-- Progression commandes livrées sur confirmées -->
            <?php
                $progress = 0;
                if ($stats['confirmees'] > 0) {
                    $progress = round(($stats['livrees'] / $stats['confirmees']) * 100);
                }
            ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-gradient text-dark fw-bold" style="background: linear-gradient(90deg, #007bff 0%, #28a745 100%); color: #fff;">Progression des commandes livrées</div>
                        <div class="card-body">
                            <div class="progress" style="height: 32px; background: #e9ecef; border-radius: 16px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: <?php echo $progress; ?>%; font-size: 1.2rem; border-radius: 16px; display: flex; align-items: center; justify-content: center; transition: width 0.6s;">
                                    <?php echo $progress; ?>%
                                </div>
                            </div>
                            <div class="mt-2 text-end text-muted" style="font-size: 0.95rem;">
                                <i class="fas fa-info-circle"></i> <?php echo $stats['livrees']; ?> livrées sur <?php echo $stats['confirmees']; ?> confirmées
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header bg-info text-white">Dernières commandes traitées</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Date</th>
                                            <th>Client</th>
                                            <th>Statut</th>
                                            <th>Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($dernieres_commandes as $cmd): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cmd['id']); ?></td>
                                            <td><?php echo htmlspecialchars($cmd['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($cmd['customer_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($cmd['status']); ?></td>
                                            <td><?php echo htmlspecialchars($cmd['total_amount'] ?? ''); ?> DH</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($dernieres_commandes) === 0): ?>
                                        <tr><td colspan="5" class="text-center">Aucune commande trouvée.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header bg-success text-white">Historique des paiements</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Montant</th>
                                            <th>Statut</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($hist_paiements as $p): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($p['date_paiement']); ?></td>
                                            <td><?php echo htmlspecialchars($p['montant']); ?> DH</td>
                                            <td>
                                                <?php if ($p['statut'] === 'paye'): ?>
                                                    <span class="badge bg-success">Payé</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">En attente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($p['statut'] === 'en_attente'): ?>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="paiement_id" value="<?php echo $p['id']; ?>">
                                                        <button type="submit" name="regler_paiement" class="btn btn-sm btn-primary">Régler</button>
                                                    </form>
                                                <?php else: ?>
                                                    <i class="fas fa-check text-success"></i>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($hist_paiements) === 0): ?>
                                        <tr><td colspan="4" class="text-center">Aucun paiement trouvé.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 