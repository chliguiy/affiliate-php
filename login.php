<?php
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
require_once 'config/database.php';

// Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord approprié
if (isset($_SESSION['user_id']) && isset($_SESSION['type']) && $_SESSION['type'] === 'affiliate') {
    header('Location: dashboard.php');
    exit();
}

if (isset($_SESSION['confirmateur_id'])) {
    header('Location: confirmateur/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    try {
        // 1. Vérifier d'abord dans la table users (clients, affiliés, admins)
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Vérifier si le compte est actif
            if ($user['status'] === 'active') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['type'] = $user['type'];
                $_SESSION['success'] = "Connexion réussie !";
                header('Location: dashboard.php');
                exit();
            } elseif ($user['status'] === 'suspended') {
                $error = "Votre compte affilié est suspendu. Veuillez contacter l'administrateur.";
            } elseif ($user['status'] === 'deleted') {
                $error = "Votre compte affilié a été supprimé. Contactez le support si besoin.";
            } else {
                $error = "Votre compte est en attente d'activation.";
            }
        } else {
            // 2. Si pas trouvé dans users, vérifier dans la table equipe (confirmateurs)
            $stmt = $conn->prepare("SELECT * FROM equipe WHERE email = ? AND role = 'confirmateur'");
            $stmt->execute([$email]);
            $confirmateur = $stmt->fetch();
            
            if ($confirmateur && password_verify($password, $confirmateur['password'])) {
                $_SESSION['confirmateur_id'] = $confirmateur['id'];
                $_SESSION['confirmateur_nom'] = $confirmateur['nom'];
                $_SESSION['success'] = "Connexion confirmateur réussie !";
                header('Location: confirmateur/dashboard.php');
                exit();
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        }
    } catch (PDOException $e) {
        $error = "Erreur de connexion : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - CHIC AFFILIATE</title>
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

        .login-container {
            max-width: 400px;
            margin: 100px auto;
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
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

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
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

        .form-check-input:checked {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .login-container {
                margin: 50px auto;
            }
            
            .card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="text-center mb-4">
                    <img src="assets/images/logo.png" alt="CHIC AFFILIATE" class="logo">
                    <h2>Connexion</h2>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>

                <div class="text-center mt-3">
                    <p class="mb-1">
                        <a href="forgot-password.php">Mot de passe oublié ?</a>
                    </p>
                    <p class="mb-0">Pas encore de compte ? 
                        <a href="register.php">S'inscrire</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 