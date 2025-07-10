<?php
require_once 'includes/auth.php';
require_once '../setup_database.php';

// Connexion à la base de données
$host = 'localhost';
$db   = 'chic_affiliate';
$user = 'root';
$pass = '';
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

// Récupérer la liste des produits
$stmt = $pdo->query("SELECT id, name, stock, min_stock_level, max_stock_level, reorder_point, status FROM products ORDER BY name");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion du Stock</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f5f5f5; }
        .low-stock { color: #c00; font-weight: bold; }
        .out-of-stock { color: #fff; background: #c00; font-weight: bold; }
        .actions a { margin: 0 5px; }
    </style>
</head>
<body>
    <h1>Gestion du Stock</h1>
    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Stock</th>
                <th>Min</th>
                <th>Max</th>
                <th>Point de réappro</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td class="<?= $p['stock'] <= $p['reorder_point'] ? 'low-stock' : '' ?><?= $p['status'] === 'out_of_stock' ? ' out-of-stock' : '' ?>">
                    <?= $p['stock'] ?>
                </td>
                <td><?= $p['min_stock_level'] ?></td>
                <td><?= $p['max_stock_level'] ?></td>
                <td><?= $p['reorder_point'] ?></td>
                <td><?= htmlspecialchars($p['status']) ?></td>
                <td class="actions">
                    <a href="stock_adjust.php?product_id=<?= $p['id'] ?>">Ajuster</a>
                    <a href="stock_history.php?product_id=<?= $p['id'] ?>">Historique</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html> 