<?php
session_start();
require_once '../config/database.php';
require_once '../includes/system_integration.php';

// Vérifier si l'utilisateur est connecté (admin ou confirmateur)
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['confirmateur_id'])) {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $order_id = (int)$_POST['order_id'];
        $new_status = $_POST['new_status'];
        $user_id = $_SESSION['admin_id'] ?? $_SESSION['confirmateur_id'];
        $user_type = isset($_SESSION['admin_id']) ? 'admin' : 'confirmateur';
        
        // Validation des données
        if (!$order_id || !$new_status) {
            throw new Exception('Données manquantes.');
        }
        
        // Vérifier que la commande existe
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            throw new Exception('Commande introuvable.');
        }
        
        // Vérifier les permissions selon le type d'utilisateur
        if ($user_type === 'confirmateur') {
            // Les confirmateurs ne peuvent que confirmer les commandes
            if ($new_status !== 'confirmed') {
                throw new Exception('Les confirmateurs ne peuvent que confirmer les commandes.');
            }
            // Vérifier que la commande est bien en statut 'pending'
            // if ($order['status'] !== 'pending') {
            //     throw new Exception('Seules les commandes en attente (pending) peuvent être confirmées.');
            // }
            
            // Vérifier que le confirmateur est assigné au client de cette commande
            $stmt = $conn->prepare("
                SELECT cc.confirmateur_id 
                FROM confirmateur_clients cc 
                JOIN users u ON cc.client_id = u.id 
                WHERE u.email = ? AND cc.confirmateur_id = ? AND cc.status = 'active'
            ");
            $stmt->execute([$order['customer_email'], $user_id]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Vous n\'êtes pas autorisé à confirmer cette commande.');
            }
            
            // Utiliser le système d'intégration pour confirmer la commande
            $result = confirmOrder($order_id, $user_id);
            
            if ($result['success']) {
                $_SESSION['success'] = "Commande confirmée avec succès !";
            } else {
                throw new Exception($result['error']);
            }
            
        } else {
            // Les admins peuvent changer tous les statuts
            $allowed_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
            
            if (!in_array($new_status, $allowed_statuses)) {
                throw new Exception('Statut non autorisé.');
            }
            
            // Mettre à jour le statut de la commande
            $stmt = $conn->prepare("
                UPDATE orders SET 
                status = ?, 
                updated_at = NOW(),
                updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $user_id, $order_id]);
            
            // Si la commande est livrée, marquer les commissions comme payables
            if ($new_status === 'delivered') {
                $stmt = $conn->prepare("
                    UPDATE orders SET 
                    commission_payable = 1,
                    delivered_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$order_id]);
            }
            
            $_SESSION['success'] = "Statut de la commande mis à jour avec succès !";
        }
        
        // Logger l'action
        $action = $user_type === 'confirmateur' ? 'Commande confirmée' : 'Statut commande modifié';
        $stmt = $conn->prepare("
            INSERT INTO admin_logs (admin_id, action, target_id, data, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id, 
            $action, 
            $order_id, 
            json_encode(['old_status' => $order['status'], 'new_status' => $new_status])
        ]);
        
        // Rediriger vers la page appropriée
        if ($user_type === 'confirmateur') {
            header('Location: ../confirmateur/dashboard.php');
        } else {
            header('Location: ../admin/orders.php');
        }
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
        
        // Rediriger vers la page appropriée
        if (isset($_SESSION['confirmateur_id'])) {
            header('Location: ../confirmateur/dashboard.php');
        } else {
            header('Location: ../admin/orders.php');
        }
        exit();
    }
} else {
    // Rediriger si accès direct
    if (isset($_SESSION['confirmateur_id'])) {
        header('Location: ../confirmateur/dashboard.php');
    } else {
        header('Location: ../admin/orders.php');
    }
    exit();
}
?> 