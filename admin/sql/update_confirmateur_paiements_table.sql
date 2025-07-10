ALTER TABLE confirmateur_paiements
ADD COLUMN statut ENUM('en_attente', 'paye') NOT NULL DEFAULT 'en_attente' AFTER montant; 