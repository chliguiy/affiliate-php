-- Script SQL ULTRA-SIMPLE pour corriger commission_rate
-- Copiez et exécutez ceci dans phpMyAdmin

-- 1. Ajouter commission_rate (ignore l'erreur si ça existe déjà)
ALTER TABLE products ADD COLUMN commission_rate DECIMAL(5,2) DEFAULT 10.00;

-- 2. Ajouter status (ignore l'erreur si ça existe déjà)
ALTER TABLE products ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active';

-- 3. Mettre à jour les prix à 0
UPDATE products SET price = 100.00 WHERE price = 0 OR price IS NULL;

-- 4. Mettre à jour les commissions à 0
UPDATE products SET commission_rate = 10.00 WHERE commission_rate = 0 OR commission_rate IS NULL;

-- 5. Activer tous les produits
UPDATE products SET status = 'active' WHERE status = 'inactive' OR status IS NULL;

-- 6. Vérifier le résultat
SELECT id, name, price, commission_rate, status FROM products LIMIT 5; 