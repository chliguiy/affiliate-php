<?php
require_once 'includes/auth.php';
require_once 'includes/AdminLogger.php';

// Vérifier les permissions
$permissions = new AdminPermissions($pdo, $_SESSION['admin_id']);
if (!$permissions->hasPermission('view_reports')) {
    header('Location: dashboard.php');
    exit;
}

// Période par défaut : 30 derniers jours
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
}

// Statistiques générales
$stats = [
    'total_sales' => $pdo->query("
        SELECT COALESCE(SUM(final_sale_price), 0) 
        FROM orders 
        WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    ")->fetchColumn(),
    
    'total_orders' => $pdo->query("
        SELECT COUNT(*) 
        FROM orders 
        WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    ")->fetchColumn(),
    
    'total_commission' => $pdo->query("
        SELECT COALESCE(SUM(commission_amount), 0) 
        FROM orders 
        WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    ")->fetchColumn(),
    
    'new_affiliates' => $pdo->query("
        SELECT COUNT(*) 
        FROM users 
        WHERE type = 'affiliate' 
        AND created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    ")->fetchColumn(),
    
    'new_customers' => $pdo->query("
        SELECT COUNT(*) 
        FROM users 
        WHERE type = 'customer' 
        AND created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    ")->fetchColumn()
];

// Ventes par jour
$daily_sales = $pdo->query("
    SELECT DATE(created_at) as date, 
           COUNT(*) as orders_count,
           SUM(final_sale_price) as total_amount
    FROM orders 
    WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetchAll();

// Top produits
$top_products = $pdo->query("
    SELECT p.name, 
           COUNT(oi.id) as order_count,
           SUM(oi.quantity) as total_quantity,
           SUM(oi.quantity * oi.price) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY p.id
    ORDER BY total_revenue DESC
    LIMIT 10
")->fetchAll();

// Top affiliés
$top_affiliates = $pdo->query("
    SELECT u.username, u.full_name,
           COUNT(o.id) as orders_count,
           SUM(o.commission_amount) as total_commission
    FROM orders o
    JOIN users u ON o.affiliate_id = u.id
    WHERE o.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'
    GROUP BY u.id
    ORDER BY total_commission DESC
    LIMIT 10
")->fetchAll();

// Fonction pour générer le CSV
function generateCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes
    fputcsv($output, array_keys($data[0]));
    
    // Données
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
}

// Export CSV si demandé
if (isset($_GET['export'])) {
    switch ($_GET['export']) {
        case 'sales':
            generateCSV($daily_sales, 'ventes_' . date('Y-m-d') . '.csv');
            exit;
        case 'products':
            generateCSV($top_products, 'produits_' . date('Y-m-d') . '.csv');
            exit;
        case 'affiliates':
            generateCSV($top_affiliates, 'affilies_' . date('Y-m-d') . '.csv');
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapports - Administration</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        .stat-card .value {
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .filters {
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filters form {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Rapports</h1>

        <div class="filters">
            <form method="GET">
                <div>
                    <label for="start_date">Date de début</label>
                    <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>">
                </div>
                <div>
                    <label for="end_date">Date de fin</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filtrer</button>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Ventes totales</h3>
                <div class="value"><?= number_format($stats['total_sales'], 2) ?> €</div>
            </div>
            <div class="stat-card">
                <h3>Commandes</h3>
                <div class="value"><?= $stats['total_orders'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Commissions</h3>
                <div class="value"><?= number_format($stats['total_commission'], 2) ?> €</div>
            </div>
            <div class="stat-card">
                <h3>Nouveaux affiliés</h3>
                <div class="value"><?= $stats['new_affiliates'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Nouveaux clients</h3>
                <div class="value"><?= $stats['new_customers'] ?></div>
            </div>
        </div>

        <div class="chart-container">
            <h2>Ventes quotidiennes</h2>
            <canvas id="salesChart"></canvas>
            <a href="?export=sales&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-success">Exporter CSV</a>
        </div>

        <div class="table-container">
            <h2>Top 10 des produits</h2>
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Commandes</th>
                        <th>Quantité</th>
                        <th>Revenus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= $product['order_count'] ?></td>
                            <td><?= $product['total_quantity'] ?></td>
                            <td><?= number_format($product['total_revenue'], 2) ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="?export=products&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-success">Exporter CSV</a>
        </div>

        <div class="table-container">
            <h2>Top 10 des affiliés</h2>
            <table>
                <thead>
                    <tr>
                        <th>Affilié</th>
                        <th>Commandes</th>
                        <th>Commissions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_affiliates as $affiliate): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($affiliate['full_name']) ?>
                                <br>
                                <small><?= htmlspecialchars($affiliate['username']) ?></small>
                            </td>
                            <td><?= $affiliate['orders_count'] ?></td>
                            <td><?= number_format($affiliate['total_commission'], 2) ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="?export=affiliates&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-success">Exporter CSV</a>
        </div>
    </div>

    <script>
    // Graphique des ventes
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($daily_sales, 'date')) ?>,
            datasets: [{
                label: 'Ventes (€)',
                data: <?= json_encode(array_column($daily_sales, 'total_amount')) ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>
</html> 