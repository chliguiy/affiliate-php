-- Script SQL pour corriger la table products
-- Exécutez ce script dans votre base de données

-- 1. Ajouter la colonne commission_rate si elle n'existe pas
ALTER TABLE products ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5,2) DEFAULT 10.00;

-- 2. Ajouter la colonne status si elle n'existe pas
ALTER TABLE products ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';

-- 3. Ajouter la colonne created_at si elle n'existe pas
ALTER TABLE products ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 4. Ajouter la colonne updated_at si elle n'existe pas
ALTER TABLE products ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 5. Mettre à jour les prix à 0 vers 100 MAD
UPDATE products SET price = 100.00 WHERE price = 0 OR price IS NULL;

-- 6. Mettre à jour les commissions à 0 vers 10%
UPDATE products SET commission_rate = 10.00 WHERE commission_rate = 0 OR commission_rate IS NULL;

-- 7. Activer tous les produits inactifs
UPDATE products SET status = 'active' WHERE status = 'inactive' OR status IS NULL;

-- 8. Vérifier la structure finale
DESCRIBE products;

-- 9. Vérifier les données
SELECT id, name, price, commission_rate, status FROM products LIMIT 5; 