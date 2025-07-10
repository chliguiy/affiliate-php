-- Ajout des colonnes pour la couleur et la taille
ALTER TABLE orders
ADD COLUMN color VARCHAR(7) AFTER quantity,
ADD COLUMN size VARCHAR(10) AFTER color;
 
-- Mise à jour des commandes existantes (si nécessaire)
UPDATE orders SET color = '#000000', size = 'M' WHERE color IS NULL; 