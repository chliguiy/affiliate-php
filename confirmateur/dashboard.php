<?php
session_start();
if (!isset($_SESSION['confirmateur_id'])) {
    header('Location: ../login.php');
    exit();
}
$confirmateur_id = $_SESSION['confirmateur_id'];

require_once '../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Infos du confirmateur
$stmt = $pdo->prepare('SELECT * FROM equipe WHERE id = ? AND role = "confirmateur"');
$stmt->execute([$confirmateur_id]);
$confirmateur = $stmt->fetch();
if (!$confirmateur) {
    die('Confirmateur introuvable.');
}

// Récupérer les emails des clients assignés
$stmt = $pdo->prepare("SELECT u.email, u.username, u.full_name FROM confirmateur_clients cc JOIN users u ON cc.client_id = u.id WHERE cc.confirmateur_id = ? AND cc.status = 'active'");
$stmt->execute([$confirmateur_id]);
$clients = $stmt->fetchAll();
$client_emails = array_column($clients, 'email');

// Récupérer les IDs des clients assignés
$client_ids = [];
foreach ($clients as $client) {
    if (isset($client['id'])) {
        $client_ids[] = $client['id'];
    }
}

// Dernières commandes
$dernieres_commandes = [];
if (count($client_emails) > 0) {
    $in = str_repeat('?,', count($client_emails) - 1) . '?';
    $sql = "SELECT * FROM orders WHERE customer_email IN ($in) ORDER BY created_at DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($client_emails);
    $dernieres_commandes = $stmt->fetchAll();
}

// Toutes les commandes des clients assignés (via user_id)
$toutes_commandes = [];
if (count($client_ids) > 0) {
    $in = str_repeat('?,', count($client_ids) - 1) . '?';
    $sql = "SELECT * FROM orders WHERE user_id IN ($in) ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($client_ids);
    $toutes_commandes = $stmt->fetchAll();
}

// Statistiques
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

// Récupérer les commandes détaillées pour chaque client assigné (comme dans clients_orders.php)
$client_orders = [];
foreach ($clients as $client) {
    if (!isset($client['id'])) continue;
    $stmt = $pdo->prepare("
        SELECT 
            o.*, 
            COUNT(oi.id) as total_items,
            GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as products
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$client['id']]);
    $client_orders[$client['id']] = $stmt->fetchAll();
}

// Correction automatique : récupérer toutes les commandes des clients assignés valides
$clients_by_id = [];
foreach ($clients as $client) {
    if (isset($client['id'])) {
        $clients_by_id[$client['id']] = $client;
    }
}

$all_orders = [];
if (count($clients_by_id) > 0) {
    $in = str_repeat('?,', count($clients_by_id) - 1) . '?';
    $sql = "SELECT o.*, u.full_name, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.user_id IN ($in) ORDER BY o.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_keys($clients_by_id));
    $all_orders = $stmt->fetchAll();
}

// Calculer le nombre de commandes 'new' (nouvelles/en attente) pour les clients assignés
$new_orders_count = 0;
if (count($client_ids) > 0) {
    $in = str_repeat('?,', count($client_ids) - 1) . '?';
    $sql = "SELECT COUNT(*) FROM orders WHERE user_id IN ($in) AND status = 'new'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($client_ids);
    $new_orders_count = (int)$stmt->fetchColumn();
}

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Confirmateur</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
    .topbar-affiliate {
        background: #fff !important;
        box-shadow: 0 2px 12px rgba(44,62,80,0.10) !important;
        border-radius: 0 !important;
        margin-bottom: 1.5rem;
    }
    .topbar-affiliate .icon-btn {
        background: none !important;
        border-radius: 50% !important;
        padding: 10px !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    .topbar-affiliate .icon-btn:hover {
        background: #f5f8ff !important;
    }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-end mb-3">
        <a href="clients_orders.php" class="btn btn-outline-primary">
            <i class="fas fa-users me-1"></i> Voir tous les clients & commandes
        </a>
    </div>
    <?php include '../includes/topbar.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Remplacer le nom et le rôle dans la topbar pour le confirmateur
      var profileName = document.querySelector('.topbar-affiliate .profile-name');
      var profileRole = document.querySelector('.topbar-affiliate .profile-role');
      if (profileName) profileName.textContent = <?php echo json_encode($confirmateur['nom'] ?? $confirmateur['full_name'] ?? 'Confirmateur'); ?>;
      if (profileRole) profileRole.textContent = 'confirmateur';
    });
    </script>
    <h2 class="mb-4">Bienvenue, <?php echo htmlspecialchars($confirmateur['nom']); ?> !</h2>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Commandes livrées</div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo $stats['livrees']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-header">À confirmer</div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo $stats['non_confirmees']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-header">Nouvelles commandes</div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo $new_orders_count; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Gains totaux</div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo $gain_total; ?> DH</h3>
                    <p class="mb-0">Payé : <?php echo $total_paye; ?> DH<br>Restant : <?php echo $non_paye; ?> DH</p>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header bg-info text-white">Clients assignés</div>
        <div class="card-body">
            <?php if (count($clients) === 0): ?>
                <p class="text-muted">Aucun client assigné.</p>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($clients as $c): ?>
                        <li class="list-group-item">
                            <?php echo htmlspecialchars($c['full_name'] ?? $c['username']); ?> (<?php echo htmlspecialchars($c['email']); ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html> 