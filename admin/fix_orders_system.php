<?php
require_once '../config/database.php';

echo "<h2>Diagnostic du Système de Commandes</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h3>1. Structure de la table orders</h3>";
    $result = $conn->query("DESCRIBE orders");
    echo "<table border='1'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>2. Structure de la table order_items</h3>";
    $result = $conn->query("DESCRIBE order_items");
    echo "<table border='1'>";
    echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>3. Commandes existantes</h3>";
    $result = $conn->query("SELECT COUNT(*) as total FROM orders");
    $count = $result->fetch(PDO::FETCH_ASSOC);
    echo "<p>Nombre total de commandes : " . $count['total'] . "</p>";
    
    if ($count['total'] > 0) {
        $result = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>N° Commande</th><th>Client</th><th>Affilié</th><th>Montant</th><th>Statut</th><th>Date</th></tr>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . ($row['order_number'] ?? 'N/A') . "</td>";
            echo "<td>" . ($row['customer_name'] ?? $row['client_name'] ?? 'N/A') . "</td>";
            echo "<td>" . $row['affiliate_id'] . "</td>";
            echo "<td>" . ($row['total_amount'] ?? 'N/A') . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>4. Vérification des affiliés</h3>";
    $result = $conn->query("SELECT id, username, email, type FROM users WHERE type = 'affiliate'");
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Type</th></tr>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['type'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>5. Correction de la structure</h3>";
    
    // Vérifier si la table orders a les bons champs
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'customer_name'");
    if ($result->rowCount() == 0) {
        echo "<p>❌ Le champ 'customer_name' n'existe pas. Ajout en cours...</p>";
        $conn->exec("ALTER TABLE orders ADD COLUMN customer_name VARCHAR(255) AFTER affiliate_id");
        echo "<p>✅ Champ 'customer_name' ajouté.</p>";
    } else {
        echo "<p>✅ Le champ 'customer_name' existe.</p>";
    }
    
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'customer_email'");
    if ($result->rowCount() == 0) {
        echo "<p>❌ Le champ 'customer_email' n'existe pas. Ajout en cours...</p>";
        $conn->exec("ALTER TABLE orders ADD COLUMN customer_email VARCHAR(255) AFTER customer_name");
        echo "<p>✅ Champ 'customer_email' ajouté.</p>";
    } else {
        echo "<p>✅ Le champ 'customer_email' existe.</p>";
    }
    
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'customer_phone'");
    if ($result->rowCount() == 0) {
        echo "<p>❌ Le champ 'customer_phone' n'existe pas. Ajout en cours...</p>";
        $conn->exec("ALTER TABLE orders ADD COLUMN customer_phone VARCHAR(20) AFTER customer_email");
        echo "<p>✅ Champ 'customer_phone' ajouté.</p>";
    } else {
        echo "<p>✅ Le champ 'customer_phone' existe.</p>";
    }
    
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'customer_address'");
    if ($result->rowCount() == 0) {
        echo "<p>❌ Le champ 'customer_address' n'existe pas. Ajout en cours...</p>";
        $conn->exec("ALTER TABLE orders ADD COLUMN customer_address TEXT AFTER customer_phone");
        echo "<p>✅ Champ 'customer_address' ajouté.</p>";
    } else {
        echo "<p>✅ Le champ 'customer_address' existe.</p>";
    }
    
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'customer_city'");
    if ($result->rowCount() == 0) {
        echo "<p>❌ Le champ 'customer_city' n'existe pas. Ajout en cours...</p>";
        $conn->exec("ALTER TABLE orders ADD COLUMN customer_city VARCHAR(100) AFTER customer_address");
        echo "<p>✅ Champ 'customer_city' ajouté.</p>";
    } else {
        echo "<p>✅ Le champ 'customer_city' existe.</p>";
    }
    
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'order_number'");
    if ($result->rowCount() == 0) {
        echo "<p>❌ Le champ 'order_number' n'existe pas. Ajout en cours...</p>";
        $conn->exec("ALTER TABLE orders ADD COLUMN order_number VARCHAR(50) UNIQUE AFTER customer_city");
        echo "<p>✅ Champ 'order_number' ajouté.</p>";
    } else {
        echo "<p>✅ Le champ 'order_number' existe.</p>";
    }
    
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'commission_amount'");
    if ($result->rowCount() == 0) {
        echo "<p>❌ Le champ 'commission_amount' n'existe pas. Ajout en cours...</p>";
        $conn->exec("ALTER TABLE orders ADD COLUMN commission_amount DECIMAL(10,2) AFTER total_amount");
        echo "<p>✅ Champ 'commission_amount' ajouté.</p>";
    } else {
        echo "<p>✅ Le champ 'commission_amount' existe.</p>";
    }
    
    $result = $conn->query("SHOW COLUMNS FROM orders LIKE 'shipping_cost'");
    if ($result->rowCount() == 0) {
        echo "<p>❌ Le champ 'shipping_cost' n'existe pas. Ajout en cours...</p>";
        $conn->exec("ALTER TABLE orders ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0.00 AFTER commission_amount");
        echo "<p>✅ Champ 'shipping_cost' ajouté.</p>";
    } else {
        echo "<p>✅ Le champ 'shipping_cost' existe.</p>";
    }
    
    echo "<h3>6. Test de création d'une commande</h3>";
    
    // Créer une commande de test
    $order_number = 'TEST-' . date('Ymd') . '-' . rand(1000, 9999);
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id, affiliate_id, customer_name, customer_email, customer_phone, 
            customer_address, customer_city, order_number, total_amount, 
            commission_amount, shipping_cost, status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', ?)
    ");
    
    $test_result = $stmt->execute([
        1, // user_id
        1, // affiliate_id
        'Test Client',
        'test@example.com',
        '0600000000',
        '123 Rue Test',
        'Casablanca',
        $order_number,
        1000.00,
        100.00,
        50.00,
        'Commande de test'
    ]);
    
    if ($test_result) {
        echo "<p>✅ Commande de test créée avec succès (N°: $order_number)</p>";
        
        // Supprimer la commande de test
        $conn->exec("DELETE FROM orders WHERE order_number = '$order_number'");
        echo "<p>✅ Commande de test supprimée</p>";
    } else {
        echo "<p>❌ Erreur lors de la création de la commande de test</p>";
    }
    
    echo "<h3>7. Recommandations</h3>";
    echo "<ul>";
    echo "<li>✅ Vérifiez que les affiliés existent dans la table users</li>";
    echo "<li>✅ Assurez-vous que les produits ont un stock suffisant</li>";
    echo "<li>✅ Vérifiez les permissions d'écriture dans la base de données</li>";
    echo "<li>✅ Testez la création d'une commande depuis l'interface client</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style> 