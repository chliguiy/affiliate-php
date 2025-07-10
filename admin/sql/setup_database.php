<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Création de la table admins
$sql = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

try {
    $conn->exec($sql);
    echo "Table 'admins' créée avec succès.<br>";

    // Vérifier si l'administrateur par défaut existe déjà
    $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute(['admin@chicaffiliate.com']);
    
    if (!$stmt->fetch()) {
        // Créer l'administrateur par défaut
        $stmt = $conn->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([
            'Administrateur',
            'admin@chicaffiliate.com',
            password_hash('admin123', PASSWORD_DEFAULT)
        ]);
        echo "Compte administrateur par défaut créé avec succès.<br>";
    } else {
        echo "Le compte administrateur existe déjà.<br>";
    }

    echo "<br>Configuration terminée. Vous pouvez maintenant vous connecter à l'administration avec :<br>";
    echo "Email : admin@chicaffiliate.com<br>";
    echo "Mot de passe : admin123<br>";
    echo "<br><a href='../index.php'>Aller à la page de connexion</a>";

} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 