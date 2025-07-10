<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_status = $_GET['status'] ?? '';
?>

<style>
    :root {
        --sidebar-bg1: #e0e0e0;
        --sidebar-bg2: #e0e0e0;
        --sidebar-color: #212529;
        --sidebar-accent: #007bff;
    }
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 260px;
        background: var(--sidebar-bg1);
        color: var(--sidebar-color);
        padding: 0 0 2rem 0;
        z-index: 1000;
        box-shadow: 2px 0 16px rgba(44,62,80,0.04);
        display: flex;
        flex-direction: column;
    }
    .sidebar-header {
        padding: 2.2rem 0 1.2rem 0;
        text-align: center;
        font-size: 1.5rem;
        font-weight: bold;
        letter-spacing: 1px;
        color: var(--sidebar-color);
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
    }
    .sidebar .nav {
        flex-direction: column;
        gap: 0.2rem;
        width: 100%;
    }
    .sidebar .nav-link {
        color: var(--sidebar-color) !important;
        padding: 0.85rem 2rem 0.85rem 1.5rem;
        border-radius: 8px 0 0 8px;
        margin: 0.1rem 0;
        font-size: 1.08rem;
        opacity: 0.92;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        font-weight: 500;
        border-left: 3px solid transparent;
        background: none;
    }
    .sidebar .nav-link i {
        margin-right: 12px;
        font-size: 1.1rem;
        min-width: 22px;
        text-align: center;
    }
    .sidebar .nav-link.active, .sidebar .nav-link:hover {
        background: #e9ecef !important;
        color: var(--sidebar-accent) !important;
        opacity: 1;
        border-left: 3px solid var(--sidebar-accent);
        font-weight: 600;
        transform: translateX(4px);
    }
    .sidebar .submenu {
        padding-left: 2.5rem;
        margin-bottom: 0.2rem;
    }
    .sidebar .submenu .nav-link {
        font-size: 0.98rem;
        padding: 0.5rem 1.5rem;
        border-radius: 6px 0 0 6px;
        opacity: 0.8;
        border-left: 2px solid transparent;
    }
    .sidebar .submenu .nav-link.active, .sidebar .submenu .nav-link:hover {
        background: rgba(255,255,255,0.13) !important;
        border-left: 2px solid #fff;
        opacity: 1;
        font-weight: 500;
    }
    .sidebar .logout {
        margin-top: auto;
        color: #fff !important;
        font-weight: 600;
        opacity: 0.9;
    }
    .sidebar .logout:hover {
        color: #fc5c7d !important;
        background: rgba(255,255,255,0.13) !important;
    }
</style>

<div class="sidebar">
    <div class="sidebar-header">CHIC AFFILIATE</div>
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <div class="nav-item">
            <a href="orders.php" class="nav-link <?php echo $current_page === 'orders' && empty($current_status) ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Commandes
            </a>
            <div class="submenu">
                <a href="orders.php?status=new" class="nav-link <?php echo $current_page === 'orders' && $current_status === 'new' ? 'active' : ''; ?>">Nouvelle</a>
                <a href="orders.php?status=unconfirmed" class="nav-link <?php echo $current_page === 'orders' && $current_status === 'unconfirmed' ? 'active' : ''; ?>">Non confirmé</a>
                <a href="orders.php?status=confirmed" class="nav-link <?php echo $current_page === 'orders' && $current_status === 'confirmed' ? 'active' : ''; ?>">Confirmé</a>
                <a href="orders.php?status=shipping" class="nav-link <?php echo $current_page === 'orders' && $current_status === 'shipping' ? 'active' : ''; ?>">En livraison</a>
                <a href="orders.php?status=delivered" class="nav-link <?php echo $current_page === 'orders' && $current_status === 'delivered' ? 'active' : ''; ?>">Livré</a>
                <a href="orders.php?status=returned" class="nav-link <?php echo $current_page === 'orders' && $current_status === 'returned' ? 'active' : ''; ?>">Retourné</a>
                <a href="orders.php?status=refused" class="nav-link <?php echo $current_page === 'orders' && $current_status === 'refused' ? 'active' : ''; ?>">Refusé</a>
                <a href="orders.php?status=cancelled" class="nav-link <?php echo $current_page === 'orders' && $current_status === 'cancelled' ? 'active' : ''; ?>">Annulé</a>
                <a href="orders.php?status=duplicate" class="nav-link <?php echo ($_GET['status'] ?? '') === 'duplicate' ? 'active' : ''; ?>"><i class="fas fa-copy"></i> Dupliqué</a>
                <a href="orders.php?status=changed" class="nav-link <?php echo ($_GET['status'] ?? '') === 'changed' ? 'active' : ''; ?>"><i class="fas fa-exchange-alt"></i> Changé</a>
            </div>
        </div>
        <a href="categories.php" class="nav-link <?php echo $current_page === 'categories' ? 'active' : ''; ?>">
            <i class="fas fa-folder"></i> Catégories
        </a>
        <a href="products.php" class="nav-link <?php echo $current_page === 'products' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Produits
        </a>
        <a href="payments.php" class="nav-link <?php echo $current_page === 'payments' ? 'active' : ''; ?>">
            <i class="fas fa-wallet"></i> Paiements
        </a>
        <a href="claims.php" class="nav-link <?php echo $current_page === 'claims' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-triangle"></i> Réclamations
        </a>
        <a href="logout.php" class="nav-link logout mt-auto">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </nav>
</div>

<!-- HTML du menu latéral affilié ici --> 