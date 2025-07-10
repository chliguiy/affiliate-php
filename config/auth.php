<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database.php';

function checkAuth() {
    global $conn;
    
    // Vérifier si l'utilisateur est déjà connecté
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Vérifier le token de connexion persistante
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Vérifier si le token existe et n'est pas expiré
        $stmt = $conn->prepare("
            SELECT u.* FROM users u
            INNER JOIN remember_tokens rt ON u.id = rt.user_id
            WHERE rt.token = ? AND rt.expires_at > NOW() AND u.status = 'active'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Mettre à jour la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Générer un nouveau token
            $new_token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Mettre à jour le token dans la base de données
            $stmt = $conn->prepare("
                UPDATE remember_tokens 
                SET token = ?, expires_at = ? 
                WHERE token = ?
            ");
            $stmt->execute([$new_token, $expires, $token]);
            
            // Mettre à jour le cookie
            setcookie('remember_token', $new_token, strtotime('+30 days'), '/', '', true, true);
            
            return true;
        }
        
        // Supprimer le cookie si le token est invalide
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
    
    return false;
}

function requireAuth() {
    if (!checkAuth()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole($role) {
    requireAuth();
    
    if ($_SESSION['role'] !== $role) {
        header('Location: /index.php');
        exit;
    }
}

function logout() {
    global $conn;
    
    // Supprimer le token de connexion persistante
    if (isset($_COOKIE['remember_token'])) {
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ?");
        $stmt->execute([$_COOKIE['remember_token']]);
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
    
    // Détruire la session
    session_destroy();
    
    // Rediriger vers la page de connexion
    header('Location: /login.php');
    exit;
} 