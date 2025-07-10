<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Inclure la classe AdminPermissions et initialiser les permissions
// Cela suppose que la session est déjà démarrée et que la connexion DB est disponible
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AdminPermissions.php';

// Assurez-vous que l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    // Gérer le cas où l'admin n'est pas connecté
    // Peut-être rediriger vers la page de connexion
    // Pour l'instant, on crée un objet avec des permissions vides
    $permissions = new class {
        public function __call($name, $arguments) {
            return false;
        }
    };
} else {
    $database = new Database();
    $pdo = $database->getConnection();
    $permissions = new AdminPermissions($pdo, $_SESSION['admin_id']);
}
?>

<style>
    .admin-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 280px;
        background: #2c3e50;
        color: #ecf0f1;
        padding: 1rem 0;
        z-index: 1000;
    }

    .admin-sidebar .nav-link {
        color: #ecf0f1 !important;
        padding: 0.8rem 1.5rem;
        opacity: 0.8;
        transition: all 0.3s;
    }

    .admin-sidebar .nav-link:hover,
    .admin-sidebar .nav-link.active {
        opacity: 1;
        background: rgba(255, 255, 255, 0.1);
    }

    .admin-sidebar .nav-link i {
        width: 24px;
        text-align: center;
        margin-right: 8px;
    }

    .admin-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 1rem;
    }

    .admin-header h1 {
        font-size: 1.5rem;
        margin: 0;
        color: #ecf0f1;
    }

    .admin-header p {
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.8;
    }

    .nav-section {
        margin-bottom: 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 1rem;
    }

    .nav-section:last-child {
        border-bottom: none;
    }

    .nav-section-title {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #95a5a6;
        padding: 0.5rem 1.5rem;
        margin-bottom: 0.5rem;
    }
</style>

<div class="col-md-3 col-lg-2 sidebar p-3">
    <h4 class="mb-4">Admin Panel</h4>
    <ul class="nav flex-column">
        <?php if ($permissions->canViewDashboard()): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-home me-2"></i> Tableau de bord
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : ''; ?>" href="messages.php">
                <i class="fas fa-envelope me-2"></i> Message
            </a>
        </li>
        <?php endif; ?>
        <?php if ($permissions->canManageUsers()): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>" href="users.php">
                <i class="fas fa-users me-2"></i> Utilisateurs
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'affiliates.php' || basename($_SERVER['PHP_SELF']) === 'affiliate_details.php') ? 'active' : ''; ?>" href="affiliates.php">
                <i class="fas fa-user-tie me-2"></i> Affiliés
            </a>
        </li>
        <?php endif; ?>
        <?php if ($permissions->canManageOrders()): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                <i class="fas fa-shopping-cart me-2"></i> Commandes
            </a>
        </li>
        <?php endif; ?>
        <?php if ($permissions->canManageOrders()): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'affiliate_orders.php' ? 'active' : ''; ?>" href="affiliate_orders.php">
                <i class="fas fa-list-alt me-2"></i> Affiliés & Commandes
            </a>
        </li>
        <?php endif; ?>
        <?php if ($permissions->canManageAdmins()): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'manage_admins.php' ? 'active' : ''; ?>" href="manage_admins.php">
                <i class="fas fa-user-shield me-2"></i> Gestion des Admins
            </a>
        </li>
        <?php endif; ?>
        <?php if ($permissions->canManageStock()): ?>
        <li class="nav-item">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="collapse" data-bs-target="#stockSubmenu" 
               <?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'categories.php', 'colors.php', 'sizes.php']) ? 'active' : ''; ?>>
                <i class="fas fa-box me-2"></i> Gestion du stock
            </a>
            <div class="collapse <?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'categories.php', 'colors.php', 'sizes.php']) ? 'show' : ''; ?>" id="stockSubmenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                            <i class="fas fa-folder me-2"></i> Catégories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'colors.php' ? 'active' : ''; ?>" href="colors.php">
                            <i class="fas fa-palette me-2"></i> Couleurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'sizes.php' ? 'active' : ''; ?>" href="sizes.php">
                            <i class="fas fa-ruler me-2"></i> Tailles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>" href="products.php">
                            <i class="fas fa-box me-2"></i> Produits
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="collapse" data-bs-target="#equipeSubmenu"
               <?php echo in_array(basename($_SERVER['PHP_SELF']), ['equipe_membres.php', 'equipe_confirmateurs.php']) ? 'active' : ''; ?>>
                <i class="fas fa-users-cog me-2"></i> Équipe
            </a>
            <div class="collapse <?php echo in_array(basename($_SERVER['PHP_SELF']), ['equipe_membres.php', 'equipe_confirmateurs.php']) ? 'show' : ''; ?>" id="equipeSubmenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'equipe_membres.php' ? 'active' : ''; ?>" href="equipe_membres.php">
                            <i class="fas fa-user-friends me-2"></i> Liste des membres
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'equipe_confirmateurs.php' ? 'active' : ''; ?>" href="equipe_confirmateurs.php">
                            <i class="fas fa-user-check me-2"></i> Liste des confirmateurs
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <?php endif; ?>
        <?php if ($permissions->canViewReports()): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'sales.php' ? 'active' : ''; ?>" href="sales.php">
                <i class="fas fa-chart-line me-2"></i> Ventes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'pay_affiliates.php' ? 'active' : ''; ?>" href="pay_affiliates.php">
                <i class="fas fa-money-check-alt me-2"></i> Paiement Affiliés
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
            </a>
        </li>
    </ul>
</div> 