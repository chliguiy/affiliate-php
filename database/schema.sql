-- Création de la base de données
CREATE DATABASE IF NOT EXISTS chic_affiliate;
USE chic_affiliate;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'affiliate') NOT NULL,
    status ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
    bank ENUM('CIH', 'ATW', 'BMCE'),
    rib VARCHAR(24),
    address TEXT,
    phone VARCHAR(30) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des tokens de connexion persistante
CREATE TABLE remember_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des réinitialisations de mot de passe
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des produits
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    commission_rate DECIMAL(5,2) NOT NULL,
    image_url VARCHAR(255),
    category VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des ventes
CREATE TABLE sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    affiliate_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    commission DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (affiliate_id) REFERENCES users(id)
);

-- Table des commissions
CREATE TABLE commissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    affiliate_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (affiliate_id) REFERENCES users(id)
);

-- Table des liens d'affiliation
CREATE TABLE affiliate_links (
    id INT PRIMARY KEY AUTO_INCREMENT,
    affiliate_id INT NOT NULL,
    product_id INT NOT NULL,
    unique_code VARCHAR(50) NOT NULL UNIQUE,
    clicks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (affiliate_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
); 