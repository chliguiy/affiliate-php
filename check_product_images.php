<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "<h2>Description de la table product_images</h2>";

try {
    $result = $conn->query("DESCRIBE product_images");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>"; // Handle null default
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Impossible de décrire la table product_images. Elle n'existe peut-être pas.</p>";
    }
    
    echo "<h2>Contenu de la table product_images (5 premières lignes)</h2>";
    $result = $conn->query("SELECT * FROM product_images LIMIT 5");
    if ($result) {
        echo "<table border='1'>";
        $firstRow = $result->fetch(PDO::FETCH_ASSOC);
        if ($firstRow) {
            echo "<tr>";
            foreach ($firstRow as $key => $value) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            
            // Reset cursor for all rows
            $result = $conn->query("SELECT * FROM product_images LIMIT 5");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='100%'>Aucune donnée dans la table product_images.</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Impossible de récupérer le contenu de la table product_images.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur de base de données : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ccc; }
    th { background-color: #f2f2f2; }
</style> 