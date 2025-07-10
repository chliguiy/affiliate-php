# Guide d'Intégration du Système Chic Affiliate

## Vue d'ensemble

Le système d'intégration centralisé (`includes/system_integration.php`) connecte toutes les parties du système Chic Affiliate :
- **Clients** : Utilisateurs finaux qui passent des commandes
- **Affiliés** : Revendeurs qui génèrent des ventes
- **Confirmateurs** : Personnel qui valide les commandes
- **Administrateurs** : Gestionnaires du système

## Architecture du Système

### 1. Flux Client → Affilié
```
Client passe commande → Affilié reçoit commission → Confirmateur notifié
```

### 2. Flux Affilié → Confirmateur
```
Affilié crée commande → Client assigné à confirmateur → Notification envoyée
```

### 3. Flux Confirmateur → Admin
```
Confirmateur valide commande → Admin notifié → Paiement confirmateur créé
```

### 4. Flux Admin → Système
```
Admin approuve affilié → Liens d'affiliation créés → Notifications envoyées
```

## Fonctions Principales

### Création de Commandes
```php
// Créer une commande client via un affilié
$result = createOrderViaAffiliate($client_data, $affiliate_id, $products);

// Exemple d'utilisation
$client_data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '0612345678',
    'address' => '123 Rue Test',
    'city' => 'Casablanca'
];

$products = [
    [
        'id' => 1,
        'name' => 'Produit Test',
        'price' => 100.00,
        'quantity' => 2,
        'commission_rate' => 10
    ]
];

$result = createOrderViaAffiliate($client_data, $affiliate_id, $products);
```

### Assignation de Clients
```php
// Assigner un client à un confirmateur
$result = assignClientToConfirmateur($client_id, $confirmateur_id);
```

### Confirmation de Commandes
```php
// Confirmer une commande (confirmateur)
$result = confirmOrder($order_id, $confirmateur_id);
```

### Gestion des Affiliés
```php
// Approuver un affilié (admin)
$result = approveAffiliate($affiliate_id, $admin_id);

// Payer les commissions d'un affilié (admin)
$result = payAffiliateCommissions($affiliate_id, $admin_id);
```

### Rapports et Statistiques
```php
// Obtenir les statistiques du système
$stats = getSystemStatistics();

// Rapport de performance des affiliés
$report = getAffiliatePerformanceReport($date_from, $date_to);

// Rapport de performance des confirmateurs
$report = getConfirmateurPerformanceReport($date_from, $date_to);
```

## Intégration dans les Fichiers Existants

### 1. Processus de Commande (`process_order.php`)
```php
require_once 'includes/system_integration.php';

// Utiliser le système d'intégration pour créer la commande
$result = createOrderViaAffiliate($client_data, $affiliate_id, $order_products);

if ($result['success']) {
    $_SESSION['success'] = "Commande créée avec succès !";
    header('Location: order_confirmation.php?order_id=' . $result['order_id']);
} else {
    $_SESSION['error'] = "Erreur : " . $result['error'];
}
```

### 2. Changement de Statut (`orders/change_status.php`)
```php
require_once '../includes/system_integration.php';

// Pour les confirmateurs
if ($user_type === 'confirmateur') {
    $result = confirmOrder($order_id, $user_id);
    if ($result['success']) {
        $_SESSION['success'] = "Commande confirmée avec succès !";
    }
}
```

### 3. Gestion des Affiliés (`admin/affiliate_activate.php`)
```php
require_once '../includes/system_integration.php';

$result = approveAffiliate($affiliate_id, $admin_id);
if ($result['success']) {
    $_SESSION['success_message'] = "Affilié approuvé avec succès !";
}
```

### 4. Paiement des Commissions (`admin/pay_affiliates.php`)
```php
require_once '../includes/system_integration.php';

$result = payAffiliateCommissions($affiliate_id, $admin_id);
if ($result['success']) {
    $_SESSION['success_message'] = "Paiement effectué : " . $result['amount_paid'] . " MAD";
}
```

## Tables de Base de Données Utilisées

### Tables Principales
- `users` : Clients, affiliés, admins
- `orders` : Commandes avec commissions
- `equipe` : Confirmateurs
- `confirmateur_clients` : Assignations clients-confirmateurs
- `confirmateur_paiements` : Paiements des confirmateurs
- `commissions` : Commissions des affiliés
- `transactions` : Transactions de paiement

### Tables de Support
- `order_items` : Détails des commandes
- `affiliate_links` : Liens d'affiliation
- `admin_logs` : Logs des actions admin
- `affiliate_notifications` : Notifications affiliés
- `confirmateur_notifications` : Notifications confirmateurs
- `admin_notifications` : Notifications admin

## Sécurité et Permissions

### Vérifications Automatiques
- Authentification des utilisateurs
- Vérification des permissions par rôle
- Validation des données d'entrée
- Protection contre les injections SQL

### Logs et Traçabilité
- Toutes les actions importantes sont loggées
- Traçabilité complète des commandes
- Historique des paiements
- Audit trail pour les admins

## Gestion des Erreurs

### Structure de Réponse
```php
// Succès
$result = [
    'success' => true,
    'order_id' => 123,
    'order_number' => 'ORD-20241201-1234'
];

// Erreur
$result = [
    'success' => false,
    'error' => 'Message d\'erreur détaillé'
];
```

### Gestion des Exceptions
```php
try {
    $result = createOrderViaAffiliate($client_data, $affiliate_id, $products);
    if ($result['success']) {
        // Traitement du succès
    } else {
        // Traitement de l'erreur
        $_SESSION['error'] = $result['error'];
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur système : " . $e->getMessage();
}
```

## Bonnes Pratiques

### 1. Inclure le Système d'Intégration
```php
require_once 'includes/system_integration.php';
```

### 2. Vérifier les Résultats
```php
if ($result['success']) {
    // Action réussie
} else {
    // Gérer l'erreur
}
```

### 3. Utiliser les Sessions pour les Messages
```php
$_SESSION['success'] = "Action réussie !";
$_SESSION['error'] = "Erreur : " . $result['error'];
```

### 4. Logger les Actions Importantes
```php
// Le système d'intégration gère automatiquement les logs
// Pas besoin de logging manuel pour les actions standard
```

## Exemples d'Utilisation Avancée

### Création d'une Commande Complète
```php
// 1. Préparer les données client
$client_data = [
    'name' => $_POST['customer_name'],
    'email' => $_POST['customer_email'],
    'phone' => $_POST['customer_phone'],
    'address' => $_POST['customer_address'],
    'city' => $_POST['customer_city']
];

// 2. Préparer les produits
$order_products = [];
foreach ($_POST['products'] as $product_id => $quantity) {
    if ($quantity > 0) {
        $product = getProductInfo($product_id);
        $order_products[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'commission_rate' => $product['commission_rate']
        ];
    }
}

// 3. Créer la commande
$result = createOrderViaAffiliate($client_data, $affiliate_id, $order_products);

// 4. Traiter le résultat
if ($result['success']) {
    // Rediriger vers la confirmation
    header('Location: confirmation.php?order=' . $result['order_number']);
} else {
    // Afficher l'erreur
    $_SESSION['error'] = $result['error'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
```

### Paiement en Masse des Commissions
```php
// Récupérer tous les affiliés avec des commissions en attente
$affiliates = getAffiliatesWithPendingCommissions();

$total_paid = 0;
$success_count = 0;

foreach ($affiliates as $affiliate) {
    $result = payAffiliateCommissions($affiliate['id'], $admin_id);
    
    if ($result['success']) {
        $total_paid += $result['amount_paid'];
        $success_count++;
    }
}

$_SESSION['success'] = "Paiement en masse : $success_count affiliés payés pour $total_paid MAD";
```

## Maintenance et Développement

### Ajouter de Nouvelles Fonctions
1. Créer la méthode dans la classe `SystemIntegration`
2. Ajouter la fonction globale correspondante
3. Documenter la fonction
4. Tester avec différents scénarios

### Débogage
```php
// Activer le mode debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier les résultats
var_dump($result);
```

### Performance
- Les requêtes utilisent des transactions pour la cohérence
- Les index de base de données sont optimisés
- Les requêtes sont préparées pour la sécurité

## Support et Contact

Pour toute question sur l'intégration du système :
1. Consulter ce guide
2. Vérifier les logs d'erreur
3. Tester avec des données de test
4. Contacter l'équipe de développement

---

**Version :** 1.0  
**Date :** Décembre 2024  
**Auteur :** Équipe Chic Affiliate 