<?php
require_once 'config/database.php';

echo "<h1>Assignation de Clients aux Confirmateurs</h1>";

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
    
    // Récupérer tous les clients (utilisateurs de type customer)
    $stmt = $conn->query("SELECT id, email, full_name FROM users WHERE type = 'customer' LIMIT 10");
    $clients = $stmt->fetchAll();
    
    if (count($clients) == 0) {
        echo "Aucun client trouvé. Création de clients de test...<br>";
        
        // Créer quelques clients de test
        $clients_test = [
            ['email' => 'client1@test.com', 'full_name' => 'Client Test 1'],
            ['email' => 'client2@test.com', 'full_name' => 'Client Test 2'],
            ['email' => 'client3@test.com', 'full_name' => 'Client Test 3'],
            ['email' => 'client4@test.com', 'full_name' => 'Client Test 4'],
            ['email' => 'client5@test.com', 'full_name' => 'Client Test 5']
        ];
        
        foreach ($clients_test as $client) {
            $hashed_password = password_hash('client123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, full_name, password, type, status) VALUES (?, ?, ?, 'customer', 'active')");
            $stmt->execute([$client['email'], $client['full_name'], $hashed_password]);
        }
        
        echo "✅ 5 clients de test créés.<br>";
        
        // Récupérer les clients créés
        $stmt = $conn->query("SELECT id, email, full_name FROM users WHERE type = 'customer' LIMIT 10");
        $clients = $stmt->fetchAll();
    }
    
    echo "<h3>Assignation des clients aux confirmateurs:</h3>";
    
    // Assigner les clients aux confirmateurs de manière équitable
    $assignments = [];
    foreach ($clients as $index => $client) {
        $confirmateur_index = $index % count($confirmateurs);
        $confirmateur = $confirmateurs[$confirmateur_index];
        
        // Vérifier si l'assignation existe déjà
        $stmt = $conn->prepare("SELECT COUNT(*) FROM confirmateur_clients WHERE confirmateur_id = ? AND client_id = ?");
        $stmt->execute([$confirmateur['id'], $client['id']]);
        
        if ($stmt->fetchColumn() == 0) {
            // Créer l'assignation
            $stmt = $conn->prepare("INSERT INTO confirmateur_clients (confirmateur_id, client_id) VALUES (?, ?)");
            $stmt->execute([$confirmateur['id'], $client['id']]);
            
            $assignments[] = [
                'confirmateur' => $confirmateur['nom'],
                'client' => $client['full_name'] . ' (' . $client['email'] . ')'
            ];
        }
    }
    
    if (count($assignments) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Confirmateur</th><th>Client Assigné</th></tr>";
        foreach ($assignments as $assignment) {
            echo "<tr>";
            echo "<td>{$assignment['confirmateur']}</td>";
            echo "<td>{$assignment['client']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<br><strong>✅ " . count($assignments) . " assignations créées avec succès !</strong><br>";
    } else {
        echo "Toutes les assignations existent déjà.<br>";
    }
    
    // Créer quelques commandes de test pour les clients
    echo "<h3>Création de commandes de test:</h3>";
    
    // Vérifier si la table orders existe
    $stmt = $conn->query("SHOW TABLES LIKE 'orders'");
    if ($stmt->rowCount() > 0) {
        // Créer quelques commandes de test
        $statuses = ['pending', 'processing', 'shipped', 'delivered'];
        $amounts = [150.00, 250.00, 350.00, 450.00, 550.00];
        
        foreach ($clients as $client) {
            // Créer 1-3 commandes par client
            $num_orders = rand(1, 3);
            for ($i = 0; $i < $num_orders; $i++) {
                $status = $statuses[array_rand($statuses)];
                $amount = $amounts[array_rand($amounts)];
                $commission = $amount * 0.1; // 10% de commission
                
                $stmt = $conn->prepare("INSERT INTO orders (user_id, affiliate_id, customer_name, customer_email, customer_phone, customer_address, customer_city, order_number, total_amount, commission_amount, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $client['id'], // user_id
                    $client['id'], // affiliate_id (même que user_id pour simplifier)
                    $client['full_name'],
                    $client['email'],
                    '0612345678',
                    '123 Rue Test',
                    'Ville Test',
                    'ORD' . time() . rand(100, 999),
                    $amount,
                    $commission,
                    $status
                ]);
            }
        }
        
        echo "✅ Commandes de test créées pour les clients.<br>";
    } else {
        echo "❌ La table 'orders' n'existe pas.<br>";
    }
    
    echo "<br><strong>🎉 Configuration terminée !</strong><br>";
    echo "Les confirmateurs ont maintenant des clients assignés et des données de test.<br>";
    echo "<br><a href='login.php'>Aller à la page de connexion</a>";
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?> 