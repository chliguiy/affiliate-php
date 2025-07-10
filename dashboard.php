<?php
// Configuration de session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'],
        'path' => '/',
        'domain' => $cookieParams['domain'],
        'secure' => $cookieParams['secure'],
        'httponly' => $cookieParams['httponly'],
        'samesite' => $cookieParams['samesite'] ?? 'Lax',
    ]);
}
session_start();

// Vérification de sécurité
if (!isset($_SESSION['user_id']) || !isset($_SESSION['type']) || $_SESSION['type'] !== 'affiliate') {
    header('Location: login.php');
    exit();
}

require_once 'config/database.php';

// Récupération des informations utilisateur
$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND type = 'affiliate' AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Récupérer le message admin actif
$stmt = $conn->prepare("SELECT message, date_debut, date_fin FROM admin_messages WHERE is_active = 1 AND date_debut <= NOW() AND date_fin >= NOW() ORDER BY id DESC LIMIT 1");
$stmt->execute();
$pub_message = $stmt->fetch();

// Récupération des statistiques rapides pour l'affichage initial
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_orders,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(COALESCE(affiliate_margin, 0)) as total_earnings
    FROM orders 
    WHERE affiliate_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$quick_stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Affilié - CHIC AFFILIATE</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-color: #212529;
            --light-color: #f8f9fa;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light-color);
            min-height: 100vh;
            color: var(--dark-color);
        }

        .main-content {
            margin-left: 280px;
            padding: 0 1rem 0;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 0 1rem 0;
            }
        }

        .glass-card {
            background: #fff;
            backdrop-filter: none;
            border: 1px solid #e9ecef;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .stats-card {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stats-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #718096;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .stats-change {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .stats-change.positive {
            color: var(--success-color);
        }

        .stats-change.negative {
            color: var(--danger-color);
        }

        .card-new { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }
        .card-confirmed { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
        .card-delivered { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); }
        .card-earnings { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

        .date-filter {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .date-filter:hover {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
        }

        .product-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }

        .product-item:hover {
            background-color: #f7fafc;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            margin-right: 1rem;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .product-details {
            flex-grow: 1;
        }

        .product-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--dark-color);
        }

        .product-stats {
            font-size: 0.875rem;
            color: #718096;
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .loading-spinner.show {
            display: block;
        }

        .alert-custom {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
        }

        .welcome-section {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: #718096;
            font-size: 1.1rem;
        }

        .quick-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .quick-action-btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .quick-action-btn.primary {
            background: var(--primary-color);
            color: white;
        }

        .quick-action-btn.primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }

        .quick-action-btn.secondary {
            background: transparent;
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .quick-action-btn.secondary:hover {
            background: var(--primary-color);
            color: white;
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .dashboard-icons-bar {
            background: #fff;
            border-radius: 32px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.04);
            padding: 7px 18px 7px 14px;
            z-index: 100;
            border: 1px solid #e9ecef;
        }
        .dashboard-icons-bar a, .dashboard-icons-bar i {
            font-size: 1.18rem;
            color: var(--primary-color);
            transition: color 0.2s, background 0.2s, box-shadow 0.2s;
            border-radius: 50%;
            padding: 6px;
            background: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dashboard-icons-bar a:hover, .dashboard-icons-bar i:hover {
            background: #e9ecef;
            color: var(--success-color);
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
        }
        .dashboard-icons-bar .fa-bell {
            color: #fff;
            position: relative;
        }
        .dashboard-icons-bar .badge {
            position: absolute;
            top: 0px;
            right: -2px;
            font-size: 0.65rem;
            background: #ff5e5e;
            color: #fff;
            border-radius: 8px;
            padding: 1px 5px;
            font-weight: 600;
            box-shadow: none;
            border: 1px solid #fff;
        }
        .dashboard-icons-bar .dropdown-whatsapp {
            position: relative;
            display: inline-block;
        }
        .dashboard-icons-bar .dropdown-menu-whatsapp {
            display: none;
            position: absolute;
            top: 36px;
            right: 0;
            min-width: 220px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(44,62,80,0.10);
            padding: 10px 0;
            z-index: 200;
            border: 1px solid #f0f0f0;
        }
        .dashboard-icons-bar .dropdown-whatsapp.open .dropdown-menu-whatsapp {
            display: block;
        }
        .dashboard-icons-bar .dropdown-menu-whatsapp a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 22px;
            color: #222;
            text-decoration: none;
            font-size: 1rem;
            transition: background 0.15s;
        }
        .dashboard-icons-bar .dropdown-menu-whatsapp a:hover {
            background: #f5f8ff;
        }
        .dashboard-icons-bar .dropdown-menu-whatsapp i {
            font-size: 1.2rem;
            min-width: 22px;
            text-align: center;
        }
        .announcement-bar {
            width: 100%;
            background: linear-gradient(90deg, #e0e7ff 0%, #f0f4ff 100%);
            color: #222;
            font-size: 1.15rem;
            font-weight: 600;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            padding: 0.7rem 0.5rem 0.7rem 1.5rem;
            display: flex;
            align-items: center;
            overflow: hidden;
            position: relative;
            min-height: 48px;
        }
        .announcement-bar i {
            color: #4f46e5;
            font-size: 1.3rem;
            margin-right: 12px;
        }
        .announcement-text {
            display: inline-block;
            white-space: nowrap;
            animation: marquee 14s linear infinite;
            padding-left: 1rem;
        }
        @keyframes marquee {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        @media (max-width: 768px) {
            .announcement-bar { font-size: 1rem; padding: 0.5rem 0.5rem 0.5rem 1rem; }
            .announcement-text { padding-left: 0.5rem; }
        }
        .stats-card .stats-icon {
            background: #e9ecef !important;
            color: var(--primary-color) !important;
        }
    </style>
</head>
<body>
<?php include 'includes/topbar.php'; ?>
<!-- Include Sidebar -->
<?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Section -->
        <div class="welcome-section animate-fade-in">
            <h1 class="welcome-title">
                <i class="fas fa-chart-line me-3"></i>
                Bienvenue, <?php echo htmlspecialchars($user['username']); ?> !
            </h1>
            <p class="welcome-subtitle">Voici un aperçu de vos performances affiliées</p>
            
            <div class="quick-actions">
                <button class="btn btn-primary" onclick="window.location.href='products.php'">Voir les Produits</button>
                <button class="btn btn-outline-primary" onclick="window.location.href='orders.php'">Mes Commandes</button>
            </div>
        </div>
        <?php if (!empty($pub_message) && !empty($pub_message['message'])): ?>
            <div class="announcement-bar mb-4 animate-fade-in">
                <i class="fas fa-bullhorn me-2"></i>
                <span class="announcement-text">
                    <?= nl2br(htmlspecialchars($pub_message['message'])) ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- Date Filter -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-white fw-bold">
                <i class="fas fa-calendar-alt me-2"></i>
                Analyse des Performances
            </h4>
            <div class="date-filter">
                <i class="fas fa-calendar me-2"></i>
                <span id="date-range">30 derniers jours</span>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loading-spinner">
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="text-light mt-2">Chargement des données...</p>
        </div>

        <!-- Stats Cards -->
        <div class="row" id="stats-container">
            <div class="col-lg-3 col-md-6">
                <div class="glass-card stats-card card-new">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stats-title">Total Commandes</div>
                    <div class="stats-value" id="total-orders"><?php echo $quick_stats['total_orders'] ?? 0; ?></div>
                    <div class="stats-change positive" id="total-orders-change">+0% ce mois</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="glass-card stats-card card-confirmed">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-title">Commandes Confirmées</div>
                    <div class="stats-value" id="confirmed-orders"><?php echo $quick_stats['confirmed_orders'] ?? 0; ?></div>
                    <div class="stats-change positive" id="confirmed-orders-change">+0% ce mois</div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="glass-card stats-card card-delivered">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stats-title">Commandes Livrées</div>
                    <div class="stats-value" id="delivered-orders"><?php echo $quick_stats['delivered_orders'] ?? 0; ?></div>
                    <div class="stats-change positive" id="delivered-orders-change">+0% ce mois</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="glass-card stats-card card-earnings">
                    <div class="stats-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stats-title">Gains Totaux</div>
                    <div class="stats-value" id="total-earnings"><?php echo number_format($quick_stats['total_earnings'] ?? 0, 2); ?> Dhs</div>
                    <div class="stats-change positive" id="total-earnings-change">+0% ce mois</div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                        Top Villes (30 derniers jours)
                    </h5>
                    <div id="cities-chart" style="height: 300px;"></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-container">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Évolution des Commandes (30 derniers jours)
                    </h5>
                    <div id="orders-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="chart-container">
            <h5 class="mb-3">
                <i class="fas fa-star me-2 text-primary"></i>
                Top 6 Produits les Plus Vendus
            </h5>
            <div id="top-products-container">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <p>Chargement des produits...</p>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="chart-container">
            <h5 class="mb-3">
                <i class="fas fa-clock me-2 text-primary"></i>
                Commandes Récentes
            </h5>
            <div id="recent-orders-container">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                    <p>Chargement des commandes récentes...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js"></script>
    
    <script>
        let citiesChart, ordersChart;
        let currentStartDate, currentEndDate;

        $(document).ready(function() {
            // Initialiser le sélecteur de dates
            $('.date-filter').daterangepicker({
                startDate: moment().subtract(30, 'days'),
                endDate: moment(),
                ranges: {
                    'Aujourd\'hui': [moment(), moment()],
                    'Hier': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    '7 derniers jours': [moment().subtract(6, 'days'), moment()],
                    '30 derniers jours': [moment().subtract(29, 'days'), moment()],
                    'Ce mois': [moment().startOf('month'), moment().endOf('month')],
                    'Mois dernier': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                locale: {
                    format: 'DD/MM/YYYY',
                    applyLabel: 'Appliquer',
                    cancelLabel: 'Annuler',
                    customRangeLabel: 'Période personnalisée',
                    daysOfWeek: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
                    monthNames: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
                }
            }, function(start, end) {
                $('#date-range').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                currentStartDate = start.format('YYYY-MM-DD');
                currentEndDate = end.format('YYYY-MM-DD');
                loadDashboardData(currentStartDate, currentEndDate);
            });

            // Initialiser les graphiques
            initializeCharts();

            // Charger les données initiales
            currentStartDate = moment().subtract(30, 'days').format('YYYY-MM-DD');
            currentEndDate = moment().format('YYYY-MM-DD');
            loadDashboardData(currentStartDate, currentEndDate);

            // Auto-refresh toutes les 5 minutes
            setInterval(function() {
                loadDashboardData(currentStartDate, currentEndDate);
            }, 300000);
        });

        function initializeCharts() {
            // Graphique des villes
            const citiesOptions = {
                series: [{
                    data: [0, 0, 0, 0, 0, 0]
                }],
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        horizontal: true,
                        distributed: true,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                colors: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe'],
                dataLabels: {
                    enabled: true,
                    textAnchor: 'start',
                    style: {
                        colors: ['#fff']
                    },
                    formatter: function (val, opt) {
                        return val
                    },
                    offsetX: 0
                },
                xaxis: {
                    categories: ['Aucune donnée'],
                },
                yaxis: {
                    labels: {
                        show: false
                    }
                },
                legend: {
                    show: false
                }
            };
            citiesChart = new ApexCharts(document.querySelector("#cities-chart"), citiesOptions);
            citiesChart.render();

            // Graphique des commandes
            const ordersOptions = {
                series: [{
                    name: 'Commandes',
                    data: Array.from({length: 30}, () => 0)
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                xaxis: {
                    type: 'datetime',
                    categories: Array.from({length: 30}, (_, i) => {
                        return moment().subtract(29-i, 'days').format('YYYY-MM-DD');
                    })
                },
                tooltip: {
                    x: {
                        format: 'dd/MM/yyyy'
                    }
                },
                colors: ['#667eea'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3,
                        stops: [0, 100],
                        colorStops: []
                    }
                }
            };
            ordersChart = new ApexCharts(document.querySelector("#orders-chart"), ordersOptions);
            ordersChart.render();
        }

        function showLoading() {
            $('#loading-spinner').addClass('show');
            $('#stats-container').addClass('animate__animated animate__fadeOut');
        }

        function hideLoading() {
            $('#loading-spinner').removeClass('show');
            $('#stats-container').removeClass('animate__animated animate__fadeOut').addClass('animate__animated animate__fadeIn');
        }

        function loadDashboardData(startDate, endDate) {
            showLoading();
            
            $.ajax({
                url: 'api/dashboard_data.php',
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate
                },
                timeout: 10000,
                success: function(response) {
                    hideLoading();
                    
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            console.error('Erreur de parsing JSON', e, response);
                            showError('Erreur lors du chargement des données');
                            return;
                        }
                    }
                
                console.log('Données reçues:', response);
                
                if (response.error) {
                    showError(response.error);
                    return;
                }
                
                    // Mettre à jour les statistiques
                if (response.stats) {
                        updateStats(response.stats);
                    }
                
                    // Mettre à jour les graphiques
                if (response.charts) {
                        updateCharts(response.charts);
                    }
                
                    // Mettre à jour les produits
                if (response.top_products) {
                        updateTopProducts(response.top_products);
                    }
                
                // Mettre à jour les commandes récentes
                if (response.recent_orders) {
                    updateRecentOrders(response.recent_orders);
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                console.error('Erreur AJAX:', error);
                console.error('Réponse:', xhr.responseText);
                
                if (status === 'timeout') {
                    showError('Délai d\'attente dépassé. Veuillez réessayer.');
                } else if (xhr.status === 401) {
                    showError('Session expirée. Veuillez vous reconnecter.');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    showError('Erreur lors du chargement des données. Veuillez réessayer.');
                }
                }
            });
        }

        function updateStats(stats) {
            const updates = {
                'total-orders': {
                    value: stats.total_orders || 0,
                    change: stats.total_orders_change || '+0% ce mois'
                },
                'confirmed-orders': {
                    value: stats.confirmed_orders || 0,
                    change: stats.confirmed_orders_change || '+0% ce mois'
                },
                'delivered-orders': {
                    value: stats.delivered_orders || 0,
                    change: stats.delivered_orders_change || '+0% ce mois'
                },
                'total-earnings': {
                    value: (Number(stats.total_commission) || 0).toFixed(2) + ' Dhs',
                    change: stats.total_commission_change || '+0% ce mois'
                }
            };

            Object.entries(updates).forEach(([id, data]) => {
                const element = document.getElementById(id);
                const changeElement = document.getElementById(id + '-change');
                
                if (element) {
                    element.textContent = data.value;
                    element.classList.add('pulse');
                    setTimeout(() => element.classList.remove('pulse'), 1000);
                }
                
                if (changeElement) {
                    changeElement.textContent = data.change;
                    changeElement.className = 'stats-change ' + (data.change.includes('+') ? 'positive' : 'negative');
                }
            });
        }

        function updateCharts(charts) {
            // Mettre à jour le graphique des villes
            if (charts.cities && charts.cities.length > 0) {
                const citiesData = charts.cities.map(item => item.total);
                const citiesLabels = charts.cities.map(item => item.customer_city);
                
                citiesChart.updateOptions({
                    series: [{
                        data: citiesData
                    }],
                    xaxis: {
                        categories: citiesLabels
                    }
                });
            }

            // Mettre à jour le graphique des commandes
            if (charts.orders && charts.orders.length > 0) {
                const ordersData = charts.orders.map(item => item.total);
                const ordersDates = charts.orders.map(item => item.date);
                
                ordersChart.updateOptions({
                    series: [{
                        name: 'Commandes',
                        data: ordersData
                    }],
                    xaxis: {
                        categories: ordersDates
                    }
                });
            }
        }

        function updateTopProducts(products) {
            const container = $('#top-products-container');
            
            if (!products || products.length === 0) {
                container.html(`
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-box-open fa-2x mb-2"></i>
                        <p>Aucun produit vendu pour cette période</p>
                    </div>
                `);
                return;
            }
            
            let html = '<div class="row">';
            products.forEach((product, index) => {
                const imageUrl = product.image_url || 'assets/images/no-image.png';
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="product-item glass-card">
                            <img src="${imageUrl}" alt="${product.name}" class="product-image" 
                                 onerror="this.src='assets/images/no-image.png'">
                            <div class="product-details">
                                <div class="product-name">${product.name}</div>
                                <div class="product-stats">
                                    <i class="fas fa-shopping-cart me-1"></i>
                                    ${product.total_orders} commande${product.total_orders > 1 ? 's' : ''}
                                </div>
                            </div>
                            <div class="ms-auto">
                                <span class="badge bg-primary">#${index + 1}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            container.html(html);
        }

        function updateRecentOrders(orders) {
            const container = $('#recent-orders-container');
            
            if (!orders || orders.length === 0) {
                container.html(`
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>Aucune commande récente</p>
                    </div>
                `);
                return;
            }
            
            let html = '<div class="table-responsive"><table class="table table-hover">';
            html += `
                <thead class="table-light">
                    <tr>
                        <th>N° Commande</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
            `;
            
            orders.forEach(order => {
                const statusClass = getStatusClass(order.status);
                const statusIcon = getStatusIcon(order.status);
                
                html += `
                    <tr>
                        <td><strong>${order.order_number}</strong></td>
                        <td>${order.customer_name}</td>
                        <td><strong>${parseFloat(order.total_amount).toFixed(2)} Dhs</strong></td>
                        <td>
                            <span class="badge ${statusClass}">
                                <i class="${statusIcon} me-1"></i>
                                ${getStatusText(order.status)}
                            </span>
                        </td>
                        <td>${moment(order.created_at).format('DD/MM/YYYY HH:mm')}</td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            container.html(html);
        }

        function getStatusClass(status) {
            const classes = {
                'new': 'bg-warning',
                'confirmed': 'bg-info',
                'shipped': 'bg-primary',
                'delivered': 'bg-success',
                'returned': 'bg-danger',
                'cancelled': 'bg-secondary'
            };
            return classes[status] || 'bg-secondary';
        }

        function getStatusIcon(status) {
            const icons = {
                'new': 'fas fa-clock',
                'confirmed': 'fas fa-check',
                'shipped': 'fas fa-truck',
                'delivered': 'fas fa-check-circle',
                'returned': 'fas fa-undo',
                'cancelled': 'fas fa-times'
            };
            return icons[status] || 'fas fa-question';
        }

        function getStatusText(status) {
            const texts = {
                'new': 'Nouvelle',
                'confirmed': 'Confirmée',
                'shipped': 'Expédiée',
                'delivered': 'Livrée',
                'returned': 'Retournée',
                'cancelled': 'Annulée'
            };
            return texts[status] || status;
        }

        function showError(message) {
            const alertHtml = `
                <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('.main-content').prepend(alertHtml);
            
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }
    </script>
</body>
</html> 