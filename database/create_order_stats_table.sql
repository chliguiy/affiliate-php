-- Création de la table order_stats
CREATE TABLE IF NOT EXISTS order_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    affiliate_id INT NOT NULL,
    total_orders INT DEFAULT 0,
    delivered_orders INT DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    total_commission DECIMAL(10,2) DEFAULT 0.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (affiliate_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 