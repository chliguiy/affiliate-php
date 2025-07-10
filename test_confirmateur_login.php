<?php
require_once 'config/database.php';

echo "<h1>Test de Connexion Confirmateur</h1>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // 1. Vérifier si la table equipe existe
    $stmt = $conn->query("SHOW TABLES LIKE 'equipe'");
    if ($stmt->rowCount() == 0) {
        echo "❌ La table 'equipe' n'existe pas.<br>";
        echo "Création de la table...<br>";
        
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
        echo "✅ Table 'equipe' créée.<br>";
    } else {
        echo "✅ La table 'equipe' existe.<br>";
    }
    
    // 2. Vérifier s'il y a des confirmateurs
    $stmt = $conn->query("SELECT COUNT(*) FROM equipe WHERE role = 'confirmateur'");
    $count = $stmt->fetchColumn();
    echo "📊 Nombre de confirmateurs: $count<br>";
    
    // 3. Créer un confirmateur de test s'il n'y en a pas
    if ($count == 0) {
        echo "Création d'un confirmateur de test...<br>";
        
        $nom = "Confirmateur Test";
        $email = "confirmateur@test.com";
        $password = "test123";
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $rib = "123456789012345678901234";
        $telephone = "0612345678";
        $adresse = "123 Rue Test, Ville Test";
        
        $stmt = $conn->prepare("INSERT INTO equipe (nom, email, password, rib, telephone, adresse, role) VALUES (?, ?, ?, ?, ?, ?, 'confirmateur')");
        $stmt->execute([$nom, $email, $hashed_password, $rib, $telephone, $adresse]);
        
        echo "✅ Confirmateur de test créé:<br>";
        echo "- Email: $email<br>";
        echo "- Mot de passe: $password<br>";
    }
    
    // 4. Lister tous les confirmateurs
    echo "<h3>Liste des confirmateurs:</h3>";
    $stmt = $conn->query("SELECT id, nom, email, role FROM equipe WHERE role = 'confirmateur'");
    $confirmateurs = $stmt->fetchAll();
    
    if (count($confirmateurs) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Role</th></tr>";
        foreach ($confirmateurs as $confirmateur) {
            echo "<tr>";
            echo "<td>{$confirmateur['id']}</td>";
            echo "<td>{$confirmateur['nom']}</td>";
            echo "<td>{$confirmateur['email']}</td>";
            echo "<td>{$confirmateur['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Aucun confirmateur trouvé.<br>";
    }
    
    // 5. Test de connexion simulé avec le premier confirmateur
    echo "<h3>Test de connexion simulé:</h3>";
    
    if (count($confirmateurs) > 0) {
        // Prendre le premier confirmateur pour le test
        $test_confirmateur = $confirmateurs[0];
        $test_email = $test_confirmateur['email'];
        
        // Essayer différents mots de passe courants
        $passwords_to_try = [
            '123456',
            'password',
            'test123',
            'admin123',
            '123123',
            'hamza123',
            'driss123',
            'adnane123',
            'othmane123',
            'chraibi123'
        ];
        
        echo "Test avec l'email: <strong>$test_email</strong><br>";
        echo "Nom: <strong>{$test_confirmateur['nom']}</strong><br><br>";
        
        // Vérifier dans la table equipe
        $stmt = $conn->prepare("SELECT * FROM equipe WHERE email = ? AND role = 'confirmateur'");
        $stmt->execute([$test_email]);
        $confirmateur = $stmt->fetch();
        
        if ($confirmateur) {
            echo "✅ Confirmateur trouvé: {$confirmateur['nom']}<br>";
            echo "🔑 Mot de passe hashé: " . substr($confirmateur['password'], 0, 20) . "...<br><br>";
            
            $password_found = false;
            foreach ($passwords_to_try as $test_password) {
                if (password_verify($test_password, $confirmateur['password'])) {
                    echo "✅ <strong>Mot de passe trouvé: $test_password</strong><br>";
                    echo "✅ Connexion confirmateur réussie !<br>";
                    echo "<br><strong>Informations de connexion:</strong><br>";
                    echo "Email: $test_email<br>";
                    echo "Mot de passe: $test_password<br>";
                    echo "<br><a href='login.php'>Aller à la page de connexion</a>";
                    $password_found = true;
                    break;
                }
            }
            
            if (!$password_found) {
                echo "❌ Aucun des mots de passe testés ne correspond.<br>";
                echo "Les mots de passe testés étaient: " . implode(', ', $passwords_to_try) . "<br>";
                echo "<br><strong>Pour tester la connexion:</strong><br>";
                echo "1. Allez sur la page de connexion<br>";
                echo "2. Utilisez l'email: $test_email<br>";
                echo "3. Essayez de deviner le mot de passe ou demandez à l'administrateur<br>";
            }
        } else {
            echo "❌ Confirmateur non trouvé<br>";
        }
    } else {
        echo "Aucun confirmateur disponible pour le test.<br>";
    }
    
    // 6. Vérifier les tables liées
    echo "<h3>Vérification des tables liées:</h3>";
    
    // Table confirmateur_clients
    $stmt = $conn->query("SHOW TABLES LIKE 'confirmateur_clients'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'confirmateur_clients' existe<br>";
    } else {
        echo "❌ Table 'confirmateur_clients' manquante<br>";
    }
    
    // Table confirmateur_paiements
    $stmt = $conn->query("SHOW TABLES LIKE 'confirmateur_paiements'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'confirmateur_paiements' existe<br>";
    } else {
        echo "❌ Table 'confirmateur_paiements' manquante<br>";
    }
    
    // 7. Test de la logique de connexion
    echo "<h3>Test de la logique de connexion:</h3>";
    if (count($confirmateurs) > 0) {
        $test_confirmateur = $confirmateurs[0];
        $test_email = $test_confirmateur['email'];
        
        // Simuler la logique de login.php
        echo "Simulation de la logique de connexion avec: $test_email<br>";
        
        // 1. Vérifier d'abord dans users
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$test_email]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "❌ L'email existe dans la table users (conflit possible)<br>";
        } else {
            echo "✅ L'email n'existe pas dans la table users<br>";
        }
        
        // 2. Vérifier dans equipe
        $stmt = $conn->prepare("SELECT * FROM equipe WHERE email = ? AND role = 'confirmateur'");
        $stmt->execute([$test_email]);
        $confirmateur = $stmt->fetch();
        
        if ($confirmateur) {
            echo "✅ L'email existe dans la table equipe (confirmateur)<br>";
            echo "✅ La logique de connexion devrait fonctionner<br>";
        } else {
            echo "❌ L'email n'existe pas dans la table equipe<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?> 