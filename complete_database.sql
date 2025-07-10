-- =====================================================
-- BASE DE DONNÉES COMPLÈTE - CHIC AFFILIATE
-- =====================================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS chic_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chic_affiliate;

-- =====================================================
-- SUPPRESSION DES TABLES EXISTANTES (si elles existent)
-- =====================================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS claim_attachments;
DROP TABLE IF EXISTS claim_responses;
DROP TABLE IF EXISTS claims;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS product_colors;
DROP TABLE IF EXISTS product_sizes;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS bank_info;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admin_sessions;
DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS stock_movements;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- CRÉATION DES TABLES
-- =====================================================

-- Table des administrateurs
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'stock_manager', 'order_manager') DEFAULT 'admin',
    permissions JSON,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    last_password_change TIMESTAMP NULL,
    password_reset_token VARCHAR(100) NULL,
    password_reset_expires TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des logs administrateurs
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des sessions administrateurs
CREATE TABLE admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    type ENUM('affiliate', 'customer', 'vendor', 'admin') DEFAULT 'affiliate',
    status ENUM('active', 'inactive', 'pending', 'suspended') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des informations bancaires
CREATE TABLE bank_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bank_name ENUM('Attijariwafa Bank', 'CIH Bank', 'BMCI', 'Banque Populaire', 'Société Générale Maroc', 'CFG Bank', 'Al Barid Bank') NOT NULL,
    rib VARCHAR(24) NOT NULL,
    account_holder VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rib (rib)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des réinitialisations de mot de passe
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des catégories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    parent_id INT DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des produits
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    stock INT NOT NULL DEFAULT 0,
    min_stock_level INT NOT NULL DEFAULT 5,
    max_stock_level INT NOT NULL DEFAULT 100,
    reorder_point INT NOT NULL DEFAULT 10,
    sku VARCHAR(50) UNIQUE,
    barcode VARCHAR(50) UNIQUE,
    weight DECIMAL(10,2) DEFAULT 0.00,
    dimensions VARCHAR(50),
    category_id INT,
    vendor_id INT,
    image_url VARCHAR(255),
    commission_rate DECIMAL(5,2) DEFAULT 10.00,
    affiliate_visibility ENUM('all', 'specific') DEFAULT 'all',
    status ENUM('active', 'inactive', 'draft', 'out_of_stock') DEFAULT 'active',
    last_stock_update TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des images de produits
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    alt_text VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des couleurs de produits
CREATE TABLE product_colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    color_name VARCHAR(100) NOT NULL,
    color_code VARCHAR(7) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des tailles de produits
CREATE TABLE product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(20) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des mouvements de stock
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    type ENUM('in', 'out', 'adjustment', 'return') NOT NULL,
    reference_type ENUM('order', 'manual', 'return', 'adjustment') NOT NULL,
    reference_id INT,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des commandes
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    affiliate_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    customer_city VARCHAR(100) NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    total_amount DECIMAL(10,2) NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    shipping_cost DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('new', 'confirmed', 'unconfirmed', 'processing', 'shipped', 'delivered', 'returned', 'cancelled') NOT NULL DEFAULT 'new',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (affiliate_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des détails de commande
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    color VARCHAR(50),
    size VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des paiements
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('bank', 'cash') NOT NULL,
    rib VARCHAR(24),
    status ENUM('pending', 'paid', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des transactions
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('commission', 'withdrawal', 'refund') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    reference_id INT,
    reference_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des réclamations
CREATE TABLE claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('order', 'payment', 'technical', 'product', 'other') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('new', 'in-progress', 'resolved', 'closed') NOT NULL DEFAULT 'new',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des réponses aux réclamations
CREATE TABLE claim_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_id INT NOT NULL,
    user_id INT NOT NULL,
    response TEXT NOT NULL,
    is_admin_response BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des pièces jointes des réclamations
CREATE TABLE claim_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CRÉATION DES INDEX POUR OPTIMISER LES PERFORMANCES
-- =====================================================

-- Index pour les utilisateurs
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_type ON users(type);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_created_at ON users(created_at);

-- Index pour les produits
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_stock ON products(stock);
CREATE INDEX idx_products_created_at ON products(created_at);
CREATE INDEX idx_products_slug ON products(slug);

-- Index pour les commandes
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_affiliate ON orders(affiliate_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_orders_number ON orders(order_number);

-- Index pour les paiements
CREATE INDEX idx_payments_user ON payments(user_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_created_at ON payments(created_at);

-- Index pour les réclamations
CREATE INDEX idx_claims_user ON claims(user_id);
CREATE INDEX idx_claims_status ON claims(status);
CREATE INDEX idx_claims_type ON claims(type);
CREATE INDEX idx_claims_created_at ON claims(created_at);

-- Index pour les transactions
CREATE INDEX idx_transactions_user ON transactions(user_id);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_status ON transactions(status);
CREATE INDEX idx_transactions_created_at ON transactions(created_at);

-- =====================================================
-- CRÉATION DES VUES POUR LES STATISTIQUES
-- =====================================================

-- Vue des statistiques des commandes
CREATE VIEW order_stats AS
SELECT 
    affiliate_id,
    COUNT(*) as total_orders,
    SUM(total_amount) as total_amount,
    SUM(commission_amount) as total_commission,
    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
    COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_orders,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
    AVG(total_amount) as avg_order_value
FROM orders
GROUP BY affiliate_id;

-- Vue des statistiques des paiements
CREATE VIEW payment_stats AS
SELECT 
    user_id,
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
    COUNT(*) as total_requests,
    MAX(created_at) as last_payment_date
FROM payments
GROUP BY user_id;

-- Vue des statistiques des produits
CREATE VIEW product_stats AS
SELECT 
    p.id,
    p.name,
    p.price,
    p.stock,
    c.name as category_name,
    COUNT(oi.id) as total_orders,
    SUM(oi.quantity) as total_quantity_sold,
    SUM(oi.quantity * oi.price) as total_revenue
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ('delivered', 'confirmed')
GROUP BY p.id;

-- Vue des statistiques des réclamations
CREATE VIEW claim_stats AS
SELECT 
    user_id,
    COUNT(*) as total_claims,
    COUNT(CASE WHEN status = 'new' THEN 1 END) as new_claims,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_claims,
    AVG(CASE WHEN status = 'resolved' THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) END) as avg_resolution_time
FROM claims
GROUP BY user_id;

-- =====================================================
-- INSERTION DES DONNÉES INITIALES
-- =====================================================

-- Insertion de l'administrateur principal
INSERT INTO admins (username, email, password, full_name, role, status) VALUES
('admin', 'admin@chic-affiliate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur Principal', 'super_admin', 'active');

-- Insertion des catégories principales
INSERT INTO categories (name, slug, description, status) VALUES
('Électronique', 'electronique', 'Produits électroniques et gadgets', 'active'),
('Mode', 'mode', 'Vêtements et accessoires de mode', 'active'),
('Maison & Jardin', 'maison-jardin', 'Articles pour la maison et le jardin', 'active'),
('Sport & Loisirs', 'sport-loisirs', 'Équipements sportifs et de loisirs', 'active'),
('Beauté & Santé', 'beaute-sante', 'Produits de beauté et de santé', 'active'),
('Livres & Éducation', 'livres-education', 'Livres et matériel éducatif', 'active');

-- Insertion des sous-catégories
INSERT INTO categories (name, slug, description, parent_id, status) VALUES
('Smartphones', 'smartphones', 'Téléphones intelligents', 1, 'active'),
('Ordinateurs', 'ordinateurs', 'Ordinateurs portables et de bureau', 1, 'active'),
('Vêtements Hommes', 'vetements-hommes', 'Vêtements pour hommes', 2, 'active'),
('Vêtements Femmes', 'vetements-femmes', 'Vêtements pour femmes', 2, 'active'),
('Meubles', 'meubles', 'Meubles pour la maison', 3, 'active'),
('Décoration', 'decoration', 'Articles de décoration', 3, 'active');

-- Insertion des produits d'exemple
INSERT INTO products (name, slug, description, price, stock, category_id, commission_rate, status) VALUES
('iPhone 14 Pro', 'iphone-14-pro', 'Smartphone Apple iPhone 14 Pro 128GB', 12999.00, 50, 7, 15.00, 'active'),
('Samsung Galaxy S23', 'samsung-galaxy-s23', 'Smartphone Samsung Galaxy S23 256GB', 8999.00, 30, 7, 12.00, 'active'),
('MacBook Air M2', 'macbook-air-m2', 'Ordinateur portable Apple MacBook Air M2 13"', 15999.00, 20, 8, 18.00, 'active'),
('T-shirt Homme Premium', 't-shirt-homme-premium', 'T-shirt en coton bio pour homme', 299.00, 100, 9, 25.00, 'active'),
('Robe Élégante Femme', 'robe-elegante-femme', 'Robe élégante pour occasions spéciales', 599.00, 75, 10, 30.00, 'active'),
('Canapé Moderne', 'canape-moderne', 'Canapé 3 places design moderne', 3999.00, 15, 11, 20.00, 'active'),
('Lampe de Bureau LED', 'lampe-bureau-led', 'Lampe de bureau LED réglable', 199.00, 200, 12, 35.00, 'active'),
('Ballon de Football', 'ballon-football', 'Ballon de football professionnel', 299.00, 150, 4, 40.00, 'active'),
('Crème Hydratante', 'creme-hydratante', 'Crème hydratante visage 50ml', 149.00, 300, 5, 45.00, 'active'),
('Livre de Cuisine', 'livre-cuisine', 'Livre de recettes traditionnelles', 199.00, 80, 6, 50.00, 'active');

-- Insertion des images de produits
INSERT INTO product_images (product_id, image_url, is_primary, alt_text) VALUES
(1, 'uploads/products/iphone-14-pro-1.jpg', TRUE, 'iPhone 14 Pro'),
(1, 'uploads/products/iphone-14-pro-2.jpg', FALSE, 'iPhone 14 Pro - Vue arrière'),
(2, 'uploads/products/samsung-s23-1.jpg', TRUE, 'Samsung Galaxy S23'),
(3, 'uploads/products/macbook-air-m2-1.jpg', TRUE, 'MacBook Air M2'),
(4, 'uploads/products/t-shirt-homme-1.jpg', TRUE, 'T-shirt Homme Premium'),
(5, 'uploads/products/robe-femme-1.jpg', TRUE, 'Robe Élégante Femme'),
(6, 'uploads/products/canape-moderne-1.jpg', TRUE, 'Canapé Moderne'),
(7, 'uploads/products/lampe-bureau-1.jpg', TRUE, 'Lampe de Bureau LED'),
(8, 'uploads/products/ballon-football-1.jpg', TRUE, 'Ballon de Football'),
(9, 'uploads/products/creme-hydratante-1.jpg', TRUE, 'Crème Hydratante'),
(10, 'uploads/products/livre-cuisine-1.jpg', TRUE, 'Livre de Cuisine');

-- Insertion des couleurs pour certains produits
INSERT INTO product_colors (product_id, color_name, color_code, stock) VALUES
(1, 'Or', '#FFD700', 20),
(1, 'Argent', '#C0C0C0', 15),
(1, 'Noir', '#000000', 15),
(2, 'Noir', '#000000', 15),
(2, 'Blanc', '#FFFFFF', 15),
(4, 'Blanc', '#FFFFFF', 25),
(4, 'Noir', '#000000', 25),
(4, 'Bleu', '#0000FF', 25),
(4, 'Rouge', '#FF0000', 25),
(5, 'Rouge', '#FF0000', 25),
(5, 'Noir', '#000000', 25),
(5, 'Bleu', '#0000FF', 25);

-- Insertion des tailles pour certains produits
INSERT INTO product_sizes (product_id, size, stock) VALUES
(4, 'S', 25),
(4, 'M', 25),
(4, 'L', 25),
(4, 'XL', 25),
(5, 'XS', 25),
(5, 'S', 25),
(5, 'M', 25),
(5, 'L', 25),
(5, 'XL', 25);

-- Insertion d'utilisateurs affiliés d'exemple
INSERT INTO users (id, username, email, password, full_name, phone, address, city, type, status) VALUES
(1, 'admin', 'admin@chic-affiliate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', '0612345678', '123 Admin St', 'Casablanca', 'admin', 'active'),
(2, 'affiliate1', 'affiliate1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Affiliate User', '0623456789', '456 Affiliate St', 'Rabat', 'affiliate', 'active');

-- Insertion des informations bancaires
INSERT INTO bank_info (user_id, bank_name, rib, account_holder) VALUES
(2, 'Attijariwafa Bank', '007810000105000000000123', 'Affiliate User');

-- Insertion de paiements après les utilisateurs
INSERT INTO payments (user_id, amount, status, payment_method) VALUES
(2, 1000.00, 'pending', 'bank_transfer');

-- Insertion de transactions après les utilisateurs
INSERT INTO transactions (user_id, amount, type, status) VALUES
(2, 1000.00, 'commission', 'pending');

-- Insertion de commandes d'exemple
INSERT INTO orders (user_id, affiliate_id, customer_name, customer_email, customer_phone, customer_address, customer_city, order_number, total_amount, commission_amount, shipping_cost, status) VALUES
(2, 2, 'Karim Tazi', 'karim@example.com', '0645678901', '321 Rue Ibn Batouta', 'Casablanca', 'ORD-20241201-1001', 13222.00, 1322.20, 23.00, 'delivered'),
(3, 3, 'Amina Benjelloun', 'amina@example.com', '0656789012', '654 Avenue Hassan I', 'Rabat', 'ORD-20241201-1002', 922.00, 92.20, 37.00, 'confirmed'),
(2, 2, 'Youssef El Fassi', 'youssef@example.com', '0667890123', '987 Boulevard Mohammed VI', 'Marrakech', 'ORD-20241201-1003', 16236.00, 1623.60, 37.00, 'processing');

-- Insertion des détails de commande
INSERT INTO order_items (order_id, product_id, product_name, quantity, price, color, size) VALUES
(1, 1, 'iPhone 14 Pro', 1, 12999.00, 'Or', NULL),
(2, 4, 'T-shirt Homme Premium', 2, 299.00, 'Bleu', 'L'),
(2, 7, 'Lampe de Bureau LED', 1, 199.00, NULL, NULL),
(3, 3, 'MacBook Air M2', 1, 15999.00, NULL, NULL);

-- Insertion de paiements d'exemple
INSERT INTO payments (user_id, amount, payment_method, rib, status) VALUES
(2, 1322.20, 'bank', '001810000105300000123456', 'paid'),
(3, 92.20, 'bank', '002810000105300000234567', 'pending'),
(4, 1623.60, 'bank', '003810000105300000345678', 'pending');

-- Insertion de transactions d'exemple
INSERT INTO transactions (user_id, amount, type, status, reference_id, reference_type) VALUES
(2, 1322.20, 'commission', 'completed', 1, 'order'),
(3, 92.20, 'commission', 'pending', 2, 'order'),
(4, 1623.60, 'commission', 'pending', 3, 'order');

-- Insertion de réclamations d'exemple
INSERT INTO claims (user_id, type, subject, description, status, priority) VALUES
(2, 'order', 'Problème de livraison', 'Ma commande n\'est pas arrivée à la date prévue', 'in-progress', 'medium'),
(3, 'payment', 'Paiement en attente', 'Mon paiement est en attente depuis plus d\'une semaine', 'new', 'high');

-- Insertion de réponses aux réclamations
INSERT INTO claim_responses (claim_id, user_id, response, is_admin_response) VALUES
(1, 1, 'Nous avons contacté le transporteur pour suivre votre commande. Nous vous tiendrons informé.', TRUE),
(1, 2, 'Merci pour le suivi. J\'attends votre retour.', FALSE);

-- =====================================================
-- CRÉATION DES TRIGGERS POUR LA MAINTENANCE AUTOMATIQUE
-- =====================================================

-- Trigger pour mettre à jour le stock lors d'une commande
DELIMITER //
CREATE TRIGGER update_stock_after_order
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE products 
    SET stock = stock - NEW.quantity,
        last_stock_update = NOW()
    WHERE id = NEW.product_id;
END//
DELIMITER ;

-- Trigger pour mettre à jour le stock lors d'une annulation
DELIMITER //
CREATE TRIGGER restore_stock_after_cancellation
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
        UPDATE products p
        JOIN order_items oi ON p.id = oi.product_id
        SET p.stock = p.stock + oi.quantity,
            p.last_stock_update = NOW()
        WHERE oi.order_id = NEW.id;
    END IF;
END//
DELIMITER ;

-- Trigger pour créer une transaction lors d'une commande livrée
DELIMITER //
CREATE TRIGGER create_commission_transaction
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'delivered' AND OLD.status != 'delivered' THEN
        INSERT INTO transactions (user_id, amount, type, status, reference_id, reference_type)
        VALUES (NEW.affiliate_id, NEW.commission_amount, 'commission', 'completed', NEW.id, 'order');
    END IF;
END//
DELIMITER ;

-- =====================================================
-- CRÉATION DES PROCÉDURES STOCKÉES UTILES
-- =====================================================

-- Procédure pour calculer les commissions d'un affilié
DELIMITER //
CREATE PROCEDURE CalculateAffiliateCommissions(IN affiliate_id INT, IN start_date DATE, IN end_date DATE)
BEGIN
    SELECT 
        o.order_number,
        o.customer_name,
        o.total_amount,
        o.commission_amount,
        o.status,
        o.created_at
    FROM orders o
    WHERE o.affiliate_id = affiliate_id
    AND DATE(o.created_at) BETWEEN start_date AND end_date
    ORDER BY o.created_at DESC;
END//
DELIMITER ;

-- Procédure pour obtenir les produits en rupture de stock
DELIMITER //
CREATE PROCEDURE GetLowStockProducts(IN threshold INT)
BEGIN
    SELECT 
        p.id,
        p.name,
        p.stock,
        p.reorder_point,
        c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.stock <= threshold
    ORDER BY p.stock ASC;
END//
DELIMITER ;

-- Procédure pour nettoyer les sessions expirées
DELIMITER //
CREATE PROCEDURE CleanExpiredSessions()
BEGIN
    DELETE FROM admin_sessions 
    WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR);
END//
DELIMITER ;

-- =====================================================
-- CRÉATION DES ÉVÉNEMENTS POUR LA MAINTENANCE AUTOMATIQUE
-- =====================================================

-- Événement pour nettoyer les sessions expirées quotidiennement
CREATE EVENT IF NOT EXISTS clean_sessions_daily
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO CALL CleanExpiredSessions();

-- Événement pour nettoyer les tokens de réinitialisation expirés
CREATE EVENT IF NOT EXISTS clean_expired_tokens
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO DELETE FROM password_resets WHERE expires_at < NOW();

-- =====================================================
-- FIN DU SCRIPT
-- =====================================================

-- Message de confirmation
SELECT 'Base de données CHIC AFFILIATE créée avec succès !' as message;
SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = 'chic_affiliate';
SELECT 'Données insérées avec succès' as status;

-- Corriger la syntaxe des triggers
DELIMITER //

CREATE TRIGGER after_order_insert
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    UPDATE users 
    SET total_orders = total_orders + 1,
        total_sales = total_sales + NEW.total_amount
    WHERE id = NEW.affiliate_id;
END//

CREATE TRIGGER after_payment_insert
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    UPDATE users 
    SET total_earnings = total_earnings + NEW.amount
    WHERE id = NEW.user_id;
END//

DELIMITER ; 