<?php
// Test de l'API dashboard_data.php
echo "Test de l'API dashboard_data.php:\n";

// Simuler une session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Simuler les paramètres GET
$_GET['start_date'] = '2024-01-01';
$_GET['end_date'] = '2025-12-31';

// Inclure l'API
ob_start();
include 'api/dashboard_data.php';
$response = ob_get_clean();

echo "Réponse de l'API:\n";
echo $response;

// Tester le parsing JSON
$data = json_decode($response, true);
if ($data) {
    echo "\n\nDonnées parsées:\n";
    print_r($data);
} else {
    echo "\n\nErreur de parsing JSON\n";
    echo "Erreur JSON: " . json_last_error_msg() . "\n";
}
?> 