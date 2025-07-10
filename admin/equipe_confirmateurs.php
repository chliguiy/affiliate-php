<?php
// Page : Liste des confirmateurs
require_once '../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

// Traitement de l'ajout
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_confirmateur'])) {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $rib = trim($_POST['rib'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    if ($nom && $email && $password && $rib && $telephone && $adresse) {
        // Vérifier si l'email existe déjà pour un confirmateur
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM equipe WHERE email = ? AND role = "confirmateur"');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $message = '<div class="alert alert-danger">Cet email est déjà utilisé par un confirmateur.</div>';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Mot de passe hashé
            $stmt = $pdo->prepare('INSERT INTO equipe (nom, email, password, rib, telephone, adresse, role) VALUES (?, ?, ?, ?, ?, ?, "confirmateur")');
            if ($stmt->execute([$nom, $email, $hashed_password, $rib, $telephone, $adresse])) {
                $message = '<div class="alert alert-success">Confirmateur ajouté avec succès.</div>';
            } else {
                $message = '<div class="alert alert-danger">Erreur lors de l\'ajout.</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-warning">Tous les champs sont obligatoires.</div>';
    }
}

// Récupérer la liste des confirmateurs
$stmt = $pdo->prepare('SELECT * FROM equipe WHERE role = "confirmateur" ORDER BY id DESC');
$stmt->execute();
$confirmateurs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des confirmateurs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Liste des confirmateurs</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConfirmateurModal"><i class="fas fa-user-plus"></i> Ajouter un confirmateur</button>
                </div>
                <?php echo $message; ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($confirmateurs) > 0): ?>
                            <?php foreach ($confirmateurs as $confirmateur): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($confirmateur['id']); ?></td>
                                <td><?php echo htmlspecialchars($confirmateur['nom']); ?></td>
                                <td><?php echo htmlspecialchars($confirmateur['email']); ?></td>
                                <td>
                                    <a href="confirmateur_dashboard.php?id=<?php echo $confirmateur['id']; ?>" class="btn btn-sm btn-info">Dashboard</a>
                                    <a href="#" class="btn btn-sm btn-warning">Modifier</a>
                                    <a href="#" class="btn btn-sm btn-danger">Supprimer</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">Aucun confirmateur trouvé.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Modal Ajout Confirmateur -->
                <div class="modal fade" id="addConfirmateurModal" tabindex="-1" aria-labelledby="addConfirmateurModalLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="post">
                        <div class="modal-header">
                          <h5 class="modal-title" id="addConfirmateurModalLabel">Ajouter un confirmateur</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                          </div>
                          <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                          </div>
                          <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                          </div>
                          <div class="mb-3">
                            <label for="rib" class="form-label">RIB</label>
                            <input type="text" class="form-control" id="rib" name="rib" required>
                          </div>
                          <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="text" class="form-control" id="telephone" name="telephone" required>
                          </div>
                          <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="adresse" name="adresse" required>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                          <button type="submit" name="add_confirmateur" class="btn btn-primary">Ajouter</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 