<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Récupération des administrateurs
    $stmt = $conn->query("SELECT id, username, email, full_name, role, status, last_login, created_at FROM admins ORDER BY id");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Liste des Administrateurs</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th>";
    echo "<th>Nom d'utilisateur</th>";
    echo "<th>Email</th>";
    echo "<th>Nom complet</th>";
    echo "<th>Rôle</th>";
    echo "<th>Statut</th>";
    echo "<th>Dernière connexion</th>";
    echo "<th>Date de création</th>";
    echo "</tr>";
    
    foreach ($admins as $admin) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($admin['id']) . "</td>";
        echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
        echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
        echo "<td>" . htmlspecialchars($admin['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($admin['role']) . "</td>";
        echo "<td>" . htmlspecialchars($admin['status']) . "</td>";
        echo "<td>" . ($admin['last_login'] ? htmlspecialchars($admin['last_login']) : 'Jamais') . "</td>";
        echo "<td>" . htmlspecialchars($admin['created_at']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 