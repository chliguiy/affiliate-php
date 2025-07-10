<?php
// Script pour créer un nouvel administrateur manuellement
$host = "localhost";
$username = "root";
$password = "";
$dbname = "chic_affiliate";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Paramètres du nouvel admin
    $username = 'admin_test'; // À modifier
    $full_name = 'Admin Test'; // À modifier
    $email = 'admintest@chic-affiliate.com'; // À modifier
    $plainPassword = 'Test@1234'; // À modifier
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    $role = 'super_admin'; // ou 'admin', selon le niveau voulu

    // Vérifier si l'email existe déjà
    $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "Un administrateur avec cet email existe déjà.";
    } else {
        $stmt = $conn->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $full_name, $role]);
        echo "Administrateur créé avec succès !<br>Email : $email<br>Mot de passe : $plainPassword";
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
} 