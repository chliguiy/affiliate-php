-- Script SQL pour corriger IMMÉDIATEMENT le problème commission_rate
-- Exécutez ce script dans phpMyAdmin ou via la ligne de commande

-- 1. Vérifier si la colonne existe et l'ajouter si nécessaire
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'products' 
     AND COLUMN_NAME = 'commission_rate') > 0,
    'SELECT "commission_rate existe déjà" as message',
    'ALTER TABLE products ADD COLUMN commission_rate DECIMAL(5,2) DEFAULT 10.00'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Vérifier si la colonne status existe et l'ajouter si nécessaire
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'products' 
     AND COLUMN_NAME = 'status') > 0,
    'SELECT "status existe déjà" as message',
    'ALTER TABLE products ADD COLUMN status ENUM("active", "inactive") DEFAULT "active"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Mettre à jour les prix à 0
UPDATE products SET price = 100.00 WHERE price = 0 OR price IS NULL;

-- 4. Mettre à jour les commissions à 0
UPDATE products SET commission_rate = 10.00 WHERE commission_rate = 0 OR commission_rate IS NULL;

-- 5. Activer tous les produits
UPDATE products SET status = 'active' WHERE status = 'inactive' OR status IS NULL;

-- 6. Vérifier le résultat
SELECT id, name, price, commission_rate, status FROM products LIMIT 5; 