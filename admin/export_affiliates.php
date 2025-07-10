<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    die('Accès refusé.');
}

require_once '../config/database.php';

$host = 'localhost';
$db   = 'chic_affiliate';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// Récupérer les affiliés
$affiliates = $pdo->query("
    SELECT 
        u.username,
        u.email,
        u.phone,
        u.city,
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.final_sale_price), 0) as total_sales,
        COALESCE(SUM(oi.commission), 0) as total_commission,
        COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as delivered_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'cancelled' THEN o.id END) as cancelled_orders
    FROM users u
    LEFT JOIN orders o ON o.affiliate_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE u.type = 'affiliate'
    GROUP BY u.id, u.username, u.email, u.phone, u.city
    ORDER BY total_sales DESC
")->fetchAll();

$format = $_GET['format'] ?? 'csv';

$headers = [
    'Nom', 'Email', 'Téléphone', 'Ville', 'Commandes', 'Ventes (MAD)', 'Commissions (MAD)', 'Taux de livraison'
];

// Calculer le taux de livraison pour chaque affilié
foreach ($affiliates as &$a) {
    $total_orders = $a['delivered_orders'] + $a['cancelled_orders'];
    $a['delivery_rate'] = $total_orders > 0 ? round(($a['delivered_orders'] / $total_orders) * 100, 1) . '%' : '0%';
}
unset($a);

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="affiliates.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($affiliates as $a) {
        fputcsv($output, [
            $a['username'], $a['email'], $a['phone'], $a['city'],
            $a['total_orders'], number_format($a['total_sales'], 2), number_format($a['total_commission'], 2), $a['delivery_rate']
        ]);
    }
    fclose($output);
    exit;
}

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="affiliates.xls"');
    echo "<table border='1'><tr>";
    foreach ($headers as $h) echo "<th>".htmlspecialchars($h)."</th>";
    echo "</tr>";
    foreach ($affiliates as $a) {
        echo "<tr>";
        echo "<td>".htmlspecialchars($a['username'])."</td>";
        echo "<td>".htmlspecialchars($a['email'])."</td>";
        echo "<td>".htmlspecialchars($a['phone'])."</td>";
        echo "<td>".htmlspecialchars($a['city'])."</td>";
        echo "<td>".htmlspecialchars($a['total_orders'])."</td>";
        echo "<td>".number_format($a['total_sales'], 2)."</td>";
        echo "<td>".number_format($a['total_commission'], 2)."</td>";
        echo "<td>".htmlspecialchars($a['delivery_rate'])."</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

if ($format === 'pdf') {
    require_once '../vendor/autoload.php';
    $html = '<h2>Liste des Affiliés</h2><table border="1" cellpadding="5" cellspacing="0"><tr>';
    foreach ($headers as $h) $html .= '<th style="background:#eee">'.htmlspecialchars($h).'</th>';
    $html .= '</tr>';
    foreach ($affiliates as $a) {
        $html .= '<tr>';
        $html .= '<td>'.htmlspecialchars($a['username']).'</td>';
        $html .= '<td>'.htmlspecialchars($a['email']).'</td>';
        $html .= '<td>'.htmlspecialchars($a['phone']).'</td>';
        $html .= '<td>'.htmlspecialchars($a['city']).'</td>';
        $html .= '<td>'.htmlspecialchars($a['total_orders']).'</td>';
        $html .= '<td>'.number_format($a['total_sales'], 2).'</td>';
        $html .= '<td>'.number_format($a['total_commission'], 2).'</td>';
        $html .= '<td>'.htmlspecialchars($a['delivery_rate']).'</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($html);
    $mpdf->Output('affiliates.pdf', 'D');
    exit;
}

echo 'Format non supporté.'; 