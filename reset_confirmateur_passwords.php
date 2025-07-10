<?php
require_once 'config/database.php';

echo "<h1>Réinitialisation des Mots de Passe Confirmateur</h1>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Récupérer tous les confirmateurs
    $stmt = $conn->query("SELECT id, nom, email FROM equipe WHERE role = 'confirmateur'");
    $confirmateurs = $stmt->fetchAll();
    
    if (count($confirmateurs) == 0) {
        echo "Aucun confirmateur trouvé.<br>";
        exit;
    }
    
    echo "<h3>Confirmateurs trouvés:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Nouveau Mot de Passe</th></tr>";
    
    foreach ($confirmateurs as $confirmateur) {
        // Créer un mot de passe simple basé sur le nom
        $nom_lower = strtolower($confirmateur['nom']);
        $nom_clean = preg_replace('/[^a-z]/', '', $nom_lower);
        $new_password = $nom_clean . "123";
        
        // Hasher le nouveau mot de passe
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Mettre à jour le mot de passe
        $stmt = $conn->prepare("UPDATE equipe SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $confirmateur['id']]);
        
        echo "<tr>";
        echo "<td>{$confirmateur['id']}</td>";
        echo "<td>{$confirmateur['nom']}</td>";
        echo "<td>{$confirmateur['email']}</td>";
        echo "<td><strong>$new_password</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><strong>✅ Mots de passe réinitialisés avec succès !</strong><br>";
    echo "<br><strong>Informations de connexion:</strong><br>";
    echo "Vous pouvez maintenant vous connecter avec les emails et mots de passe ci-dessus.<br>";
    echo "<br><a href='login.php'>Aller à la page de connexion</a>";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?> 