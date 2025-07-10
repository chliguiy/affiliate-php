<?php
require_once 'includes/auth.php';
require_once 'includes/AdminLogger.php';

// Vérifier si l'admin a les permissions nécessaires
$permissions = new AdminPermissions($pdo, $_SESSION['admin_id']);
if (!$permissions->canManageAdmins()) {
    header('Location: dashboard.php');
    exit;
}

$logger = new AdminLogger($pdo, $_SESSION['admin_id']);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Récupérer les logs
$logs = $logger->getLogs($limit, $offset);

// Compter le nombre total de logs pour la pagination
$total_logs = $pdo->query("SELECT COUNT(*) FROM admin_logs")->fetchColumn();
$total_pages = ceil($total_logs / $limit);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Journal d'activité - Administration</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .logs-table th, .logs-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .logs-table th {
            background-color: #f5f5f5;
        }
        .logs-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            padding: 8px 16px;
            margin: 0 4px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
        }
        .pagination a.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .details-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Journal d'activité</h1>
        
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Administrateur</th>
                    <th>Action</th>
                    <th>Type</th>
                    <th>ID</th>
                    <th>Détails</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td><?= htmlspecialchars($log['full_name']) ?></td>
                        <td><?= htmlspecialchars($log['action']) ?></td>
                        <td><?= htmlspecialchars($log['entity_type']) ?></td>
                        <td><?= $log['entity_id'] ?: '-' ?></td>
                        <td class="details-cell" title="<?= htmlspecialchars($log['details']) ?>">
                            <?= htmlspecialchars($log['details']) ?>
                        </td>
                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 