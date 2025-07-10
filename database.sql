-- Création de la base de données
CREATE DATABASE IF NOT EXISTS chic_affiliate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chic_affiliate;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    type ENUM('affiliate', 'vendor', 'admin') NOT NULL,
    status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des informations bancaires
CREATE TABLE bank_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    bank_name ENUM('Attijariwafa Bank', 'CIH Bank', 'BMCI') NOT NULL,
    rib VARCHAR(24) NOT NULL,
    account_holder VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rib (rib)
);

-- Table des catégories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des produits
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    commission_rate DECIMAL(5,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Table des images de produits
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Table des commandes
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    affiliate_id INT NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    client_phone VARCHAR(20) NOT NULL,
    client_address TEXT NOT NULL,
    client_city VARCHAR(100) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    status ENUM('new', 'confirmed', 'unconfirmed', 'delivery', 'distributed', 'shipped', 'delivered', 'returned') NOT NULL DEFAULT 'new',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (affiliate_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des détails de commande
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Table des paiements
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('bank') NOT NULL,
    rib VARCHAR(24) NOT NULL,
    status ENUM('pending', 'paid', 'rejected') NOT NULL DEFAULT 'pending',
    notes TEXT,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table des réclamations
CREATE TABLE claims (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('order', 'payment', 'technical', 'other') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('new', 'in-progress', 'resolved', 'closed') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table des réponses aux réclamations
CREATE TABLE claim_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_id INT NOT NULL,
    user_id INT NOT NULL,
    response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table des pièces jointes des réclamations
CREATE TABLE claim_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES claims(id) ON DELETE CASCADE
);

-- Index pour améliorer les performances
CREATE INDEX idx_orders_affiliate ON orders(affiliate_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_payments_user ON payments(user_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_claims_user ON claims(user_id);
CREATE INDEX idx_claims_status ON claims(status);

-- Vues pour les statistiques
CREATE VIEW order_stats AS
SELECT 
    affiliate_id,
    COUNT(*) as total_orders,
    SUM(total_amount) as total_amount,
    SUM(commission_amount) as total_commission,
    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
    COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_orders
FROM orders
GROUP BY affiliate_id;

CREATE VIEW payment_stats AS
SELECT 
    user_id,
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid,
    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
    COUNT(*) as total_requests
FROM payments
GROUP BY user_id; 