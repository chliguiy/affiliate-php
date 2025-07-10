<?php
/**
 * Script simple pour exécuter la base de données CHIC AFFILIATE
 */

echo "<h1>🚀 Exécution de la Base de Données CHIC AFFILIATE</h1>";

// Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "chic_affiliate";

try {
    echo "<h2>1. Connexion à MySQL</h2>";
    
    // Connexion à MySQL avec options pour éviter les problèmes de requêtes non bufférées
    $pdo = new PDO("mysql:host=$host", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    echo "✅ Connexion à MySQL réussie<br>";
    
    echo "<h2>2. Création de la base de données</h2>";
    
    // Création de la base de données
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de données '$database' créée<br>";
    
    // Sélection de la base de données
    $pdo->exec("USE $database");
    echo "✅ Base de données sélectionnée<br>";
    
    echo "<h2>3. Exécution du fichier SQL</h2>";
    
    // Lecture du fichier SQL
    $sqlFile = 'complete_database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Le fichier $sqlFile n'existe pas");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✅ Fichier SQL lu (" . strlen($sql) . " caractères)<br>";
    
    // Nettoyer le SQL et le diviser en requêtes
    $sql = preg_replace('/--.*$/m', '', $sql); // Supprimer les commentaires --
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Supprimer les commentaires /* */
    
    // Diviser en requêtes individuelles
    $queries = [];
    $currentQuery = '';
    $inString = false;
    $delimiter = ';';
    
    $lines = explode("\n", $sql);
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Ignorer les lignes vides
        if (empty($line)) {
            continue;
        }
        
        // Vérifier si c'est un changement de délimiteur
        if (preg_match('/^DELIMITER\s+(.+)$/i', $line, $matches)) {
            if (!empty($currentQuery)) {
                $queries[] = trim($currentQuery);
                $currentQuery = '';
            }
            $delimiter = $matches[1];
            continue;
        }
        
        $currentQuery .= $line . "\n";
        
        // Vérifier si la requête se termine par le délimiteur
        if (substr($line, -strlen($delimiter)) === $delimiter) {
            $currentQuery = substr($currentQuery, 0, -strlen($delimiter));
            if (!empty(trim($currentQuery))) {
                $queries[] = trim($currentQuery);
            }
            $currentQuery = '';
        }
    }
    
    // Ajouter la dernière requête si elle existe
    if (!empty(trim($currentQuery))) {
        $queries[] = trim($currentQuery);
    }
    
    echo "📝 " . count($queries) . " requêtes préparées<br>";
    
    // Exécution des requêtes
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($queries as $index => $query) {
        $query = trim($query);
        
        // Ignorer les requêtes vides
        if (empty($query)) {
            continue;
        }
        
        try {
            // Exécuter la requête
            $result = $pdo->exec($query);
            $successCount++;
            
            // Afficher le progrès pour les requêtes importantes
            if (preg_match('/CREATE TABLE (\w+)/i', $query, $matches)) {
                echo "✅ Table '{$matches[1]}' créée<br>";
            } elseif (preg_match('/INSERT INTO (\w+)/i', $query, $matches)) {
                echo "✅ Données insérées dans '{$matches[1]}'<br>";
            } elseif (preg_match('/CREATE VIEW (\w+)/i', $query, $matches)) {
                echo "✅ Vue '{$matches[1]}' créée<br>";
            } elseif (preg_match('/CREATE TRIGGER (\w+)/i', $query, $matches)) {
                echo "✅ Trigger '{$matches[1]}' créé<br>";
            } elseif (preg_match('/CREATE PROCEDURE (\w+)/i', $query, $matches)) {
                echo "✅ Procédure '{$matches[1]}' créée<br>";
            }
            
        } catch (PDOException $e) {
            $errorCount++;
            // Afficher seulement les erreurs importantes
            if (preg_match('/CREATE TABLE|INSERT INTO|CREATE VIEW|CREATE TRIGGER|CREATE PROCEDURE/i', $query)) {
                echo "⚠️ Erreur dans la requête #" . ($index + 1) . ": " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<br>📊 Résumé: $successCount requêtes réussies, $errorCount erreurs<br>";
    
    echo "<h2>4. Vérification de l'installation</h2>";
    
    // Vérifier les tables principales
    $tables = ['admins', 'users', 'categories', 'products', 'orders'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
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
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
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
    
    echo "<h2>🎉 Installation terminée !</h2>";
    
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>✅ Base de données installée avec succès !</h3>";
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
    echo "<li>Vérifiez les paramètres de connexion</li>";
    echo "<li>Assurez-vous que l'utilisateur MySQL a les droits suffisants</li>";
    echo "<li>Vérifiez que le fichier complete_database.sql existe</li>";
    echo "<li>Essayez de redémarrer le serveur MySQL</li>";
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