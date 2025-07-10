CREATE TABLE IF NOT EXISTS confirmateur_paiements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    confirmateur_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (confirmateur_id) REFERENCES equipe(id) ON DELETE CASCADE
); 