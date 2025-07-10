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

// Récupération des statistiques de l'affilié
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
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Traitement de la mise à jour du profil
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $city = trim($_POST['city']);
    
    // Validation
    if (empty($full_name) || empty($email)) {
        $message = 'Le nom complet et l\'email sont obligatoires.';
        $message_type = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'L\'adresse email n\'est pas valide.';
        $message_type = 'danger';
    } else {
        try {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, city = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $city, $_SESSION['user_id']]);
            
            // Mettre à jour les données de session
            $user['full_name'] = $full_name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['city'] = $city;
            
            $message = 'Profil mis à jour avec succès !';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Erreur lors de la mise à jour du profil.';
            $message_type = 'danger';
        }
    }
}

// Traitement du changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'Tous les champs sont obligatoires.';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Les nouveaux mots de passe ne correspondent pas.';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 6) {
        $message = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
        $message_type = 'danger';
    } elseif (!password_verify($current_password, $user['password'])) {
        $message = 'Le mot de passe actuel est incorrect.';
        $message_type = 'danger';
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            
            $message = 'Mot de passe modifié avec succès !';
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = 'Erreur lors du changement de mot de passe.';
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - CHIC AFFILIATE</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #48bb78;
            --warning-color: #ed8936;
            --danger-color: #f56565;
            --info-color: #4299e1;
            --dark-color: #2d3748;
            --light-color: #f7fafc;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .profile-header {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: white;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
        }

        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .profile-role {
            color: #718096;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--primary-color);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #718096;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }

        .form-section h3 {
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .alert-custom {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white fw-bold">
                <i class="fas fa-user me-2"></i>
                Mon Profil
            </h2>
            <a href="dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>
                Retour au Dashboard
            </a>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-custom animate-fade-in" role="alert">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header animate-fade-in">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h1>
            <p class="profile-role">Affilié CHIC AFFILIATE</p>
            <div class="d-flex justify-content-center gap-2">
                <span class="badge bg-primary"><?php echo htmlspecialchars($user['status']); ?></span>
                <span class="badge bg-secondary">ID: <?php echo htmlspecialchars($user['id']); ?></span>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid animate-fade-in">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                <div class="stat-label">Total Commandes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['confirmed_orders'] ?? 0; ?></div>
                <div class="stat-label">Commandes Confirmées</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['delivered_orders'] ?? 0; ?></div>
                <div class="stat-label">Commandes Livrées</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_earnings'] ?? 0, 2); ?> Dhs</div>
                <div class="stat-label">Gains Totaux</div>
            </div>
        </div>

        <!-- Profile Information Form -->
        <div class="form-section animate-fade-in">
            <h3>
                <i class="fas fa-user-edit text-primary"></i>
                Informations Personnelles
            </h3>
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="full_name" class="form-label">Nom Complet</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="username" 
                               value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        <small class="text-muted">Le nom d'utilisateur ne peut pas être modifié</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="city" class="form-label">Ville</label>
                        <input type="text" class="form-control" id="city" name="city" 
                               value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="created_at" class="form-label">Date d'inscription</label>
                        <input type="text" class="form-control" id="created_at" 
                               value="<?php echo date('d/m/Y', strtotime($user['created_at'])); ?>" readonly>
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    Mettre à Jour le Profil
                </button>
            </form>
        </div>

        <!-- Change Password Form -->
        <div class="form-section animate-fade-in">
            <h3>
                <i class="fas fa-lock text-warning"></i>
                Changer le Mot de Passe
            </h3>
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="current_password" class="form-label">Mot de Passe Actuel</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="new_password" class="form-label">Nouveau Mot de Passe</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le Nouveau Mot de Passe</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" name="change_password" class="btn btn-warning">
                    <i class="fas fa-key me-2"></i>
                    Changer le Mot de Passe
                </button>
            </form>
        </div>

        <!-- Account Actions -->
        <div class="form-section animate-fade-in">
            <h3>
                <i class="fas fa-cog text-info"></i>
                Actions du Compte
            </h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="d-grid">
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-chart-line me-2"></i>
                            Retour au Dashboard
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-grid">
                        <a href="logout.php" class="btn btn-outline-danger" 
                           onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Se Déconnecter
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html> 