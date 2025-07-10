<?php
require_once 'config/database.php';

$email = "client1@example.com";
$password = "client123";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Vérifier si l'utilisateur existe
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Utilisateur trouvé dans la base de données\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Type: " . $user['type'] . "\n";
        echo "Status: " . $user['status'] . "\n";
        
        // Vérifier le mot de passe
        if (password_verify($password, $user['password'])) {
            echo "✅ Mot de passe correct\n";
        } else {
            echo "❌ Mot de passe incorrect\n";
            echo "Mot de passe haché dans la base : " . $user['password'] . "\n";
            echo "Nouveau hachage du mot de passe : " . password_hash($password, PASSWORD_BCRYPT) . "\n";
        }
    } else {
        echo "❌ Utilisateur non trouvé dans la base de données\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?> 