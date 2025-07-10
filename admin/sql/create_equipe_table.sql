CREATE TABLE IF NOT EXISTS equipe (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    role ENUM('membre', 'confirmateur') NOT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
); 