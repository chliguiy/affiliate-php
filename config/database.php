<?php
require_once 'app.php';

class Database {
    private $host = "localhost:8889";
    private $db_name = "chic_affiliate";
    private $username = "root";
    private $password = "root";
    private $conn = null;

    public function getConnection() {
        try {
            if ($this->conn === null) {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            }
            return $this->conn;
        } catch(PDOException $e) {
            error_log("Erreur de connexion à la base de données : " . $e->getMessage());
            return null;
        }
    }
}
?>