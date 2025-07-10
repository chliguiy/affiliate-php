<?php
/**
 * Système d'intégration centralisé pour Chic Affiliate
 * Ce fichier lie toutes les fonctions entre les différentes parties du système :
 * - Clients
 * - Affiliés  
 * - Confirmateurs
 * - Administrateurs
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../ApiDelivery.php';

class SystemIntegration {
    private $pdo;
    private $logger;
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }
    
    /**
     * ========================================
     * FONCTIONS DE LIAISON CLIENT-AFFILIÉ
     * ========================================
     */
    
    /**
     * Créer une commande client via un affilié
     */
    public function createOrderViaAffiliate($client_data, $affiliate_id, $products) {
        try {
            error_log("Début de createOrderViaAffiliate - Affilié: $affiliate_id, Produits: " . count($products));
            
            $this->pdo->beginTransaction();
            
            // 1. Créer ou récupérer le client
            $client_id = $this->getOrCreateClient($client_data);
            error_log("Client ID: $client_id");
            
            // 2. Créer la commande
            $order_number = $this->generateOrderNumber();
            $total_amount = 0;
            $commission_total = 0;
            
            // Calculer le total
            foreach ($products as $product) {
                $total_amount += $product['price'] * $product['quantity'];
            }
            
            // Calcul professionnel de la marge affilié pour 1 produit (cohérent avec le frontend)
            $final_sale_price = isset($_POST['final_sale_price']) ? floatval($_POST['final_sale_price']) : 0;
            $delivery_fee = isset($_POST['delivery_fee']) ? floatval($_POST['delivery_fee']) : 0;
            $quantity = isset($products[0]['quantity']) ? (int)$products[0]['quantity'] : 1;
            $admin_cost = $products[0]['price'] * $quantity;
            $affiliate_margin = $final_sale_price - $admin_cost - $delivery_fee;
            
            // La commission est maintenant égale à la marge affiliée
            $commission_total = $affiliate_margin;
            
            error_log("Total: $total_amount, Commission (marge affiliée): $commission_total, Final Sale Price: $final_sale_price, Affiliate Margin: $affiliate_margin");
            
            // Chercher le confirmateur assigné au client
            $stmt = $this->pdo->prepare("SELECT confirmateur_id FROM confirmateur_clients WHERE client_id = ? AND status = 'active'");
            $stmt->execute([$client_id]);
            $confirmateur = $stmt->fetch();
            
            if ($confirmateur && !empty($confirmateur['confirmateur_id'])) {
                // Assigner automatiquement au confirmateur
                $status = 'pending';
                $confirmateur_id = $confirmateur['confirmateur_id'];
            } else {
                $status = 'new';
                $confirmateur_id = null;
            }
            
            // Toujours renseigner le champ customer_name avec le nom du client
            $customer_name = isset($client_data['name']) ? $client_data['name'] : '';
            
            // Récupérer le commentaire
            $comment = isset($client_data['comment']) ? $client_data['comment'] : null;
            
            // Créer la commande avec les nouveaux champs
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (
                    user_id, 
                    affiliate_id, 
                    confirmateur_id,
                    customer_name, 
                    customer_email, 
                    customer_phone, 
                    customer_address, 
                    customer_city, 
                    order_number, 
                    total_amount, 
                    commission_amount, 
                    final_sale_price,
                    affiliate_margin,
                    delivery_fee,
                    notes,
                    status, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $client_id, 
                $affiliate_id, 
                $confirmateur_id,
                $customer_name, 
                $client_data['email'],
                $client_data['phone'], 
                $client_data['address'], 
                $client_data['city'],
                $order_number, 
                $total_amount, 
                $commission_total,
                $final_sale_price,
                $affiliate_margin,
                $delivery_fee,
                $comment,
                $status
            ]);
            
            $order_id = $this->pdo->lastInsertId();
            error_log("Commande créée avec ID: " . $order_id);
            
            // Appel API livraison (création de colis)
            $apiDelivery = new ApiDelivery();
            $parcelData = [
                'external_id' => $order_id,
                'receiver' => $customer_name,
                'phone' => $client_data['phone'],
                'city_id' => $client_data['city'],
                'product_nature' => 'Order from Website', // nkhalik had partie mn ba3d
                'price' => $final_sale_price,
                'address' => $client_data['address'],
                'note' => $comment,
                'can_open' => false
            ];
            $response = $apiDelivery->createParcel($parcelData);
            $result = json_decode($response, true);
            
            // Correction : si confirmateur_id est renseigné, forcer le statut à 'pending'
            if ($confirmateur_id) {
                $stmt = $this->pdo->prepare("UPDATE orders SET status = 'pending' WHERE id = ?");
                $stmt->execute([$order_id]);
                error_log("Statut de la commande mis à jour à 'pending' pour l'assignation confirmateur.");
            }
            
            // 3. Ajouter les produits à la commande
            foreach ($products as $product) {
                $this->addOrderItem($order_id, $product);
            }
            
            // Mettre à jour la commission avec la marge affiliée (pas de recalcul basé sur le taux)
            $stmt = $this->pdo->prepare("UPDATE orders SET commission_amount = affiliate_margin WHERE id = ?");
            $stmt->execute([$order_id]);
            
            // 4. Créer la commission pour l'affilié (si la table existe) - utilise la marge affiliée
            try {
                $this->createAffiliateCommission($affiliate_id, $order_id, $affiliate_margin);
            } catch (Exception $e) {
                error_log("Erreur lors de la création de la commission: " . $e->getMessage());
                // Continuer même si la commission ne peut pas être créée
            }
            
            // 5. Notifier le confirmateur assigné (si la table existe)
            try {
                $this->notifyConfirmateur($client_id, $order_id);
            } catch (Exception $e) {
                error_log("Erreur lors de la notification confirmateur: " . $e->getMessage());
                // Continuer même si la notification ne peut pas être envoyée
            }
            
            $this->pdo->commit();
            error_log("Transaction validée avec succès");
            
            return ['success' => true, 'order_id' => $order_id, 'order_number' => $order_number];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur dans createOrderViaAffiliate: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * ========================================
     * FONCTIONS DE LIAISON AFFILIÉ-CONFIRMATEUR
     * ========================================
     */
    
    /**
     * Assigner un client à un confirmateur
     */
    public function assignClientToConfirmateur($client_id, $confirmateur_id) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO confirmateur_clients (confirmateur_id, client_id, status)
                VALUES (?, ?, 'active')
            ");
            $stmt->execute([$confirmateur_id, $client_id]);
            
            // Notifier le confirmateur
            $this->notifyConfirmateurAssignment($confirmateur_id, $client_id);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Notifier un confirmateur d'une nouvelle commande
     */
    public function notifyConfirmateur($client_id, $order_id) {
        try {
            // Récupérer le confirmateur assigné au client
            $stmt = $this->pdo->prepare("
                SELECT confirmateur_id FROM confirmateur_clients 
                WHERE client_id = ? AND status = 'active'
            ");
            $stmt->execute([$client_id]);
            $confirmateur = $stmt->fetch();
            
            if ($confirmateur) {
                // Créer une notification pour le confirmateur
                $this->createConfirmateurNotification($confirmateur['confirmateur_id'], $order_id);
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * ========================================
     * FONCTIONS DE LIAISON CONFIRMATEUR-ADMIN
     * ========================================
     */
    
    /**
     * Confirmer une commande (confirmateur -> admin)
     */
    public function confirmOrder($order_id, $confirmateur_id) {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Mettre à jour le statut de la commande
            $stmt = $this->pdo->prepare("
                UPDATE orders SET status = 'confirmed', confirmed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$order_id]);
            
            // 2. Créer un paiement pour le confirmateur (optionnel)
            if (method_exists($this, 'createConfirmateurPayment')) {
                $this->createConfirmateurPayment($confirmateur_id, 8.00); // 8 DH par commande confirmée
            }
            
            // 3. Notifier l'admin (optionnel)
            if (method_exists($this, 'notifyAdminOrderConfirmed')) {
                $this->notifyAdminOrderConfirmed($order_id, $confirmateur_id);
            }
            
            // 4. Mettre à jour les statistiques (optionnel)
            if (method_exists($this, 'updateOrderStatistics')) {
                $this->updateOrderStatistics($order_id);
            }
            
            $this->pdo->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * ========================================
     * FONCTIONS DE LIAISON ADMIN-SYSTÈME
     * ========================================
     */
    
    /**
     * Approuver un affilié (admin)
     */
    public function approveAffiliate($affiliate_id, $admin_id) {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Activer l'affilié
            $stmt = $this->pdo->prepare("
                UPDATE users SET status = 'active', approved_at = NOW()
                WHERE id = ? AND type = 'affiliate'
            ");
            $stmt->execute([$affiliate_id]);
            
            // 2. Créer un lien d'affiliation par défaut
            $this->createDefaultAffiliateLinks($affiliate_id);
            
            // 3. Notifier l'affilié
            $this->notifyAffiliateApproved($affiliate_id);
            
            // 4. Logger l'action admin
            $this->logAdminAction($admin_id, 'approve_affiliate', $affiliate_id);
            
            $this->pdo->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Payer les commissions d'un affilié (admin)
     */
    public function payAffiliateCommissions($affiliate_id, $admin_id) {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Récupérer toutes les commissions en attente
            $stmt = $this->pdo->prepare("
                SELECT SUM(commission_amount) as total_commission
                FROM orders 
                WHERE affiliate_id = ? AND status = 'delivered' 
                AND commission_paid = 0
            ");
            $stmt->execute([$affiliate_id]);
            $result = $stmt->fetch();
            $total_commission = $result['total_commission'] ?? 0;
            
            if ($total_commission > 0) {
                // 2. Marquer les commissions comme payées
                $stmt = $this->pdo->prepare("
                    UPDATE orders SET commission_paid = 1, commission_paid_at = NOW()
                    WHERE affiliate_id = ? AND status = 'delivered' AND commission_paid = 0
                ");
                $stmt->execute([$affiliate_id]);
                
                // 3. Créer une transaction de paiement
                $this->createPaymentTransaction($affiliate_id, $total_commission, 'commission_payment');
                
                // 4. Notifier l'affilié
                $this->notifyAffiliatePayment($affiliate_id, $total_commission);
                
                // 5. Logger l'action admin
                $this->logAdminAction($admin_id, 'pay_commissions', $affiliate_id, ['amount' => $total_commission]);
            }
            
            $this->pdo->commit();
            return ['success' => true, 'amount_paid' => $total_commission];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * ========================================
     * FONCTIONS UTILITAIRES
     * ========================================
     */
    
    /**
     * Récupérer ou créer un client
     */
    private function getOrCreateClient($client_data) {
        try {
            // Vérifier si le client existe déjà
            $stmt = $this->pdo->prepare("
                SELECT id FROM users WHERE email = ? AND type = 'customer'
            ");
            $stmt->execute([$client_data['email']]);
            $existing_client = $stmt->fetch();
            
            if ($existing_client) {
                error_log("Client existant trouvé: " . $existing_client['id']);
                return $existing_client['id'];
            }
            
            // Créer un username unique basé sur le nom
            $base_username = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($client_data['name']));
            $unique_username = $base_username;
            $counter = 1;
            while (true) {
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$unique_username]);
                if (!$stmt->fetch()) break;
                $unique_username = $base_username . '-' . rand(1000, 9999);
                $counter++;
                if ($counter > 10) {
                    $unique_username = $base_username . '-' . uniqid();
                    break;
                }
            }
            // Créer un nouveau client
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, phone, city, type, status, created_at)
                VALUES (?, ?, ?, ?, 'customer', 'active', NOW())
            ");
            $stmt->execute([
                $unique_username,
                $client_data['email'],
                $client_data['phone'],
                $client_data['city']
            ]);
            
            $client_id = $this->pdo->lastInsertId();
            error_log("Nouveau client créé: " . $client_id);
            return $client_id;
            
        } catch (Exception $e) {
            error_log("Erreur dans getOrCreateClient: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Générer un numéro de commande unique
     */
    private function generateOrderNumber() {
        return 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    }
    
    /**
     * Ajouter un produit à une commande
     */
    private function addOrderItem($order_id, $product) {
        try {
            // Calcul de la commission pour cet item
            $commission = 0;
            if (isset($product['price'], $product['quantity'], $product['commission_rate'])) {
                $commission = $product['price'] * $product['quantity'] * $product['commission_rate'] / 100;
            }
            $stmt = $this->pdo->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, price, commission_rate, commission, color, size, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $order_id,
                $product['id'],
                $product['name'] ?? '',
                $product['quantity'],
                $product['price'],
                $product['commission_rate'],
                $commission,
                $product['color'] ?? null,
                $product['size'] ?? null
            ]);
            error_log("Produit ajouté à la commande: " . $product['name'] . ", commission: " . $commission);
        } catch (Exception $e) {
            error_log("Erreur dans addOrderItem: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Créer une commission pour un affilié
     */
    private function createAffiliateCommission($affiliate_id, $order_id, $amount) {
        try {
            // Vérifier si la table commissions existe
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'commissions'");
            if ($stmt->rowCount() == 0) {
                error_log("Table commissions n'existe pas, création ignorée");
                return;
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO commissions (affiliate_id, order_id, amount, status, created_at)
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$affiliate_id, $order_id, $amount]);
            error_log("Commission créée pour affilié: " . $affiliate_id);
        } catch (Exception $e) {
            error_log("Erreur dans createAffiliateCommission: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Créer une notification pour un confirmateur
     */
    private function createConfirmateurNotification($confirmateur_id, $order_id) {
        try {
            // Vérifier si la table confirmateur_notifications existe
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'confirmateur_notifications'");
            if ($stmt->rowCount() == 0) {
                error_log("Table confirmateur_notifications n'existe pas, notification ignorée");
                return;
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO confirmateur_notifications (confirmateur_id, order_id, type, message, created_at)
                VALUES (?, ?, 'new_order', 'Nouvelle commande à confirmer', NOW())
            ");
            $stmt->execute([$confirmateur_id, $order_id]);
            error_log("Notification confirmateur créée");
        } catch (Exception $e) {
            error_log("Erreur dans createConfirmateurNotification: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Créer un paiement pour un confirmateur
     */
    private function createConfirmateurPayment($confirmateur_id, $amount) {
        $stmt = $this->pdo->prepare("
            INSERT INTO confirmateur_paiements (confirmateur_id, montant, statut, date_paiement)
            VALUES (?, ?, 'en_attente', NOW())
        ");
        $stmt->execute([$confirmateur_id, $amount]);
    }
    
    /**
     * Notifier l'admin d'une commande confirmée
     */
    private function notifyAdminOrderConfirmed($order_id, $confirmateur_id) {
        // Créer une notification pour l'admin
        $stmt = $this->pdo->prepare("
            INSERT INTO admin_notifications (type, message, data, created_at)
            VALUES ('order_confirmed', 'Commande confirmée par un confirmateur', ?, NOW())
        ");
        $stmt->execute([json_encode(['order_id' => $order_id, 'confirmateur_id' => $confirmateur_id])]);
    }
    
    /**
     * Mettre à jour les statistiques de commande
     */
    private function updateOrderStatistics($order_id) {
        // Mettre à jour les statistiques globales
        $stmt = $this->pdo->prepare("
            UPDATE order_statistics SET 
            confirmed_orders = confirmed_orders + 1,
            last_updated = NOW()
            WHERE id = 1
        ");
        $stmt->execute();
    }
    
    /**
     * Créer des liens d'affiliation par défaut
     */
    private function createDefaultAffiliateLinks($affiliate_id) {
        // Récupérer tous les produits actifs
        $stmt = $this->pdo->query("SELECT id FROM products WHERE status = 'active'");
        $products = $stmt->fetchAll();
        
        foreach ($products as $product) {
            $unique_code = uniqid('ref_' . $affiliate_id . '_');
            $stmt = $this->pdo->prepare("
                INSERT INTO affiliate_links (affiliate_id, product_id, unique_code, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$affiliate_id, $product['id'], $unique_code]);
        }
    }
    
    /**
     * Notifier un affilié de son approbation
     */
    private function notifyAffiliateApproved($affiliate_id) {
        $stmt = $this->pdo->prepare("
            INSERT INTO affiliate_notifications (affiliate_id, type, message, created_at)
            VALUES (?, 'approved', 'Votre compte affilié a été approuvé', NOW())
        ");
        $stmt->execute([$affiliate_id]);
    }
    
    /**
     * Logger une action admin
     */
    private function logAdminAction($admin_id, $action, $target_id, $data = []) {
        $stmt = $this->pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, target_id, data, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$admin_id, $action, $target_id, json_encode($data)]);
    }
    
    /**
     * Créer une transaction de paiement
     */
    private function createPaymentTransaction($user_id, $amount, $type) {
        $stmt = $this->pdo->prepare("
            INSERT INTO transactions (user_id, type, amount, status, created_at)
            VALUES (?, ?, ?, 'completed', NOW())
        ");
        $stmt->execute([$user_id, $type, $amount]);
    }
    
    /**
     * Notifier un affilié d'un paiement
     */
    private function notifyAffiliatePayment($affiliate_id, $amount) {
        $stmt = $this->pdo->prepare("
            INSERT INTO affiliate_notifications (affiliate_id, type, message, data, created_at)
            VALUES (?, 'payment', 'Paiement de commission reçu', ?, NOW())
        ");
        $stmt->execute([$affiliate_id, json_encode(['amount' => $amount])]);
    }
    
    /**
     * ========================================
     * FONCTIONS DE RAPPORT ET ANALYSE
     * ========================================
     */
    
    /**
     * Obtenir les statistiques complètes du système
     */
    public function getSystemStatistics() {
        try {
            $stats = [];
            
            // Statistiques des utilisateurs
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN type = 'customer' THEN 1 ELSE 0 END) as total_customers,
                    SUM(CASE WHEN type = 'affiliate' THEN 1 ELSE 0 END) as total_affiliates,
                    SUM(CASE WHEN type = 'admin' THEN 1 ELSE 0 END) as total_admins
                FROM users
            ");
            $stats['users'] = $stmt->fetch();
            
            // Statistiques des commandes
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(final_sale_price) as total_revenue
                FROM orders
            ");
            $stats['orders'] = $stmt->fetch();
            
            // Statistiques des confirmateurs
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_confirmateurs,
                    SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as pending_payments
                FROM equipe 
                LEFT JOIN confirmateur_paiements ON equipe.id = confirmateur_paiements.confirmateur_id
                WHERE role = 'confirmateur'
            ");
            $stats['confirmateurs'] = $stmt->fetch();
            
            return ['success' => true, 'data' => $stats];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtenir les rapports de performance par affilié
     */
    public function getAffiliatePerformanceReport($date_from = null, $date_to = null) {
        try {
            $where_clause = "";
            $params = [];
            
            if ($date_from && $date_to) {
                $where_clause = "WHERE o.created_at BETWEEN ? AND ?";
                $params = [$date_from, $date_to];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(o.final_sale_price) as total_sales,
                    COALESCE(SUM(o.commission_amount), 0) as total_commission,
                    SUM(CASE WHEN o.commission_paid = 1 THEN o.commission_amount ELSE 0 END) as paid_commission,
                    AVG(o.final_sale_price) as avg_order_value
                FROM users u
                LEFT JOIN orders o ON u.id = o.affiliate_id
                $where_clause
                WHERE u.type = 'affiliate'
                GROUP BY u.id, u.username, u.email
                ORDER BY total_sales DESC
            ");
            $stmt->execute($params);
            
            return ['success' => true, 'data' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtenir les rapports de performance par confirmateur
     */
    public function getConfirmateurPerformanceReport($date_from = null, $date_to = null) {
        try {
            $where_clause = "";
            $params = [];
            
            if ($date_from && $date_to) {
                $where_clause = "AND o.created_at BETWEEN ? AND ?";
                $params = [$date_from, $date_to];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    e.id,
                    e.nom,
                    e.email,
                    COUNT(DISTINCT cc.client_id) as assigned_clients,
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(CASE WHEN o.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                    SUM(cp.montant) as total_earnings,
                    SUM(CASE WHEN cp.statut = 'paye' THEN cp.montant ELSE 0 END) as paid_earnings
                FROM equipe e
                LEFT JOIN confirmateur_clients cc ON e.id = cc.confirmateur_id AND cc.status = 'active'
                LEFT JOIN users u ON cc.client_id = u.id
                LEFT JOIN orders o ON u.email = o.customer_email $where_clause
                LEFT JOIN confirmateur_paiements cp ON e.id = cp.confirmateur_id
                WHERE e.role = 'confirmateur'
                GROUP BY e.id, e.nom, e.email
                ORDER BY confirmed_orders DESC
            ");
            $stmt->execute($params);
            
            return ['success' => true, 'data' => $stmt->fetchAll()];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Fonctions globales pour faciliter l'utilisation
function getSystemIntegration() {
    static $integration = null;
    if ($integration === null) {
        $integration = new SystemIntegration();
    }
    return $integration;
}

// Fonction pour créer une commande via un affilié
function createOrderViaAffiliate($client_data, $affiliate_id, $products) {
    return getSystemIntegration()->createOrderViaAffiliate($client_data, $affiliate_id, $products);
}

// Fonction pour assigner un client à un confirmateur
function assignClientToConfirmateur($client_id, $confirmateur_id) {
    return getSystemIntegration()->assignClientToConfirmateur($client_id, $confirmateur_id);
}

// Fonction pour confirmer une commande
function confirmOrder($order_id, $confirmateur_id) {
    return getSystemIntegration()->confirmOrder($order_id, $confirmateur_id);
}

// Fonction pour approuver un affilié
function approveAffiliate($affiliate_id, $admin_id) {
    return getSystemIntegration()->approveAffiliate($affiliate_id, $admin_id);
}

// Fonction pour payer les commissions d'un affilié
function payAffiliateCommissions($affiliate_id, $admin_id) {
    return getSystemIntegration()->payAffiliateCommissions($affiliate_id, $admin_id);
}

// Fonction pour obtenir les statistiques du système
function getSystemStatistics() {
    return getSystemIntegration()->getSystemStatistics();
}

// Fonction pour obtenir le rapport de performance des affiliés
function getAffiliatePerformanceReport($date_from = null, $date_to = null) {
    return getSystemIntegration()->getAffiliatePerformanceReport($date_from, $date_to);
}

// Fonction pour obtenir le rapport de performance des confirmateurs
function getConfirmateurPerformanceReport($date_from = null, $date_to = null) {
    return getSystemIntegration()->getConfirmateurPerformanceReport($date_from, $date_to);
}
?> 