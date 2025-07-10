<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config/database.php';

// Récupérer les informations de l'utilisateur
$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réclamations - CHIC AFFILIATE</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2196F3;
            --secondary-color: #64B5F6;
            --accent-color: #1976D2;
            --background-color: #F5F5F5;
            --card-background: rgba(255, 255, 255, 0.9);
            --sidebar-bg: rgba(33, 150, 243, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--background-color) 0%, #FFFFFF 100%);
            min-height: 100vh;
        }

        .glass-effect {
            background: var(--card-background);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: var(--sidebar-bg);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem 1rem;
            z-index: 1000;
        }

        .main-content {
            margin-left: 280px;
            padding: 0 1rem 0;
        }

        .nav-link {
            color: var(--accent-color) !important;
            padding: 0.8rem 1rem;
            margin: 0.2rem 0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--primary-color) !important;
            color: white !important;
            transform: translateX(5px);
        }

        .claims-list {
            margin-top: 2rem;
        }

        .claim-item {
            background: var(--card-background);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .claim-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(31, 38, 135, 0.15);
        }

        .claim-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .claim-title {
            font-weight: 600;
            color: var(--accent-color);
            font-size: 1.1rem;
        }

        .claim-date {
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        .claim-content {
            margin-bottom: 1.5rem;
            color: #666;
            line-height: 1.6;
        }

        .claim-response {
            background: rgba(33, 150, 243, 0.05);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid rgba(33, 150, 243, 0.1);
        }

        .claim-response-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--accent-color);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-new { background-color: #FFF3E0; color: #E65100; }
        .status-in-progress { background-color: #E3F2FD; color: #1565C0; }
        .status-resolved { background-color: #E8F5E9; color: #2E7D32; }
        .status-closed { background-color: #FAFAFA; color: #616161; }

        .search-box {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background: white;
        }

        .btn-action {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.2rem;
            background: var(--background-color);
            border: none;
            color: var(--accent-color);
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        /* Animation pour les éléments */
        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
<?php include 'includes/topbar.php'; ?>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">Gestion des Réclamations</h2>
            
            <!-- Contenu de la page des réclamations ici -->
            
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 