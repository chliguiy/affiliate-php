<?php
// Configuration de l'application
class AppConfig {
    // Configuration des erreurs
    public static function setErrorReporting() {
        $serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';

        if ($serverName === 'localhost' || $serverName === '127.0.0.1') {
            // Mode développement
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        } else {
            // Mode production
            error_reporting(0);
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
        }
    }
    
    // Configuration des sessions
    public static function configureSession() {
        // Configuration de sécurité des sessions AVANT de démarrer la session
        if (session_status() === PHP_SESSION_NONE) {
            // Définir les paramètres de session avant session_start()
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            
            // Démarrer la session
            session_start();
        }
    }
    
    // Fonction sécurisée pour démarrer une session
    public static function startSecureSession() {
        // Vérifier si la session n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            // Configurer les paramètres de session
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            
            // Démarrer la session
            session_start();
        }
    }
    
    // Fonction de nettoyage des données
    public static function sanitizeInput($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    // Fonction de validation d'email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    // Fonction de validation de RIB
    public static function validateRIB($rib) {
        return preg_match('/^\d{24}$/', $rib);
    }
    
    // Configuration des constantes
    const APP_NAME = 'CHIC AFFILIATE';
    const APP_VERSION = '1.0.0';
    const COMMISSION_RATE = 0.10; // 10%
    const SHIPPING_COST_CASABLANCA = 23;
    const SHIPPING_COST_OTHER = 37;
}

// Appliquer la configuration
AppConfig::setErrorReporting();
// Ne pas démarrer automatiquement la session ici pour éviter les conflits
// AppConfig::configureSession();
?> 