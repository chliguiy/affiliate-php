-- Table des commandes
CREATE TABLE IF NOT EXISTS `orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `affiliate_id` int(11) NOT NULL,
    `customer_name` varchar(255) NOT NULL,
    `customer_phone` varchar(20) NOT NULL,
    `city` varchar(100) NOT NULL,
    `address` text NOT NULL,
    `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
    `commission` decimal(10,2) NOT NULL DEFAULT '0.00',
    `status` enum('new','unconfirmed','confirmed','shipping','delivered','returned','refused','cancelled','duplicate') NOT NULL DEFAULT 'new',
    `notes` text,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `delivered_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `affiliate_id` (`affiliate_id`),
    KEY `status` (`status`),
    KEY `created_at` (`created_at`),
    CONSTRAINT `orders_affiliate_fk` FOREIGN KEY (`affiliate_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des produits
CREATE TABLE IF NOT EXISTS `products` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `price` decimal(10,2) NOT NULL DEFAULT '0.00',
    `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
    `image` varchar(255) DEFAULT NULL,
    `category_id` int(11) DEFAULT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    KEY `status` (`status`),
    CONSTRAINT `products_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des éléments de commande
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) NOT NULL DEFAULT '1',
    `price` decimal(10,2) NOT NULL,
    `commission` decimal(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `product_id` (`product_id`),
    CONSTRAINT `order_items_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_items_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des catégories
CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données de test pour les catégories
INSERT INTO `categories` (`name`, `description`, `status`) VALUES
('Vêtements', 'Tous types de vêtements', 'active'),
('Chaussures', 'Tous types de chaussures', 'active'),
('Accessoires', 'Accessoires de mode', 'active');

-- Données de test pour les produits
INSERT INTO `products` (`name`, `description`, `price`, `commission_rate`, `category_id`, `status`) VALUES
('T-shirt Basic', 'T-shirt en coton de qualité', 199.00, 20.00, 1, 'active'),
('Jean Slim', 'Jean slim fit confortable', 399.00, 25.00, 1, 'active'),
('Baskets Sport', 'Baskets confortables pour le sport', 599.00, 30.00, 2, 'active'),
('Sac à Main', 'Sac à main élégant en cuir', 899.00, 35.00, 3, 'active');

-- Données de test pour les commandes
INSERT INTO `orders` (`affiliate_id`, `customer_name`, `customer_phone`, `city`, `address`, `total_amount`, `commission`, `status`) VALUES
(1, 'John Doe', '0600000000', 'Casablanca', '123 Rue Example', 598.00, 119.60, 'new'),
(1, 'Jane Smith', '0611111111', 'Rabat', '456 Avenue Test', 1198.00, 359.40, 'confirmed'),
(1, 'Alice Johnson', '0622222222', 'Marrakech', '789 Boulevard Demo', 899.00, 314.65, 'delivered');

-- Données de test pour les éléments de commande
INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price`, `commission`) VALUES
(1, 1, 2, 199.00, 39.80),
(1, 2, 1, 399.00, 79.80),
(2, 3, 2, 599.00, 179.70),
(3, 4, 1, 899.00, 314.65);

ALTER TABLE users
ADD COLUMN phone VARCHAR(30) DEFAULT NULL,
ADD COLUMN city VARCHAR(100) DEFAULT NULL;