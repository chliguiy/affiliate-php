<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';

// Récupérer les informations de l'utilisateur
$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiements - CHIC AFFILIATE</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2196F3;
            --secondary-color: #64B5F6;
            --accent-color: #1976D2;
            --background-color: #F5F5F5;
            --card-background: rgba(255, 255, 255, 0.9);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--background-color) 0%, #FFFFFF 100%);
            min-height: 100vh;
        }

        .main-content {
            margin-left: 280px;
            padding: 0 1rem 0;
        }

        .glass-effect {
            background: var(--card-background);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }
    </style>
</head>
<body>
<?php include 'includes/topbar.php'; ?>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">Gestion des Paiements</h2>
            <!-- Contenu de la page des paiements ici -->
            <?php
            // Récupérer les paiements enrichis de l'affilié connecté
            $stmt = $conn->prepare("
                SELECT ap.id, ap.montant, ap.date_paiement, ap.statut, u.username AS store, bi.bank_name,
                    (SELECT COUNT(*) FROM orders o WHERE o.affiliate_id = ap.affiliate_id AND o.commission_paid_at = ap.date_paiement) AS colis
                FROM affiliate_payments ap
                JOIN users u ON ap.affiliate_id = u.id
                LEFT JOIN bank_info bi ON bi.user_id = u.id
                WHERE ap.affiliate_id = ?
                ORDER BY ap.date_paiement DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $payments = $stmt->fetchAll();
            ?>
            <div class="card glass-effect mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Les paiements</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th></th>
                                    <th>Montant</th>
                                    <th>Colis</th>
                                    <th>Date Paiement</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">Aucun paiement reçu pour le moment.</td></tr>
                                <?php else: foreach ($payments as $p): ?>
                                    <tr>
                                        <td><?php echo $p['id']; ?></td>
                                        <td>
                                            <?php if ($p['bank_name'] === 'CIH Bank'): ?>
                                                <img src="assets/images/cihbank.png" alt="CIH BANK" style="height:24px;">
                                            <?php elseif ($p['bank_name'] === 'Attijariwafa Bank'): ?>
                                                <img src="assets/images/attijariwafa.png" alt="Attijariwafa Bank" style="height:24px;">
                                            <?php elseif ($p['bank_name'] === 'BMCI'): ?>
                                                <img src="assets/images/bmci.png" alt="BMCI" style="height:24px;">
                                            <?php else: ?>
                                                <span>-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo number_format($p['montant'], 2); ?> MAD</td>
                                        <td><?php echo $p['colis']; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($p['date_paiement'])); ?></td>
                                        <td>
                                            <span class="badge bg-success">Réglé</span>
                                            <span class="badge bg-info"><?php echo date('Y-m-d', strtotime($p['date_paiement'])); ?></span>
                                            <span class="badge bg-info">Virement_bancaire</span>
                                        </td>
                                        <td>
                                            <a href="print_payment.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn btn-outline-primary btn-sm" title="Imprimer"><i class="fas fa-print"></i></a>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" title="Voir détails" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $p['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Modale détails -->
                                    <div class="modal fade" id="detailsModal<?php echo $p['id']; ?>" tabindex="-1" aria-labelledby="detailsModalLabel<?php echo $p['id']; ?>" aria-hidden="true">
                                      <div class="modal-dialog">
                                        <div class="modal-content">
                                          <div class="modal-header">
                                            <h5 class="modal-title" id="detailsModalLabel<?php echo $p['id']; ?>">Détails du paiement</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                          </div>
                                          <div class="modal-body">
                                            <p><strong>Montant :</strong> <?php echo number_format($p['montant'], 2); ?> MAD</p>
                                            <p><strong>Date :</strong> <?php echo date('Y-m-d', strtotime($p['date_paiement'])); ?></p>
                                            <p><strong>Banque :</strong> <?php echo htmlspecialchars($p['bank_name']); ?></p>
                                            <p><strong>Colis :</strong> <?php echo $p['colis']; ?></p>
                                            <!-- Ajoute ici d'autres infos si besoin -->
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 