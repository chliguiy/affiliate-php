<?php
try {
    // Connexion à MySQL sans sélectionner de base de données
    $pdo = new PDO(
        "mysql:host=localhost:8889",
        "root",
        "root",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Créer la base de données si elle n'existe pas
    $pdo->exec("DROP DATABASE IF EXISTS chic_affiliate");
    $pdo->exec("CREATE DATABASE chic_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Base de données créée.<br>";

    // Sélectionner la base de données
    $pdo->exec("USE chic_affiliate");

    // Désactiver les contraintes de clé étrangère temporairement
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Créer la table users
    $pdo->exec("
        CREATE TABLE `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `email` varchar(100) NOT NULL,
            `full_name` varchar(100) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `address` text,
            `city` varchar(100) DEFAULT NULL,
            `type` enum('admin','affiliate') NOT NULL DEFAULT 'affiliate',
            `status` enum('active','inactive') NOT NULL DEFAULT 'active',
            `commission_rate` decimal(5,2) DEFAULT '0.00',
            `balance` decimal(10,2) DEFAULT '0.00',
            `total_sales` decimal(10,2) DEFAULT '0.00',
            `total_commission` decimal(10,2) DEFAULT '0.00',
            `last_login` datetime DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table users créée.<br>";

    // Créer la table categories
    $pdo->exec("
        CREATE TABLE `categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `image` varchar(255) DEFAULT NULL,
            `parent_id` int(11) DEFAULT NULL,
            `sort_order` int(11) DEFAULT '0',
            `status` enum('active','inactive') NOT NULL DEFAULT 'active',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `parent_id` (`parent_id`),
            CONSTRAINT `categories_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table categories créée.<br>";

    // Créer la table products
    $pdo->exec("
        CREATE TABLE `products` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `price` decimal(10,2) NOT NULL DEFAULT '0.00',
            `old_price` decimal(10,2) DEFAULT NULL,
            `cost` decimal(10,2) DEFAULT '0.00',
            `commission_fixed` decimal(10,2) DEFAULT '0.00',
            `sku` varchar(50) DEFAULT NULL,
            `barcode` varchar(50) DEFAULT NULL,
            `quantity` int(11) DEFAULT '0',
            `minimum_quantity` int(11) DEFAULT '1',
            `subtract_stock` tinyint(1) DEFAULT '1',
            `stock_status` enum('in_stock','out_of_stock','pre_order') DEFAULT 'in_stock',
            `requires_shipping` tinyint(1) DEFAULT '1',
            `weight` decimal(10,2) DEFAULT '0.00',
            `weight_class` enum('kg','g','lb','oz') DEFAULT 'kg',
            `length` decimal(10,2) DEFAULT '0.00',
            `width` decimal(10,2) DEFAULT '0.00',
            `height` decimal(10,2) DEFAULT '0.00',
            `length_class` enum('cm','mm','in') DEFAULT 'cm',
            `image` varchar(255) DEFAULT NULL,
            `additional_images` text,
            `category_id` int(11) DEFAULT NULL,
            `manufacturer` varchar(100) DEFAULT NULL,
            `meta_title` varchar(255) DEFAULT NULL,
            `meta_description` text,
            `meta_keywords` varchar(255) DEFAULT NULL,
            `sort_order` int(11) DEFAULT '0',
            `status` enum('active','inactive') NOT NULL DEFAULT 'active',
            `viewed` int(11) DEFAULT '0',
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `category_id` (`category_id`),
            KEY `status` (`status`),
            UNIQUE KEY `sku` (`sku`),
            CONSTRAINT `products_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table products créée.<br>";

    // Créer la table orders
    $pdo->exec("
        CREATE TABLE `orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_number` varchar(50) NOT NULL,
            `invoice_number` varchar(50) DEFAULT NULL,
            `invoice_prefix` varchar(10) DEFAULT NULL,
            `affiliate_id` int(11) NOT NULL,
            `customer_name` varchar(255) NOT NULL,
            `customer_email` varchar(100) DEFAULT NULL,
            `customer_phone` varchar(20) NOT NULL,
            `customer_address` text NOT NULL,
            `city` varchar(100) NOT NULL,
            `postal_code` varchar(20) DEFAULT NULL,
            `shipping_method` varchar(100) DEFAULT NULL,
            `shipping_cost` decimal(10,2) DEFAULT '0.00',
            `payment_method` varchar(100) DEFAULT NULL,
            `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
            `total_products` decimal(10,2) NOT NULL DEFAULT '0.00',
            `total_discount` decimal(10,2) DEFAULT '0.00',
            `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
            `commission` decimal(10,2) NOT NULL DEFAULT '0.00',
            `status` enum('new','unconfirmed','confirmed','shipping','delivered','returned','refused','cancelled','duplicate') NOT NULL DEFAULT 'new',
            `notes` text,
            `admin_notes` text,
            `tracking_number` varchar(100) DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `confirmed_at` datetime DEFAULT NULL,
            `shipped_at` datetime DEFAULT NULL,
            `delivered_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `order_number` (`order_number`),
            KEY `affiliate_id` (`affiliate_id`),
            KEY `status` (`status`),
            KEY `created_at` (`created_at`),
            CONSTRAINT `orders_affiliate_fk` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table orders créée.<br>";

    // Créer la table order_items
    $pdo->exec("
        CREATE TABLE `order_items` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `name` varchar(255) NOT NULL,
            `sku` varchar(50) DEFAULT NULL,
            `quantity` int(11) NOT NULL DEFAULT '1',
            `price` decimal(10,2) NOT NULL,
            `old_price` decimal(10,2) DEFAULT NULL,
            `cost` decimal(10,2) DEFAULT '0.00',
            `discount` decimal(10,2) DEFAULT '0.00',
            `commission_rate` decimal(5,2) NOT NULL,
            `commission` decimal(10,2) NOT NULL,
            `weight` decimal(10,2) DEFAULT '0.00',
            `total` decimal(10,2) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`),
            KEY `product_id` (`product_id`),
            CONSTRAINT `order_items_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
            CONSTRAINT `order_items_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table order_items créée.<br>";

    // Créer la table payments
    $pdo->exec("
        CREATE TABLE `payments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `affiliate_id` int(11) NOT NULL,
            `amount` decimal(10,2) NOT NULL,
            `method` varchar(100) NOT NULL,
            `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
            `reference` varchar(100) DEFAULT NULL,
            `notes` text,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `completed_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `affiliate_id` (`affiliate_id`),
            CONSTRAINT `payments_affiliate_fk` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table payments créée.<br>";

    // Insérer un utilisateur de test si la table est vide
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `users` (`username`, `password`, `email`, `full_name`, `phone`, `type`, `status`, `commission_rate`) VALUES
            ('admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin@example.com', 'Admin User', '0600000000', 'admin', 'active', 0),
            ('affiliate1', '" . password_hash('affiliate123', PASSWORD_DEFAULT) . "', 'affiliate1@example.com', 'First Affiliate', '0611111111', 'affiliate', 'active', 20)
        ");
        echo "Utilisateurs de test créés.<br>";
    }

    // Insérer les données de test pour les catégories
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `categories` (`name`, `description`, `status`, `sort_order`) VALUES
            ('Vêtements', 'Tous types de vêtements', 'active', 1),
            ('Chaussures', 'Tous types de chaussures', 'active', 2),
            ('Accessoires', 'Accessoires de mode', 'active', 3)
        ");
        echo "Données de test des catégories insérées.<br>";
    }

    // Insérer les données de test pour les produits
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `products` (`name`, `description`, `price`, `commission_rate`, `category_id`, `status`, `sku`, `quantity`) VALUES
            ('T-shirt Basic', 'T-shirt en coton de qualité', 199.00, 20.00, 1, 'active', 'TSH001', 100),
            ('Jean Slim', 'Jean slim fit confortable', 399.00, 25.00, 1, 'active', 'JEN001', 50),
            ('Baskets Sport', 'Baskets confortables pour le sport', 599.00, 30.00, 2, 'active', 'BSK001', 30),
            ('Sac à Main', 'Sac à main élégant en cuir', 899.00, 35.00, 3, 'active', 'SAC001', 20)
        ");
        echo "Données de test des produits insérées.<br>";
    }

    // Insérer les données de test pour les commandes
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    if ($stmt->fetchColumn() == 0) {
        // Créer quelques commandes de test
        $order_data = [
            [
                'order' => [
                    'order_number' => 'CMD-001',
                    'affiliate_id' => 2,
                    'customer_name' => 'John Doe',
                    'customer_email' => 'john@example.com',
                    'customer_phone' => '0600000000',
                    'customer_address' => '123 Rue Example',
                    'city' => 'Casablanca',
                    'status' => 'new',
                    'total_products' => 598.00,
                    'total_amount' => 598.00,
                    'commission' => 119.60
                ],
                'items' => [
                    ['product_id' => 1, 'quantity' => 2, 'price' => 199.00, 'commission_rate' => 20.00],
                    ['product_id' => 2, 'quantity' => 1, 'price' => 399.00, 'commission_rate' => 25.00]
                ]
            ],
            [
                'order' => [
                    'order_number' => 'CMD-002',
                    'affiliate_id' => 2,
                    'customer_name' => 'Jane Smith',
                    'customer_email' => 'jane@example.com',
                    'customer_phone' => '0611111111',
                    'customer_address' => '456 Avenue Test',
                    'city' => 'Rabat',
                    'status' => 'confirmed',
                    'total_products' => 1198.00,
                    'total_amount' => 1198.00,
                    'commission' => 359.40
                ],
                'items' => [
                    ['product_id' => 3, 'quantity' => 2, 'price' => 599.00, 'commission_rate' => 30.00]
                ]
            ]
        ];

        foreach ($order_data as $data) {
            // Insérer la commande
            $stmt = $pdo->prepare("
                INSERT INTO `orders` (
                    order_number, affiliate_id, customer_name, customer_email,
                    customer_phone, customer_address, city, status,
                    total_products, total_amount, commission
                ) VALUES (
                    :order_number, :affiliate_id, :customer_name, :customer_email,
                    :customer_phone, :customer_address, :city, :status,
                    :total_products, :total_amount, :commission
                )
            ");
            $stmt->execute($data['order']);
            $order_id = $pdo->lastInsertId();

            // Insérer les articles de la commande
            foreach ($data['items'] as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO `order_items` (
                        order_id, product_id, quantity, price,
                        commission_rate, commission, total
                    ) VALUES (
                        :order_id, :product_id, :quantity, :price,
                        :commission_rate, :commission, :total
                    )
                ");

                $commission = ($item['price'] * $item['quantity'] * $item['commission_rate']) / 100;
                $total = $item['price'] * $item['quantity'];

                $stmt->execute([
                    'order_id' => $order_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'commission_rate' => $item['commission_rate'],
                    'commission' => $commission,
                    'total' => $total
                ]);
            }
        }
        echo "Données de test des commandes insérées.<br>";
    }

    // Réactiver les contraintes de clé étrangère
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "<br><strong>Installation terminée avec succès !</strong>";
    echo "<br><br>Identifiants de connexion :<br>";
    echo "Admin : admin / admin123<br>";
    echo "Affilié : affiliate1 / affiliate123<br>";
    
} catch(PDOException $e) {
    echo "<br><strong>Erreur : " . $e->getMessage() . "</strong>";
}
?> 