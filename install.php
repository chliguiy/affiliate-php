<?php
/**
 * Script d'installation automatique pour CHIC AFFILIATE
 * Ce script configure la base de données et l'application
 */

echo "<h1>🚀 Installation de CHIC AFFILIATE</h1>";

// Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "chic_affiliate";

try {
    echo "<h2>1. Connexion à MySQL</h2>";
    
    // Connexion à MySQL sans sélectionner de base de données
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion à MySQL réussie<br>";
    
    echo "<h2>2. Création de la base de données</h2>";
    
    // Création de la base de données
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de données '$database' créée<br>";
    
    // Sélection de la base de données
    $pdo->exec("USE $database");
    echo "✅ Base de données sélectionnée<br>";
    
    echo "<h2>3. Lecture du fichier SQL</h2>";
    
    // Lecture du fichier SQL
    $sqlFile = 'complete_database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Le fichier $sqlFile n'existe pas");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✅ Fichier SQL lu (" . strlen($sql) . " caractères)<br>";
    
    echo "<h2>4. Exécution des requêtes SQL</h2>";
    
    // Division du fichier SQL en requêtes individuelles
    $queries = explode(';', $sql);
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        
        // Ignorer les lignes vides et les commentaires
        if (empty($query) || strpos($query, '--') === 0 || strpos($query, '/*') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($query);
            $successCount++;
            
            // Afficher le progrès pour les requêtes importantes
            if (strpos($query, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE (\w+)/', $query, $matches);
                if (isset($matches[1])) {
                    echo "✅ Table '{$matches[1]}' créée<br>";
                }
            } elseif (strpos($query, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO (\w+)/', $query, $matches);
                if (isset($matches[1])) {
                    echo "✅ Données insérées dans '{$matches[1]}'<br>";
                }
            }
            
        } catch (PDOException $e) {
            $errorCount++;
            echo "❌ Erreur dans la requête: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br>📊 Résumé: $successCount requêtes réussies, $errorCount erreurs<br>";
    
    echo "<h2>5. Vérification de l'installation</h2>";
    
    // Vérifier les tables principales
    $tables = ['admins', 'users', 'categories', 'products', 'orders'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' existe<br>";
        } else {
            echo "❌ Table '$table' manquante<br>";
        }
    }
    
    // Vérifier les données
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
    $adminCount = $stmt->fetch()['count'];
    echo "👤 Administrateurs: $adminCount<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $productCount = $stmt->fetch()['count'];
    echo "📦 Produits: $productCount<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $categoryCount = $stmt->fetch()['count'];
    echo "📁 Catégories: $categoryCount<br>";
    
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
    
    echo "<h2>7. Configuration des permissions</h2>";
    
    // Vérifier les permissions d'écriture
    $writableDirs = ['uploads', 'logs'];
    foreach ($writableDirs as $dir) {
        if (is_writable($dir)) {
            echo "✅ Dossier '$dir' accessible en écriture<br>";
        } else {
            echo "⚠️ Dossier '$dir' non accessible en écriture (à corriger manuellement)<br>";
        }
    }
    
    echo "<h2>8. Test de connexion avec la classe Database</h2>";
    
    // Tester la classe Database
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "✅ Connexion via la classe Database réussie<br>";
    } else {
        echo "❌ Erreur de connexion via la classe Database<br>";
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
    
    // Créer un fichier de configuration d'installation
    $config = [
        'installed' => true,
        'installed_at' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'database' => $database,
        'admin_email' => 'admin@chic-affiliate.com'
    ];
    
    file_put_contents('install_config.json', json_encode($config, JSON_PRETTY_PRINT));
    echo "✅ Fichier de configuration d'installation créé<br>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffe8e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>❌ Erreur d'installation</h3>";
    echo "<p><strong>Erreur:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Solutions possibles:</strong></p>";
    echo "<ul>";
    echo "<li>Vérifiez que MySQL est démarré</li>";
    echo "<li>Vérifiez les paramètres de connexion</li>";
    echo "<li>Assurez-vous que l'utilisateur MySQL a les droits suffisants</li>";
    echo "<li>Vérifiez que le fichier complete_database.sql existe</li>";
    echo "</ul>";
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
</style> 