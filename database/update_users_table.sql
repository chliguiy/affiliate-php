-- Ajout des colonnes manquantes à la table users si elles n'existent pas déjà
SET @dbname = DATABASE();

-- Ajout de full_name
SET @exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'full_name'
);
SET @query = IF(@exists = 0,
    'ALTER TABLE users ADD COLUMN full_name VARCHAR(255) NOT NULL AFTER id',
    'SELECT "Column full_name already exists"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajout de type
SET @exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'type'
);
SET @query = IF(@exists = 0,
    'ALTER TABLE users ADD COLUMN type ENUM("admin", "affiliate") NOT NULL DEFAULT "affiliate" AFTER full_name',
    'SELECT "Column type already exists"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajout de address
SET @exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'address'
);
SET @query = IF(@exists = 0,
    'ALTER TABLE users ADD COLUMN address TEXT NOT NULL AFTER email',
    'SELECT "Column address already exists"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajout de bank_name
SET @exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'bank_name'
);
SET @query = IF(@exists = 0,
    'ALTER TABLE users ADD COLUMN bank_name VARCHAR(50) NOT NULL AFTER address',
    'SELECT "Column bank_name already exists"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajout de rib
SET @exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'rib'
);
SET @query = IF(@exists = 0,
    'ALTER TABLE users ADD COLUMN rib VARCHAR(24) NOT NULL AFTER bank_name',
    'SELECT "Column rib already exists"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajout de created_at
SET @exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'created_at'
);
SET @query = IF(@exists = 0,
    'ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'SELECT "Column created_at already exists"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajout de phone
SET @exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'phone'
);
SET @query = IF(@exists = 0,
    'ALTER TABLE users ADD COLUMN phone VARCHAR(30) DEFAULT NULL AFTER rib',
    'SELECT "Column phone already exists"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajout de city
SET @exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'city'
);
SET @query = IF(@exists = 0,
    'ALTER TABLE users ADD COLUMN city VARCHAR(100) DEFAULT NULL AFTER phone',
    'SELECT "Column city already exists"'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 