CREATE TABLE IF NOT EXISTS product_colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    color_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_color (product_id, color_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 