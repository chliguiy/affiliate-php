CREATE TABLE IF NOT EXISTS product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (size_id) REFERENCES sizes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_size (product_id, size_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 