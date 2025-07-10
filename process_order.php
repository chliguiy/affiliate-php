<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';
require_once 'includes/system_integration.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

    $database = new Database();
    $conn = $database->getConnection();

// Affichage de l'erreur si présente
if (isset($_SESSION['error'])) {
    echo '<div style="color:red;font-weight:bold;">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug: Afficher les données reçues
        error_log("Données POST reçues: " . print_r($_POST, true));
        
        // Récupérer les données du formulaire
        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_email = trim($_POST['customer_email'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $customer_address = trim($_POST['customer_address'] ?? '');
        $customer_city = trim($_POST['customer_city'] ?? '');
        $affiliate_id = $_SESSION['user_id'];
        $products = $_POST['products'] ?? [];
        $comment = trim($_POST['comment'] ?? '');
        
        // Debug: Afficher les données traitées
        error_log("Données traitées - Nom: $customer_name, Email: $customer_email, Affilié: $affiliate_id");
        error_log("Produits reçus: " . print_r($products, true));
        
        // Validation des données
        if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || empty($customer_address) || empty($customer_city)) {
            throw new Exception('Tous les champs sont obligatoires.');
        }
        
        if (empty($products)) {
            throw new Exception('Aucun produit sélectionné.');
        }
        
        // Préparer les données client
        $client_data = [
            'name' => $customer_name,
            'email' => $customer_email,
            'phone' => $customer_phone,
            'address' => $customer_address,
            'city' => $customer_city,
            'comment' => $comment
        ];
        
        // Préparer les données des produits
        $order_products = [];
        foreach ($products as $product_id => $quantity) {
            if ($quantity > 0) {
                // Récupérer les informations du produit avec gestion de commission_rate
                try {
                    $stmt = $conn->prepare("SELECT id, name, seller_price as price, commission_rate FROM products WHERE id = ? AND status = 'active'");
                    $stmt->execute([$product_id]);
                    $product = $stmt->fetch();
                } catch (Exception $e) {
                    // Si commission_rate n'existe pas, utiliser une requête alternative
                    error_log("Erreur avec commission_rate, utilisation de requête alternative: " . $e->getMessage());
                    $stmt = $conn->prepare("SELECT id, name, seller_price as price FROM products WHERE id = ? AND status = 'active'");
                    $stmt->execute([$product_id]);
                    $product = $stmt->fetch();
                    if ($product) {
                        $product['commission_rate'] = 10.00; // Valeur par défaut
                    }
                }
                
                if ($product) {
                    $order_products[] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'price' => (float)$product['price'],
                        'quantity' => $quantity,
                        'commission_rate' => isset($product['commission_rate']) ? (float)$product['commission_rate'] : 10.00
                    ];
                } else {
                    error_log("Produit non trouvé ou inactif: ID $product_id");
                }
            }
        }
        
        if (empty($order_products)) {
            throw new Exception('Aucun produit valide sélectionné.');
        }
        
        // Debug: Afficher les produits préparés
        error_log("Produits préparés: " . print_r($order_products, true));
        
        // Vérifier que l'affilié existe et est actif
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE id = ? AND type = 'affiliate' AND status = 'active'");
        $stmt->execute([$affiliate_id]);
        $affiliate = $stmt->fetch();
        
        if (!$affiliate) {
            throw new Exception('Affilié invalide ou inactif.');
        }

        // Utiliser le système d'intégration pour créer la commande
        error_log("Appel de createOrderViaAffiliate avec affilié ID: $affiliate_id");
        $result = createOrderViaAffiliate($client_data, $affiliate_id, $order_products);
        
        // Debug: Afficher le résultat
        error_log("Résultat de createOrderViaAffiliate: " . print_r($result, true));
        
        if ($result['success']) {
            $_SESSION['success'] = "Commande créée avec succès ! Numéro de commande : " . $result['order_number'];
            
            // Rediriger vers la page de confirmation
            header('Location: order_confirmation.php?order_id=' . $result['order_id']);
            exit();
        } else {
            throw new Exception($result['error']);
        }
        
    } catch (Exception $e) {
        error_log("Erreur dans process_order.php: " . $e->getMessage());
        echo '<div style="color:red;font-weight:bold;">Erreur : ' . $e->getMessage() . '</div>';
        exit;
    }
} else {
    // Rediriger vers la page des produits si accès direct
    header('Location: products.php');
    exit();
} 
?> 