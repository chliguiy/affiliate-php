-- Table des couleurs
CREATE TABLE IF NOT EXISTS colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    color_code VARCHAR(7) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des tailles
CREATE TABLE IF NOT EXISTS sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table de liaison produits-couleurs
CREATE TABLE IF NOT EXISTS product_colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    color_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_color (product_id, color_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table de liaison produits-tailles
CREATE TABLE IF NOT EXISTS product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_size (product_id, size_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;