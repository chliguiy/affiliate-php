<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once 'includes/auth.php';
require_once 'includes/AdminLogger.php';
require_once 'includes/AdminPermissions.php';

$database = new Database();
$pdo = $database->getConnection();

$permissions = new AdminPermissions($pdo, $_SESSION['admin_id']);

if (!$permissions->canManageUsers()) {
    header('Location: dashboard.php?error=access_denied');
    exit;
}

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Connexion à la base de données
$host = 'localhost:8889';
$db   = 'chic_affiliate';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// Vérifier les permissions
// $permissions = new AdminPermissions($pdo, $_SESSION['admin_id']);
// if (!$permissions->hasPermission('canViewAffiliates')) {
//     header('Location: index.php');
//     exit;
// }

$logger = new AdminLogger($pdo, $_SESSION['admin_id']);

// Récupérer les statistiques globales des affiliés
try {
    // Statistiques globales
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_affiliates,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_affiliates,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_affiliates,
            SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_affiliates
        FROM users 
        WHERE type = 'affiliate'
    ")->fetch();

    // Classement des affiliés
    $affiliates = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.phone,
            u.city,
            u.status,
            u.created_at,
            COUNT(DISTINCT o.id) as total_orders,
            SUM(CASE WHEN o.status = 'delivered' THEN o.final_sale_price ELSE 0 END) as total_sales,
            SUM(CASE WHEN o.status = 'delivered' THEN o.commission_amount ELSE 0 END) as total_commission,
            COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as delivered_orders,
            COUNT(DISTINCT CASE WHEN o.status = 'cancelled' THEN o.id END) as cancelled_orders
        FROM users u
        LEFT JOIN orders o ON o.affiliate_id = u.id
        WHERE u.type = 'affiliate'
        GROUP BY u.id, u.username, u.email, u.phone, u.city, u.status, u.created_at
        ORDER BY total_sales DESC
    ")->fetchAll();

    // Meilleurs affiliés du mois
    $top_monthly = $pdo->query("
        SELECT 
            u.username,
            COUNT(DISTINCT o.id) as total_orders,
            SUM(CASE WHEN o.status = 'delivered' THEN o.final_sale_price ELSE 0 END) as total_sales,
            SUM(CASE WHEN o.status = 'delivered' THEN o.commission_amount ELSE 0 END) as total_commission
        FROM users u
        LEFT JOIN orders o ON o.affiliate_id = u.id
        WHERE u.type = 'affiliate'
        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY u.id, u.username
        ORDER BY total_sales DESC
        LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
    $error = "Une erreur est survenue lors de la récupération des données.";
    $top_monthly = [];
    $affiliates = [];
}

// Journaliser l'accès
$logger->log('view', 'affiliates', null, ['ip' => $_SERVER['REMOTE_ADDR']]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affiliés et leurs Commandes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Inter:400,600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', Arial, sans-serif; background: #f4f6fa; }
        .aff-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.07); margin-bottom: 32px; padding: 24px 32px; }
        .aff-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,0.12); }
        .table-affiliates thead { background: #f5f7fa; font-weight: bold; }
        .table-affiliates { border-radius: 12px; overflow: hidden; }
        .stat-value { font-size: 2.2rem; font-weight: 600; color: #27ae60; }
        .stat-label { color: #888; font-size: 1rem; }
        .badge-status { font-size: 0.95rem; padding: 6px 14px; border-radius: 12px; }
        .badge-status.active { background: #27ae60; color: #fff; }
        .badge-status.pending { background: #f1c40f; color: #fff; }
        .badge-status.suspended { background: #e74c3c; color: #fff; }
        .aff-header { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .aff-header .fa-user { font-size: 1.5rem; color: #3498db; }
        .aff-header .badge-status { margin-left: 8px; }
        .aff-totals { display: flex; gap: 32px; margin-bottom: 18px; }
        .aff-totals .stat-block { flex: 1; text-align: center; }
        .aff-totals .stat-label { margin-bottom: 2px; display: block; }
        .aff-totals .stat-value { margin-bottom: 0; }
        @media (max-width: 900px) {
            .aff-card { padding: 12px 6px; }
            .aff-totals { flex-direction: column; gap: 10px; }
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
                <h2 class="mb-4">Gestion des Affiliés</h2>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Affiliés</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><?php echo $stats['total_affiliates']; ?></h2>
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Affiliés Actifs</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><?php echo $stats['active_affiliates']; ?></h2>
                                    <i class="fas fa-user-check fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">En Attente</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><?php echo $stats['pending_affiliates']; ?></h2>
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Suspendus</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="mb-0"><?php echo $stats['suspended_affiliates']; ?></h2>
                                    <i class="fas fa-user-slash fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Affiliates -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Meilleurs Affiliés du Mois</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Rang</th>
                                                <th>Affilié</th>
                                                <th>Commandes</th>
                                                <th>Ventes</th>
                                                <th>Commissions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($top_monthly as $index => $affiliate): ?>
                                            <tr>
                                                <td>
                                                    <div class="rank-badge rank-<?php echo $index + 1; ?>">
                                                        <?php echo $index + 1; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($affiliate['username']); ?></td>
                                                <td><?php echo $affiliate['total_orders']; ?></td>
                                                <td><?php echo number_format((float)($affiliate['total_sales'] ?? 0), 2); ?> MAD</td>
                                                <td><?php echo number_format((float)($affiliate['total_commission'] ?? 0), 2); ?> MAD</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Classement général des affiliés (tous les temps) -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Classement Général des Affiliés</h5>
                                <div>
                                    <a href="export_affiliates.php?format=csv" class="btn btn-sm btn-outline-primary me-2"><i class="fas fa-file-csv"></i> Export CSV</a>
                                    <a href="export_affiliates.php?format=excel" class="btn btn-sm btn-outline-success me-2"><i class="fas fa-file-excel"></i> Export Excel</a>
                                    <a href="export_affiliates.php?format=pdf" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf"></i> Export PDF</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Rang</th>
                                                <th>Nom d'utilisateur</th>
                                                <th>Email</th>
                                                <th>Téléphone</th>
                                                <th>Ville</th>
                                                <th>Commandes</th>
                                                <th>Ventes</th>
                                                <th>Commissions</th>
                                                <th>Taux de livraison</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($affiliates as $index => $affiliate): ?>
                                            <tr>
                                                <td><span class="rank-badge rank-<?php echo ($index < 3) ? ($index+1) : ''; ?>"><?php echo $index+1; ?></span></td>
                                                <td><?php echo htmlspecialchars($affiliate['username']); ?></td>
                                                <td><?php echo htmlspecialchars($affiliate['email']); ?></td>
                                                <td><?php echo htmlspecialchars($affiliate['phone'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($affiliate['city'] ?? ''); ?></td>
                                                <td><?php echo $affiliate['total_orders']; ?></td>
                                                <td><?php echo number_format((float)($affiliate['total_sales'] ?? 0), 2); ?> MAD</td>
                                                <td><?php echo number_format((float)($affiliate['total_commission'] ?? 0), 2); ?> MAD</td>
                                                <td>
                                                    <?php
                                                    $total_orders = $affiliate['delivered_orders'] + $affiliate['cancelled_orders'];
                                                    echo $total_orders > 0 
                                                        ? round(($affiliate['delivered_orders'] / $total_orders) * 100, 1) . '%'
                                                        : '0%';
                                                    ?>
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

                <!-- All Affiliates -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Liste Complète des Affiliés</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom d'utilisateur</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>Ville</th>
                                        <th>Statut</th>
                                        <th>Date d'inscription</th>
                                        <th>Commandes</th>
                                        <th>Ventes</th>
                                        <th>Commissions</th>
                                        <th>Taux de livraison</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($affiliates as $affiliate): ?>
                                    <tr>
                                        <td><?php echo $affiliate['id']; ?></td>
                                        <td><?php echo htmlspecialchars($affiliate['username']); ?></td>
                                        <td><?php echo htmlspecialchars($affiliate['email']); ?></td>
                                        <td><?php echo htmlspecialchars($affiliate['phone'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($affiliate['city'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge-status status-<?php echo strtolower($affiliate['status']); ?>">
                                                <?php echo ucfirst($affiliate['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($affiliate['created_at'])); ?></td>
                                        <td><?php echo $affiliate['total_orders']; ?></td>
                                        <td><?php echo number_format((float)($affiliate['total_sales'] ?? 0), 2); ?> MAD</td>
                                        <td><?php echo number_format((float)($affiliate['total_commission'] ?? 0), 2); ?> MAD</td>
                                        <td>
                                            <?php
                                            $total_orders = $affiliate['delivered_orders'] + $affiliate['cancelled_orders'];
                                            echo $total_orders > 0 
                                                ? round(($affiliate['delivered_orders'] / $total_orders) * 100, 1) . '%'
                                                : '0%';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="affiliate_details.php?id=<?php echo $affiliate['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="affiliate_edit.php?id=<?php echo $affiliate['id']; ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($affiliate['status'] === 'active'): ?>
                                                <a href="affiliate_suspend.php?id=<?php echo $affiliate['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir suspendre cet affilié ?')">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                                <?php else: ?>
                                                <a href="affiliate_activate.php?id=<?php echo $affiliate['id']; ?>" 
                                                   class="btn btn-sm btn-success"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir activer cet affilié ?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="affiliate_delete.php?id=<?php echo $affiliate['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet affilié ? Cette action est irréversible.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 