<?php
session_start();
require_once 'config/database.php';

// Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Récupération et nettoyage des données
    $fullName = htmlspecialchars($_POST['fullName'], ENT_QUOTES, 'UTF-8');
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $address = htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8');
    $bankName = htmlspecialchars($_POST['bankName'], ENT_QUOTES, 'UTF-8');
    $rib = htmlspecialchars($_POST['rib'], ENT_QUOTES, 'UTF-8');
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validation
    if (empty($fullName) || empty($email) || empty($address) || empty($bankName) || empty($rib) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (!preg_match('/^\d{24}$/', $rib)) {
        $error = "Le RIB doit contenir exactement 24 chiffres.";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Cet email est déjà utilisé.";
            } else {
                // Générer un username unique basé sur le nom complet
                $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($fullName));
                $username = $baseUsername;
                $counter = 1;
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                while (true) {
                    $stmt->execute([$username]);
                    if (!$stmt->fetch()) break;
                    $username = $baseUsername . $counter;
                    $counter++;
                }

                // Insertion dans la base de données
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, address, bank_name, rib, password, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
                $stmt->execute([$username, $fullName, $email, $address, $bankName, $rib, $hashedPassword]);

                if ($stmt->rowCount() > 0) {
                    $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                    // Redirection après 3 secondes
                    header("refresh:3;url=login.php");
                } else {
                    $error = "Erreur lors de l'inscription.";
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur de base de données : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - CHIC AFFILIATE</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #1f2937;
            --text-color: #333;
            --bg-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }

        body {
            background: var(--bg-gradient);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            position: relative;
            margin: 0;
            padding: 20px;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233498db' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }

        .register-container {
            max-width: 600px;
            margin: 30px auto;
            position: relative;
            z-index: 1;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
            padding: 2rem;
        }

        .logo {
            max-width: 150px;
            margin: 0 auto 2rem;
            display: block;
            filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.2));
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            background-color: #ffffff;
        }

        .btn-primary {
            background: linear-gradient(to right, #2c3e50, #3498db);
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(to right, #3498db, #2c3e50);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .rib-info {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: linear-gradient(to right, #28a745, #20c997);
            color: white;
        }

        .alert-danger {
            background: linear-gradient(to right, #dc3545, #c82333);
            color: white;
        }

        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        a {
            color: var(--secondary-color);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        a:hover {
            color: var(--primary-color);
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* Style personnalisé pour le select */
        .form-select {
            background-image: linear-gradient(45deg, transparent 50%, #3498db 50%),
                            linear-gradient(135deg, #3498db 50%, transparent 50%);
            background-position: calc(100% - 20px) calc(1em + 2px),
                               calc(100% - 15px) calc(1em + 2px);
            background-size: 5px 5px,
                           5px 5px;
            background-repeat: no-repeat;
        }

        /* Style pour les champs disabled */
        .form-control:disabled,
        .form-control[readonly] {
            background-color: #f8f9fa;
            opacity: 0.8;
        }

        /* Animation de chargement */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading::after {
            content: '';
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Effet de survol sur les cartes */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        @media (max-width: 768px) {
            .register-container {
                margin: 10px auto;
            }
            
            .card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="card p-4">
                <div class="text-center mb-4">
                    <img src="assets/images/logo.png" alt="CHIC AFFILIATE" class="logo">
                    <h2>Inscription</h2>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" id="registerForm">
                    <div class="mb-3">
                        <label for="fullName" class="form-label">Nom complet</label>
                        <input type="text" class="form-control" id="fullName" name="fullName" required 
                               value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Adresse</label>
                        <textarea class="form-control" id="address" name="address" rows="2" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="bankName" class="form-label">Choisir votre banque</label>
                        <select class="form-select" id="bankName" name="bankName" required>
                            <option value="">-- Sélectionnez une banque --</option>
                            <option value="attijariwafa" <?php echo (isset($_POST['bankName']) && $_POST['bankName'] == 'attijariwafa') ? 'selected' : ''; ?>>Attijariwafa Bank</option>
                            <option value="cih" <?php echo (isset($_POST['bankName']) && $_POST['bankName'] == 'cih') ? 'selected' : ''; ?>>CIH Bank</option>
                            <option value="bmci" <?php echo (isset($_POST['bankName']) && $_POST['bankName'] == 'bmci') ? 'selected' : ''; ?>>BMCI</option>
                            <option value="bp" <?php echo (isset($_POST['bankName']) && $_POST['bankName'] == 'bp') ? 'selected' : ''; ?>>Banque Populaire</option>
                            <option value="sgmb" <?php echo (isset($_POST['bankName']) && $_POST['bankName'] == 'sgmb') ? 'selected' : ''; ?>>Société Générale Maroc</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="rib" class="form-label">RIB (24 chiffres)</label>
                        <input type="text" class="form-control" id="rib" name="rib" pattern="[0-9]{24}" maxlength="24" required
                               value="<?php echo isset($_POST['rib']) ? htmlspecialchars($_POST['rib']) : ''; ?>">
                        <div class="rib-info mt-1">Format : 24 chiffres sans espaces</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Créer un mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required
                               minlength="8">
                        <div class="form-text">Minimum 8 caractères</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
                </form>

                <div class="text-center mt-3">
                    <p class="mb-0">Déjà inscrit ? 
                        <a href="login.php">Se connecter</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation en temps réel du RIB
        document.getElementById('rib').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^\d]/g, '').slice(0, 24);
        });

        // Validation du formulaire
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const rib = document.getElementById('rib').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return;
            }

            if (rib.length !== 24) {
                e.preventDefault();
                alert('Le RIB doit contenir exactement 24 chiffres.');
                return;
            }
        });
    </script>
</body>
</html> 