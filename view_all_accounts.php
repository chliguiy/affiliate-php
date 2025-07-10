<?php
require_once 'config/database.php';

// Connexion à la base de données
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Erreur de connexion à la base de données");
}

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Tous les Comptes - CHIC AFFILIATE</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>";
echo "<style>";
echo ".account-card { margin-bottom: 20px; }";
echo ".password-field { font-family: monospace; }";
echo ".admin-account { border-left: 4px solid #dc3545; }";
echo ".client-account { border-left: 4px solid #0d6efd; }";
echo ".affiliate-account { border-left: 4px solid #198754; }";
echo "</style>";
echo "</head>";
echo "<body class='bg-light'>";

echo "<div class='container-fluid py-4'>";
echo "<div class='row'>";
echo "<div class='col-12'>";
echo "<h1 class='text-center mb-4'>";
echo "<i class='fas fa-users'></i> Tous les Comptes - CHIC AFFILIATE";
echo "</h1>";

try {
    // Récupérer tous les administrateurs
    echo "<h2 class='text-danger mb-3'><i class='fas fa-user-shield'></i> Comptes Administrateurs</h2>";
    
    $adminQuery = "SELECT id, username, email, password, full_name, role, status, created_at FROM admins ORDER BY created_at DESC";
    $adminStmt = $conn->query($adminQuery);
    $admins = $adminStmt->fetchAll();
    
    if (empty($admins)) {
        echo "<div class='alert alert-warning'>Aucun compte administrateur trouvé.</div>";
    } else {
        echo "<div class='row'>";
        foreach ($admins as $admin) {
            echo "<div class='col-md-6 col-lg-4'>";
            echo "<div class='card account-card admin-account'>";
            echo "<div class='card-header bg-danger text-white'>";
            echo "<h5 class='mb-0'><i class='fas fa-user-shield'></i> " . htmlspecialchars($admin['full_name']) . "</h5>";
            echo "</div>";
            echo "<div class='card-body'>";
            echo "<p><strong>ID:</strong> " . $admin['id'] . "</p>";
            echo "<p><strong>Nom d'utilisateur:</strong> " . htmlspecialchars($admin['username']) . "</p>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</p>";
            echo "<p><strong>Mot de passe (hashé):</strong> <span class='password-field'>" . htmlspecialchars($admin['password']) . "</span></p>";
            echo "<p><strong>Rôle:</strong> <span class='badge bg-danger'>" . htmlspecialchars($admin['role']) . "</span></p>";
            echo "<p><strong>Statut:</strong> <span class='badge bg-" . ($admin['status'] === 'active' ? 'success' : 'warning') . "'>" . htmlspecialchars($admin['status']) . "</span></p>";
            echo "<p><strong>Date de création:</strong> " . date('d/m/Y H:i', strtotime($admin['created_at'])) . "</p>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    // Récupérer tous les utilisateurs (clients/affiliés)
    echo "<h2 class='text-primary mb-3 mt-5'><i class='fas fa-users'></i> Comptes Clients/Affiliés</h2>";
    
    $userQuery = "SELECT id, username, email, password, full_name, type, status, phone, address, city, created_at FROM users ORDER BY created_at DESC";
    $userStmt = $conn->query($userQuery);
    $users = $userStmt->fetchAll();
    
    if (empty($users)) {
        echo "<div class='alert alert-warning'>Aucun compte utilisateur trouvé.</div>";
    } else {
        echo "<div class='row'>";
        foreach ($users as $user) {
            $cardClass = $user['type'] === 'admin' ? 'admin-account' : ($user['type'] === 'affiliate' ? 'affiliate-account' : 'client-account');
            $badgeClass = $user['type'] === 'admin' ? 'danger' : ($user['type'] === 'affiliate' ? 'success' : 'primary');
            
            echo "<div class='col-md-6 col-lg-4'>";
            echo "<div class='card account-card " . $cardClass . "'>";
            echo "<div class='card-header bg-" . $badgeClass . " text-white'>";
            echo "<h5 class='mb-0'><i class='fas fa-user'></i> " . htmlspecialchars($user['full_name']) . "</h5>";
            echo "</div>";
            echo "<div class='card-body'>";
            echo "<p><strong>ID:</strong> " . $user['id'] . "</p>";
            echo "<p><strong>Nom d'utilisateur:</strong> " . htmlspecialchars($user['username']) . "</p>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
            echo "<p><strong>Mot de passe (hashé):</strong> <span class='password-field'>" . htmlspecialchars($user['password']) . "</span></p>";
            echo "<p><strong>Type:</strong> <span class='badge bg-" . $badgeClass . "'>" . htmlspecialchars($user['type']) . "</span></p>";
            echo "<p><strong>Statut:</strong> <span class='badge bg-" . ($user['status'] === 'active' ? 'success' : ($user['status'] === 'pending' ? 'warning' : 'danger')) . "'>" . htmlspecialchars($user['status']) . "</span></p>";
            if (!empty($user['phone'])) {
                echo "<p><strong>Téléphone:</strong> " . htmlspecialchars($user['phone']) . "</p>";
            }
            if (!empty($user['city'])) {
                echo "<p><strong>Ville:</strong> " . htmlspecialchars($user['city']) . "</p>";
            }
            echo "<p><strong>Date de création:</strong> " . date('d/m/Y H:i', strtotime($user['created_at'])) . "</p>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    // Statistiques
    echo "<div class='row mt-5'>";
    echo "<div class='col-12'>";
    echo "<div class='card'>";
    echo "<div class='card-header bg-info text-white'>";
    echo "<h4 class='mb-0'><i class='fas fa-chart-bar'></i> Statistiques des Comptes</h4>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    // Compter les administrateurs
    $adminCount = $conn->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    echo "<p><strong>Nombre d'administrateurs:</strong> " . $adminCount . "</p>";
    
    // Compter les utilisateurs par type
    $userStats = $conn->query("SELECT type, COUNT(*) as count FROM users GROUP BY type")->fetchAll();
    foreach ($userStats as $stat) {
        echo "<p><strong>Nombre de " . htmlspecialchars($stat['type']) . "s:</strong> " . $stat['count'] . "</p>";
    }
    
    // Compter les utilisateurs par statut
    $statusStats = $conn->query("SELECT status, COUNT(*) as count FROM users GROUP BY status")->fetchAll();
    foreach ($statusStats as $stat) {
        echo "<p><strong>Utilisateurs " . htmlspecialchars($stat['status']) . "s:</strong> " . $stat['count'] . "</p>";
    }
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>Erreur de base de données:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='container-fluid mt-4'>";
echo "<div class='row'>";
echo "<div class='col-12 text-center'>";
echo "<a href='index.php' class='btn btn-primary'><i class='fas fa-home'></i> Retour à l'accueil</a>";
echo "<a href='admin/' class='btn btn-secondary ms-2'><i class='fas fa-cog'></i> Administration</a>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";
echo "</body>";
echo "</html>";
?> 