<?php
require_once 'config/database.php';

echo "<h1>Création des Tables Confirmateur</h1>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // 1. Créer la table equipe si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS equipe (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        password VARCHAR(255) NOT NULL,
        rib VARCHAR(50) DEFAULT NULL,
        telephone VARCHAR(30) DEFAULT NULL,
        adresse VARCHAR(255) DEFAULT NULL,
        role ENUM('membre', 'confirmateur') NOT NULL,
        date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "✅ Table 'equipe' créée ou déjà existante.<br>";
    
    // 2. Créer la table confirmateur_clients
    $sql = "CREATE TABLE IF NOT EXISTS confirmateur_clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        confirmateur_id INT NOT NULL,
        client_id INT NOT NULL,
        date_assignment DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'inactive') DEFAULT 'active',
        FOREIGN KEY (confirmateur_id) REFERENCES equipe(id) ON DELETE CASCADE,
        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_assignment (confirmateur_id, client_id)
    )";
    $conn->exec($sql);
    echo "✅ Table 'confirmateur_clients' créée ou déjà existante.<br>";
    
    // 3. Créer la table confirmateur_paiements
    $sql = "CREATE TABLE IF NOT EXISTS confirmateur_paiements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        confirmateur_id INT NOT NULL,
        montant DECIMAL(10,2) NOT NULL,
        statut ENUM('en_attente', 'paye') NOT NULL DEFAULT 'en_attente',
        date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (confirmateur_id) REFERENCES equipe(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "✅ Table 'confirmateur_paiements' créée ou déjà existante.<br>";
    
    // 4. Créer plusieurs confirmateurs de test
    $confirmateurs_test = [
        ["Confirmateur Alpha", "alpha.confirmateur@test.com", "alpha123", "111111111111111111111111", "0600000001", "1 Rue Alpha, Ville Test"],
        ["Confirmateur Beta", "beta.confirmateur@test.com", "beta123", "222222222222222222222222", "0600000002", "2 Rue Beta, Ville Test"],
        ["Confirmateur Gamma", "gamma.confirmateur@test.com", "gamma123", "333333333333333333333333", "0600000003", "3 Rue Gamma, Ville Test"],
        ["Confirmateur Delta", "delta.confirmateur@test.com", "delta123", "444444444444444444444444", "0600000004", "4 Rue Delta, Ville Test"]
    ];
    foreach ($confirmateurs_test as $c) {
        list($nom, $email, $password, $rib, $telephone, $adresse) = $c;
        $stmt = $conn->prepare("SELECT COUNT(*) FROM equipe WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() == 0) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO equipe (nom, email, password, rib, telephone, adresse, role) VALUES (?, ?, ?, ?, ?, ?, 'confirmateur')");
            $stmt->execute([$nom, $email, $hashed_password, $rib, $telephone, $adresse]);
            echo "✅ Confirmateur de test créé:<br>- Email: $email<br>- Mot de passe: $password<br>";
        } else {
            echo "✅ Confirmateur de test existe déjà: $email<br>";
        }
    }
    
    // 5. Vérifier les tables
    echo "<h3>Vérification des tables:</h3>";
    $tables = ['equipe', 'confirmateur_clients', 'confirmateur_paiements'];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✅ Table '$table' existe avec $count enregistrement(s)<br>";
        } else {
            echo "❌ Table '$table' manquante<br>";
        }
    }
    
    echo "<br><strong>🎉 Configuration terminée !</strong><br>";
    echo "Vous pouvez maintenant tester la connexion confirmateur avec:<br>";
    echo "Email: confirmateur@test.com<br>";
    echo "Mot de passe: test123<br>";
    echo "<br><a href='login.php'>Aller à la page de connexion</a>";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?> 