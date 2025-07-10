<?php
require_once '../../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    $stmt = $conn->query("DESCRIBE users");
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo '<h2>Structure de la table users</h2>';
    echo '<table border="1" cellpadding="5"><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
    foreach ($fields as $field) {
        echo '<tr>';
        foreach ($field as $value) {
            echo '<td>' . htmlspecialchars($value) . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
} catch(PDOException $e) {
    echo "Erreur : " . $e->getMessage();
} 