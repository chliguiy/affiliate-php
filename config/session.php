<?php
/**
 * Gestion sécurisée des sessions
 * Ce fichier doit être inclus au début de chaque page qui nécessite une session
 */

// Inclure la configuration de l'application
require_once __DIR__ . '/app.php';

// Fonction pour démarrer une session de manière sécurisée
function startSecureSession() {
    // Vérifier si la session n'est pas déjà démarrée
    if (session_status() === PHP_SESSION_NONE) {
        // Configurer les paramètres de session AVANT de démarrer
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        
        // Démarrer la session
        session_start();
    }
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
}

// Fonction pour vérifier si l'utilisateur est administrateur
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

// Fonction pour vérifier si l'utilisateur est affilié
function isAffiliate() {
    return isset($_SESSION['user_id']);
}

// Fonction pour obtenir l'ID de l'utilisateur connecté
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
}

// Fonction pour obtenir le type d'utilisateur
function getUserType() {
    if (isset($_SESSION['admin_id'])) {
        return 'admin';
    } elseif (isset($_SESSION['user_id'])) {
        return 'affiliate';
    }
    return null;
}

// Fonction pour rediriger si non connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Fonction pour rediriger si non administrateur
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../login.php');
        exit();
    }
}

// Fonction pour rediriger si non affilié
function requireAffiliate() {
    if (!isAffiliate()) {
        header('Location: ../login.php');
        exit();
    }
}

// Démarrer la session de manière sécurisée
startSecureSession();
?> 