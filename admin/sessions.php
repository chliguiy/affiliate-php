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

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'logout' && isset($_POST['session_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM admin_sessions WHERE session_id = ?");
            $stmt->execute([$_POST['session_id']]);
            
            $logger->log('logout_session', 'admin_session', null, [
                'session_id' => $_POST['session_id']
            ]);
            
            $success = "Session déconnectée avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de la déconnexion de la session.";
        }
    }
}

// Récupérer les sessions actives
$sessions = $pdo->query("
    SELECT s.*, a.username, a.full_name, a.role
    FROM admin_sessions s
    JOIN admins a ON s.admin_id = a.id
    ORDER BY s.last_activity DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Sessions Actives - Administration</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .sessions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .sessions-table th, .sessions-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .sessions-table th {
            background-color: #f5f5f5;
        }
        .sessions-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .inactive {
            color: #6c757d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sessions Actives</h1>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <table class="sessions-table">
            <thead>
                <tr>
                    <th>Administrateur</th>
                    <th>Rôle</th>
                    <th>IP</th>
                    <th>Navigateur</th>
                    <th>Dernière activité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($session['full_name']) ?>
                            <br>
                            <small><?= htmlspecialchars($session['username']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($session['role']) ?></td>
                        <td><?= htmlspecialchars($session['ip_address']) ?></td>
                        <td><?= htmlspecialchars($session['user_agent']) ?></td>
                        <td>
                            <?php
                            $last_activity = strtotime($session['last_activity']);
                            $now = time();
                            $diff = $now - $last_activity;
                            
                            if ($diff > 3600) { // Plus d'une heure
                                echo '<span class="inactive">';
                            }
                            
                            echo date('d/m/Y H:i:s', $last_activity);
                            
                            if ($diff > 3600) {
                                echo '</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($session['admin_id'] !== $_SESSION['admin_id']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="logout">
                                    <input type="hidden" name="session_id" value="<?= $session['session_id'] ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir déconnecter cette session ?')">
                                        Déconnecter
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="inactive">Session actuelle</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 