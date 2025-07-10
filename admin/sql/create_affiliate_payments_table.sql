CREATE TABLE IF NOT EXISTS affiliate_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    affiliate_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en attente', 'payé') DEFAULT 'en attente',
    FOREIGN KEY (affiliate_id) REFERENCES users(id) ON DELETE CASCADE
); 