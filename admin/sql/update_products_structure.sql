-- Ajout des nouvelles colonnes
ALTER TABLE products 
ADD COLUMN seller_price DECIMAL(10,2) NOT NULL AFTER description,
ADD COLUMN reseller_price DECIMAL(10,2) NOT NULL AFTER seller_price,
ADD COLUMN has_discount BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN discount_price DECIMAL(10,2) DEFAULT NULL AFTER has_discount,
ADD COLUMN affiliate_visibility ENUM('all', 'specific') DEFAULT 'all' AFTER discount_price;

-- Création de la table pour les affiliés spécifiques
CREATE TABLE IF NOT EXISTS product_affiliates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    affiliate_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (affiliate_id) REFERENCES affiliates(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_affiliate (product_id, affiliate_id)
);

-- Mise à jour de la table des couleurs
DROP TABLE IF EXISTS product_colors;
CREATE TABLE product_colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    color_code VARCHAR(7) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Mise à jour de la table des tailles
DROP TABLE IF EXISTS product_sizes;
CREATE TABLE product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(10) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Migration des données existantes
UPDATE products 
SET seller_price = price,
    reseller_price = price + ((price * commission_rate) / 100);

-- Suppression des anciennes colonnes
ALTER TABLE products 
DROP COLUMN price,
DROP COLUMN commission_rate; 