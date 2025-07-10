<?php
/**
 * Script pour mettre à jour la gestion des sessions dans tous les fichiers
 */

echo "<h1>🔄 Mise à jour de la Gestion des Sessions</h1>";

// Liste des fichiers à mettre à jour
$files = [
    'index.php',
    'login.php',
    'register.php',
    'dashboard.php',
    'logout.php',
    'forgot-password.php',
    'reset-password.php',
    'products.php',
    'product_details.php',
    'orders.php',
    'payments.php',
    'claims.php',
    'categories.php',
    'search.php',
    'process_order.php',
    'vendor/dashboard.php',
    'vendor/add_product.php',
    'products/get_products.php',
    'orders/edit_order.php',
    'orders/change_status.php',
    'orders/get_order_items.php',
    'orders/change_products.php',
    'config/auth.php',
    'auth/login.php',
    'api/dashboard_data.php',
    'affiliate/sales.php',
    'affiliate/products.php',
    'affiliate/links.php',
    'affiliate/dashboard.php',
    'affiliate/commissions.php',
    'admin/users.php',
    'admin/sales.php',
    'admin/products_new.php',
    'admin/logout.php',
    'admin/index.php',
    'admin/export_affiliates.php',
    'admin/dashboard.php',
    'admin/commissions.php',
    'admin/includes/auth.php',
    'admin/affiliate_details.php',
    'admin/affiliates.php',
    'admin/products.php'
];

$updatedCount = 0;
$errorCount = 0;

foreach ($files as $file) {
    if (file_exists($file)) {
        try {
            $content = file_get_contents($file);
            $originalContent = $content;
            
            // Remplacer session_start() par l'inclusion du fichier de session
            if (strpos($content, 'session_start();') !== false) {
                // Supprimer session_start() s'il est sur sa propre ligne
                $content = preg_replace('/^\s*session_start\(\);\s*$/m', '', $content);
                
                // Supprimer session_start() s'il est avec d'autres instructions
                $content = preg_replace('/session_start\(\);\s*/', '', $content);
                
                // Ajouter l'inclusion du fichier de session au début
                $includeStatement = "require_once 'config/session.php';\n";
                
                // Si le fichier commence par <?php, ajouter après
                if (strpos($content, '<?php') === 0) {
                    $content = preg_replace('/^<\?php\s*/', "<?php\n$includeStatement", $content);
                } else {
                    $content = "<?php\n$includeStatement" . $content;
                }
                
                // Nettoyer les lignes vides multiples
                $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
                
                // Sauvegarder le fichier
                if (file_put_contents($file, $content)) {
                    echo "✅ Fichier '$file' mis à jour<br>";
                    $updatedCount++;
                } else {
                    echo "❌ Impossible de sauvegarder '$file'<br>";
                    $errorCount++;
                }
            } else {
                echo "ℹ️ Fichier '$file' n'utilise pas session_start()<br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Erreur lors du traitement de '$file': " . $e->getMessage() . "<br>";
            $errorCount++;
        }
    } else {
        echo "⚠️ Fichier '$file' n'existe pas<br>";
    }
}

echo "<h2>📊 Résumé</h2>";
echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>✅ Mise à jour terminée !</h3>";
echo "<p><strong>Résultats:</strong></p>";
echo "<ul>";
echo "<li>📁 Fichiers traités: " . count($files) . "</li>";
echo "<li>✅ Fichiers mis à jour: $updatedCount</li>";
echo "<li>❌ Erreurs: $errorCount</li>";
echo "</ul>";
echo "<p><strong>Avantages de la nouvelle gestion des sessions:</strong></p>";
echo "<ul>";
echo "<li>🔒 Sécurité renforcée (httponly, secure cookies)</li>";
echo "<li>⚠️ Plus de warnings de session</li>";
echo "<li>🔄 Gestion centralisée</li>";
echo "<li>🛡️ Protection contre les conflits</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🎯 Prochaines étapes</h2>";
echo "<ol>";
echo "<li>Testez votre application</li>";
echo "<li>Vérifiez que les sessions fonctionnent correctement</li>";
echo "<li>Les warnings de session devraient avoir disparu</li>";
echo "</ol>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    margin: 20px;
    background-color: #f5f5f5;
}

h1, h2, h3 {
    color: #333;
}

h1 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 30px;
}

h2 {
    background: #3498db;
    color: white;
    padding: 10px;
    border-radius: 5px;
    margin-top: 30px;
}

h3 {
    color: #2c3e50;
}

ul, ol {
    margin-left: 20px;
}

li {
    margin-bottom: 5px;
}
</style> 