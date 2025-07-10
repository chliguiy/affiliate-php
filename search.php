<?php
session_start();
require_once 'config/database.php';

$searchTerm = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Simuler une recherche dans la base de données
// À remplacer par votre vraie logique de recherche
$query = "SELECT * FROM products WHERE name LIKE :search OR description LIKE :search LIMIT :limit OFFSET :offset";
$countQuery = "SELECT COUNT(*) FROM products WHERE name LIKE :search OR description LIKE :search";

try {
    $stmt = $pdo->prepare($query);
    $searchParam = "%{$searchTerm}%";
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $countStmt->execute();
    $totalResults = $countStmt->fetchColumn();
    $totalPages = ceil($totalResults / $perPage);
} catch(PDOException $e) {
    // En cas d'erreur, initialiser avec des valeurs par défaut
    $results = [];
    $totalResults = 0;
    $totalPages = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche - CHIC AFFILIATE</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --text-color: #333;
            --bg-color: #fff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color);
        }

        .search-result-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            background: var(--bg-color);
            position: relative;
        }

        .search-result-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1;
            border-radius: 10px;
        }

        .search-result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .search-result-card:hover::before {
            opacity: 0.1;
        }

        .product-image {
            height: 200px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .search-result-card:hover .product-image {
            transform: scale(1.05);
        }

        .card-body {
            position: relative;
            z-index: 2;
        }

        .btn-primary {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        .btn-primary:hover::before {
            width: 200px;
            height: 200px;
        }

        .navbar {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9) !important;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .search-input-wrapper {
            position: relative;
            max-width: 500px;
            width: 100%;
        }

        .search-input {
            transition: all 0.3s ease;
        }

        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .search-stats {
            opacity: 0;
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pagination .page-link {
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .no-results {
            opacity: 0;
            animation: fadeIn 0.8s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .loading .loading-overlay {
            opacity: 1;
            visibility: visible;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid var(--secondary-color);
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php" data-aos="fade-right">
                <i class="fas fa-handshake"></i> CHIC AFFILIATE
            </a>
            <form class="d-flex mx-auto search-input-wrapper" action="search.php" method="GET" data-aos="fade-up">
                <div class="input-group">
                    <input type="search" 
                           class="form-control search-input" 
                           name="q" 
                           value="<?php echo $searchTerm; ?>" 
                           placeholder="Rechercher des produits..."
                           autocomplete="off">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </nav>

    <!-- Search Results -->
    <div class="container" style="margin-top: 100px;">
        <?php if (!empty($searchTerm)): ?>
            <div class="search-results">
                <?php if ($totalResults > 0): ?>
                    <h2 class="mb-4">Résultats de recherche pour "<?php echo htmlspecialchars($searchTerm); ?>"</h2>
                    <p class="text-muted mb-4"><?php echo $totalResults; ?> résultat(s) trouvé(s)</p>
                    
                    <div class="row">
                        <?php foreach ($results as $product): ?>
                            <div class="col-md-4 mb-4" data-aos="fade-up">
                                <div class="product-card">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="product-image">
                                    <div class="product-details">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="price"><?php echo number_format($product['price'], 2); ?> DH</span>
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                                Voir plus
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Navigation des pages" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?q=<?php echo urlencode($searchTerm); ?>&page=<?php echo ($page - 1); ?>">
                                            Précédent
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?q=<?php echo urlencode($searchTerm); ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?q=<?php echo urlencode($searchTerm); ?>&page=<?php echo ($page + 1); ?>">
                                            Suivant
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center" data-aos="fade-up">
                        <h2>Aucun résultat trouvé pour "<?php echo htmlspecialchars($searchTerm); ?>"</h2>
                        <p class="text-muted">Essayez avec d'autres mots-clés</p>
                        <a href="index.php" class="btn btn-primary mt-3">Retour à l'accueil</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-center" data-aos="fade-up">
                <h2>Veuillez entrer un terme de recherche</h2>
                <a href="index.php" class="btn btn-primary mt-3">Retour à l'accueil</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        // Initialisation des animations AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        // Animation de la barre de navigation au défilement
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Animation de chargement
        document.addEventListener('DOMContentLoaded', () => {
            document.body.classList.add('loading');
            setTimeout(() => {
                document.body.classList.remove('loading');
            }, 500);
        });

        // Animation des liens de pagination
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                document.body.classList.add('loading');
            });
        });
    </script>
</body>
</html> 