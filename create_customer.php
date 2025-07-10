<?php
// Configuration de la base de données
$host = "localhost";
$username = "root";
$password = "";
$database = "chic_affiliate";

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);

    // Données du client
    $username = "client1";
    $email = "client1@example.com";
    $password = password_hash("client123", PASSWORD_DEFAULT);
    $full_name = "Mohammed Client";
    $phone = "0612345678";
    $address = "123 Rue Hassan II, Casablanca";
    $city = "Casablanca";
    $type = "customer";
    $status = "active";

    // Préparation de la requête
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, address, city, type, status) 
                          VALUES (:username, :email, :password, :full_name, :phone, :address, :city, :type, :status)");

    // Exécution de la requête
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $password,
        ':full_name' => $full_name,
        ':phone' => $phone,
        ':address' => $address,
        ':city' => $city,
        ':type' => $type,
        ':status' => $status
    ]);

    echo "✅ Compte client créé avec succès !\n";
    echo "👤 Nom d'utilisateur: $username\n";
    echo "📧 Email: $email\n";
    echo "🔑 Mot de passe: client123\n";

} catch (PDOException $e) {
    echo "❌ Erreur lors de la création du compte: " . $e->getMessage() . "\n";
}
?> 