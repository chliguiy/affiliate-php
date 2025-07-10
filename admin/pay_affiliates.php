<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/system_integration.php';
require_once 'includes/auth.php';
require_once 'includes/AdminLogger.php';
require_once 'includes/AdminPermissions.php';

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();
$permissions = new AdminPermissions($conn, $_SESSION['admin_id']);
if (!$permissions->canManageUsers()) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}
$logger = new AdminLogger($conn, $_SESSION['admin_id']);

// Traitement du paiement d'un affilié spécifique
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_affiliate_id'])) {
    try {
        $affiliate_id = (int)$_POST['pay_affiliate_id'];
        // 1. Calculer le montant total à payer
        $stmt = $conn->prepare("SELECT SUM(commission_amount) as total, COUNT(id) as nb FROM orders WHERE affiliate_id = ? AND status = 'delivered' AND commission_paid = 0");
        $stmt->execute([$affiliate_id]);
        $row = $stmt->fetch();
        $amount = $row ? (float)$row['total'] : 0;
        $nb = $row ? (int)$row['nb'] : 0;
        if ($amount > 0 && $nb > 0) {
            // 2. Mettre à jour les commandes
            $stmt = $conn->prepare("UPDATE orders SET commission_paid = 1, commission_paid_at = NOW() WHERE affiliate_id = ? AND status = 'delivered' AND commission_paid = 0");
            $stmt->execute([$affiliate_id]);
            // 3. Insérer le paiement dans affiliate_payments
            $stmt = $conn->prepare("INSERT INTO affiliate_payments (affiliate_id, montant, date_paiement, statut) VALUES (?, ?, NOW(), 'payé')");
            $stmt->execute([$affiliate_id, $amount]);
            $_SESSION['success_message'] = "Paiement de " . number_format($amount, 2) . " MAD effectué avec succès pour $nb commande(s) !";
        } else {
            $_SESSION['info_message'] = "Aucune commission en attente de paiement pour cet affilié.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
    }
    header('Location: pay_affiliates.php');
    exit;
}

// Traitement du paiement en masse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_all_affiliates'])) {
    try {
        $admin_id = $_SESSION['admin_id'];
        
        // Récupérer tous les affiliés avec des commissions en attente
        $stmt = $conn->prepare("
            SELECT DISTINCT u.id, u.username, u.email
            FROM users u
            JOIN orders o ON u.id = o.affiliate_id
            WHERE u.type = 'affiliate' 
            AND o.status = 'delivered' 
            AND o.commission_paid = 0
        ");
        $stmt->execute();
        $affiliates_with_commissions = $stmt->fetchAll();
        
        $total_paid = 0;
        $success_count = 0;
        
        foreach ($affiliates_with_commissions as $affiliate) {
            $result = payAffiliateCommissions($affiliate['id'], $admin_id);
            
            if ($result['success']) {
                $total_paid += $result['amount_paid'];
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            $_SESSION['success_message'] = "Paiement en masse effectué : " . $success_count . " affiliés payés pour un total de " . number_format($total_paid, 2) . " MAD";
        } else {
            $_SESSION['info_message'] = "Aucune commission en attente de paiement.";
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur lors du paiement en masse : " . $e->getMessage();
    }
    
    header('Location: affiliates.php');
    exit;
}

// Gestion des actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'regler' && isset($_POST['payment_id'])) {
        $stmt = $conn->prepare("UPDATE affiliate_payments ap SET ap.statut = 'payé', ap.date_paiement = NOW() WHERE ap.id = ?");
        $stmt->execute([$_POST['payment_id']]);
        $_SESSION['success_message'] = "Paiement réglé avec succès.";
        header('Location: pay_affiliates.php');
        exit;
    }
    if ($_POST['action'] === 'supprimer' && isset($_POST['payment_id'])) {
        $stmt = $conn->prepare("DELETE FROM affiliate_payments ap WHERE ap.id = ?");
        $stmt->execute([$_POST['payment_id']]);
        $_SESSION['success_message'] = "Paiement supprimé.";
        header('Location: pay_affiliates.php');
        exit;
    }
    if ($_POST['action'] === 'supprimer_affilie' && isset($_POST['affiliate_id'])) {
        $stmt = $conn->prepare("DELETE FROM orders WHERE affiliate_id = ? AND status = 'delivered' AND commission_paid = 0");
        $stmt->execute([$_POST['affiliate_id']]);
        $_SESSION['success_message'] = "Toutes les commandes livrées non payées de l'affilié ont été supprimées.";
        header('Location: pay_affiliates.php');
        exit;
    }
}

// Gestion du filtre de statut
$filtre_statut = isset($_GET['filtre_statut']) ? $_GET['filtre_statut'] : 'tous';
$where = '';
$params = [];
if ($filtre_statut === 'payé') {
    $where = "WHERE ap.statut = 'payé'";
} elseif ($filtre_statut === 'en attente') {
    $where = "WHERE ap.statut = 'en attente'";
}

// Récupérer tous les paiements d'affiliés selon le filtre
$stmt = $conn->prepare("
    SELECT ap.id, u.username, ap.montant, ap.date_paiement, ap.statut
    FROM affiliate_payments ap
    JOIN users u ON ap.affiliate_id = u.id
    $where
    ORDER BY ap.date_paiement DESC, ap.id DESC
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Récupérer la liste des affiliés actifs pour le formulaire
$stmt = $conn->prepare("SELECT id, username FROM users WHERE type = 'affiliate' AND status = 'active' ORDER BY username ASC");
$stmt->execute();
$affiliates_list = $stmt->fetchAll();

// Affichage de la page de paiement des commissions
$page_title = "Paiement des Commissions";

// Afficher la liste de tous les affiliés avec leur statut de commission (en attente ou payé)
$stmt = $conn->prepare('
    SELECT u.id, u.username, u.email,
           SUM(CASE WHEN o.status = "delivered" THEN o.commission_amount ELSE 0 END) AS montant_total,
           SUM(CASE WHEN o.status = "delivered" AND o.commission_paid = 0 THEN o.commission_amount ELSE 0 END) AS montant_en_attente,
           SUM(CASE WHEN o.status = "delivered" AND o.commission_paid = 1 THEN o.commission_amount ELSE 0 END) AS montant_paye,
           COUNT(CASE WHEN o.status = "delivered" THEN o.id END) AS nb_commandes,
           MAX(o.updated_at) AS derniere_commande
    FROM users u
    LEFT JOIN orders o ON u.id = o.affiliate_id
    WHERE u.type = "affiliate" AND u.status = "active"
    GROUP BY u.id, u.username, u.email
    HAVING montant_total > 0
    ORDER BY derniere_commande DESC
');
$stmt->execute();
$affiliates_all = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                        <i class="fas fa-money-bill-wave me-2"></i>
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

                <?php if (isset($_SESSION['info_message'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['info_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['info_message']); ?>
                <?php endif; ?>

                <!-- Résumé -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-users me-2"></i>
                                    Affiliés avec commissions
                                </h5>
                                <h3 class="card-text"><?php echo count($affiliates_all); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-coins me-2"></i>
                                    Total en attente
                                </h5>
                                <h3 class="card-text"><?php echo number_format(array_sum(array_column($affiliates_all, 'montant_en_attente')), 2); ?> MAD</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-clock me-2"></i>
                                    Actions
                                </h5>
                                <form method="post" class="d-inline" onsubmit="return confirm('Payer toutes les commissions en attente ?');">
                                    <input type="hidden" name="pay_all_affiliates" value="1">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-money-bill-wave me-2"></i>
                                        Payer tout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtre de statut -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="get" class="row g-3 align-items-center">
                            <div class="col-auto">
                                <label for="filtre_statut" class="form-label mb-0">Filtrer par statut :</label>
                            </div>
                            <div class="col-auto">
                                <select name="filtre_statut" id="filtre_statut" class="form-select" onchange="this.form.submit()">
                                    <option value="tous" <?php if ($filtre_statut === 'tous') echo 'selected'; ?>>Tous</option>
                                    <option value="en attente" <?php if ($filtre_statut === 'en attente') echo 'selected'; ?>>En attente</option>
                                    <option value="payé" <?php if ($filtre_statut === 'payé') echo 'selected'; ?>>Payés</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste de tous les affiliés avec commissions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Affiliés avec commissions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom affilié</th>
                                        <th>Montant à payer</th>
                                        <th>Commandes</th>
                                        <th>Date dernière commande</th>
                                        <th>Statut</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($affiliates_all)): ?>
                                        <tr><td colspan="7" class="text-center">Aucun affilié trouvé.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($affiliates_all as $aff): ?>
                                            <tr>
                                                <td><?php echo $aff['id']; ?></td>
                                                <td><?php echo htmlspecialchars($aff['username']); ?></td>
                                                <td><strong><?php echo number_format($aff['montant_en_attente'], 2); ?> MAD</strong></td>
                                                <td><?php echo $aff['nb_commandes']; ?></td>
                                                <td><?php echo $aff['derniere_commande'] ? $aff['derniere_commande'] : '-'; ?></td>
                                                <td>
                                                    <?php if ($aff['montant_en_attente'] > 0): ?>
                                                        <span class="badge bg-warning text-dark">En attente</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Payé</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <!-- Voir détails -->
                                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $aff['id']; ?>" title="Voir détails">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <!-- Modifier -->
                                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $aff['id']; ?>" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <!-- Payer -->
                                                    <?php if ($aff['montant_en_attente'] > 0): ?>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Payer les commissions de <?php echo htmlspecialchars($aff['username']); ?> ?');">
                                                            <input type="hidden" name="pay_affiliate_id" value="<?php echo $aff['id']; ?>">
                                                            <button type="submit" class="btn btn-success btn-sm" title="Payer">
                                                                <i class="fas fa-money-bill-wave me-1"></i> Payer
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <!-- Modale Détails -->
                                                    <div class="modal fade" id="detailsModal<?php echo $aff['id']; ?>" tabindex="-1" aria-labelledby="detailsModalLabel<?php echo $aff['id']; ?>" aria-hidden="true">
                                                      <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                          <div class="modal-header">
                                                            <h5 class="modal-title" id="detailsModalLabel<?php echo $aff['id']; ?>">Détails des commandes à payer - <?php echo htmlspecialchars($aff['username']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                          </div>
                                                          <div class="modal-body">
                                                            <ul>
                                                            <?php
                                                            $stmt_details = $conn->prepare("SELECT id, commission_amount, created_at FROM orders WHERE affiliate_id = ? AND status = 'delivered' AND commission_paid = 0");
                                                            $stmt_details->execute([$aff['id']]);
                                                            $orders = $stmt_details->fetchAll();
                                                            foreach ($orders as $order) {
                                                                echo '<li>Commande #' . $order['id'] . ' - ' . number_format($order['commission_amount'], 2) . ' MAD - ' . $order['created_at'] . '</li>';
                                                            }
                                                            ?>
                                                            </ul>
                                                          </div>
                                                        </div>
                                                      </div>
                                                    </div>
                                                    <!-- Modale Modifier (structure, à personnaliser) -->
                                                    <div class="modal fade" id="editModal<?php echo $aff['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $aff['id']; ?>" aria-hidden="true">
                                                      <div class="modal-dialog">
                                                        <div class="modal-content">
                                                          <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel<?php echo $aff['id']; ?>">Modifier le paiement - <?php echo htmlspecialchars($aff['username']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                          </div>
                                                          <div class="modal-body">
                                                            <form>
                                                              <div class="mb-3">
                                                                <label for="montant_edit_<?php echo $aff['id']; ?>" class="form-label">Montant à payer</label>
                                                                <input type="number" class="form-control" id="montant_edit_<?php echo $aff['id']; ?>" value="<?php echo number_format($aff['montant_en_attente'], 2, '.', ''); ?>" readonly>
                                                              </div>
                                                              <!-- Ajoute ici d'autres champs à modifier si besoin -->
                                                              <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Enregistrer</button>
                                                            </form>
                                                          </div>
                                                        </div>
                                                      </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 