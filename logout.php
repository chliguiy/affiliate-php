<?php
session_start();

// Vérifier si c'est un confirmateur qui se déconnecte
$is_confirmateur = isset($_SESSION['confirmateur_id']);

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Rediriger vers la page appropriée
if ($is_confirmateur) {
    header('Location: login.php');
} else {
    header('Location: index.php');
}
exit();
?> 