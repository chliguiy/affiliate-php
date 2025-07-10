<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once 'config/database.php';

// Créer une instance de la base de données
$database = new Database();
$conn = $database->getConnection();

// Si la connexion échoue, afficher un message d'erreur
if (!$conn) {
    die("Erreur de connexion à la base de données");
}

// Traiter les actions (comme la suppression)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ? AND affiliate_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    header('Location: orders.php');
    exit;
}

// Inclure l'en-tête
require_once 'includes/header.php';
?>

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body>
<?php include 'includes/topbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Commandes</h1>
            <a href="new_order.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvelle Commande
            </a>
        </div>

        <?php
        // Inclure la liste des commandes
        require_once 'orders/all.php';
        ?>
    </div>
</body>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?> 