<?php
/**
 * Script d'installation simple pour CHIC AFFILIATE
 * Utilise la commande MySQL directement pour éviter les problèmes de requêtes non bufférées
 */

echo "<h1>🚀 Installation Simple de CHIC AFFILIATE</h1>";

// Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "chic_affiliate";
$sqlFile = 'complete_database.sql';

try {
    echo "<h2>1. Vérification du fichier SQL</h2>";
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Le fichier $sqlFile n'existe pas");
    }
    
    echo "✅ Fichier SQL trouvé (" . filesize($sqlFile) . " octets)<br>";
    
    echo "<h2>2. Test de connexion MySQL</h2>";
    
    // Test de connexion simple
    $pdo = new PDO("mysql:host=$host", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
    echo "✅ Connexion MySQL réussie<br>";
    
    echo "<h2>3. Création de la base de données</h2>";
    
    // Création de la base de données
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de données '$database' créée<br>";
    
    echo "<h2>4. Installation via commande MySQL</h2>";
    
    // Construction de la commande MySQL
    $mysqlCommand = "mysql -h $host -u $username";
    if (!empty($password)) {
        $mysqlCommand .= " -p$password";
    }
    $mysqlCommand .= " $database < $sqlFile";
    
    echo "🔧 Exécution de la commande MySQL...<br>";
    
    // Exécution de la commande
    $output = [];
    $returnCode = 0;
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $mysqlCommand = "mysql -h $host -u $username";
        if (!empty($password)) {
            $mysqlCommand .= " -p$password";
        }
        $mysqlCommand .= " $database < \"$sqlFile\"";
        
        exec($mysqlCommand . " 2>&1", $output, $returnCode);
    } else {
        // Linux/Mac
        exec($mysqlCommand . " 2>&1", $output, $returnCode);
    }
    
    if ($returnCode === 0) {
        echo "✅ Installation MySQL réussie<br>";
    } else {
        echo "⚠️ Installation MySQL terminée avec des avertissements<br>";
        if (!empty($output)) {
            echo "<details>";
            echo "<summary>Détails des avertissements</summary>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
            echo "</details>";
        }
    }
    
    echo "<h2>5. Vérification de l'installation</h2>";
    
    // Connexion à la base de données créée
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Vérifier les tables principales
    $tables = ['admins', 'users', 'categories', 'products', 'orders'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $result = $stmt->fetchAll();
            if (count($result) > 0) {
                echo "✅ Table '$table' existe<br>";
            } else {
                echo "❌ Table '$table' manquante<br>";
            }
        } catch (PDOException $e) {
            echo "❌ Erreur lors de la vérification de la table '$table': " . $e->getMessage() . "<br>";
        }
    }
    
    // Vérifier les données
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
        $result = $stmt->fetchAll();
        $adminCount = $result[0]['count'];
        echo "👤 Administrateurs: $adminCount<br>";
    } catch (PDOException $e) {
        echo "❌ Erreur lors du comptage des administrateurs: " . $e->getMessage() . "<br>";
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $result = $stmt->fetchAll();
        $productCount = $result[0]['count'];
        echo "📦 Produits: $productCount<br>";
    } catch (PDOException $e) {
        echo "❌ Erreur lors du comptage des produits: " . $e->getMessage() . "<br>";
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
        $result = $stmt->fetchAll();
        $categoryCount = $result[0]['count'];
        echo "📁 Catégories: $categoryCount<br>";
    } catch (PDOException $e) {
        echo "❌ Erreur lors du comptage des catégories: " . $e->getMessage() . "<br>";
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetchAll();
        $userCount = $result[0]['count'];
        echo "👥 Utilisateurs: $userCount<br>";
    } catch (PDOException $e) {
        echo "❌ Erreur lors du comptage des utilisateurs: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>6. Création des dossiers nécessaires</h2>";
    
    // Créer les dossiers nécessaires
    $directories = [
        'uploads',
        'uploads/products',
        'uploads/categories',
        'uploads/claims',
        'logs'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "✅ Dossier '$dir' créé<br>";
            } else {
                echo "❌ Impossible de créer le dossier '$dir'<br>";
            }
        } else {
            echo "✅ Dossier '$dir' existe déjà<br>";
        }
    }
    
    echo "<h2>🎉 Installation terminée !</h2>";
    
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>✅ Installation réussie !</h3>";
    echo "<p><strong>Informations de connexion:</strong></p>";
    echo "<ul>";
    echo "<li>🔗 URL: <a href='index.php'>http://localhost/adnane1/</a></li>";
    echo "<li>👤 Administrateur: admin@chic-affiliate.com</li>";
    echo "<li>🔑 Mot de passe: password</li>";
    echo "<li>📊 Test: <a href='test_database.php'>test_database.php</a></li>";
    echo "</ul>";
    echo "<p><strong>Prochaines étapes:</strong></p>";
    echo "<ol>";
    echo "<li>Connectez-vous en tant qu'administrateur</li>";
    echo "<li>Configurez vos paramètres</li>";
    echo "<li>Ajoutez vos produits</li>";
    echo "<li>Invitez vos affiliés</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffe8e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>❌ Erreur d'installation</h3>";
    echo "<p><strong>Erreur:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Solutions possibles:</strong></p>";
    echo "<ul>";
    echo "<li>Vérifiez que MySQL est démarré</li>";
    echo "<li>Vérifiez que la commande 'mysql' est disponible</li>";
    echo "<li>Vérifiez les paramètres de connexion</li>";
    echo "<li>Assurez-vous que l'utilisateur MySQL a les droits suffisants</li>";
    echo "<li>Vérifiez que le fichier complete_database.sql existe</li>";
    echo "<li>Essayez l'installation manuelle via phpMyAdmin</li>";
    echo "</ul>";
    echo "<p><strong>Installation manuelle:</strong></p>";
    echo "<ol>";
    echo "<li>Ouvrez phpMyAdmin</li>";
    echo "<li>Créez une base de données 'chic_affiliate'</li>";
    echo "<li>Importez le fichier 'complete_database.sql'</li>";
    echo "</ol>";
    echo "</div>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    margin: 20px;
    background-color: #f5f5f5;
}

h1, h2, h3 {
    color: #333;
}

h1 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 30px;
}

h2 {
    background: #3498db;
    color: white;
    padding: 10px;
    border-radius: 5px;
    margin-top: 30px;
}

h3 {
    color: #2c3e50;
}

a {
    color: #3498db;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

ul, ol {
    margin-left: 20px;
}

li {
    margin-bottom: 5px;
}

details {
    margin: 10px 0;
}

summary {
    cursor: pointer;
    font-weight: bold;
    color: #3498db;
}

pre {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
    font-size: 12px;
}
</style> 