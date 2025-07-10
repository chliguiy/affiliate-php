<?php
// admin/messages.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
require_once '../config/database.php';

$db = new Database();
$pdo = $db->getConnection();

$success = null;
$error = null;

// Suppression
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM admin_messages WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Message supprimé.";
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}
// Activation/désactivation
if (isset($_POST['toggle_id'])) {
    $id = intval($_POST['toggle_id']);
    $current = intval($_POST['current_active']);
    try {
        $stmt = $pdo->prepare("UPDATE admin_messages SET is_active = ? WHERE id = ?");
        $stmt->execute([$current ? 0 : 1, $id]);
        $success = $current ? "Message désactivé." : "Message activé.";
    } catch (PDOException $e) {
        $error = "Erreur lors du changement d'état : " . $e->getMessage();
    }
}
// Modification
if (isset($_POST['edit_id'], $_POST['edit_message'], $_POST['edit_date_debut'], $_POST['edit_date_fin'])) {
    $id = intval($_POST['edit_id']);
    $msg = trim($_POST['edit_message']);
    $date_debut = $_POST['edit_date_debut'];
    $date_fin = $_POST['edit_date_fin'];
    if ($msg && $date_debut && $date_fin) {
        try {
            $stmt = $pdo->prepare("UPDATE admin_messages SET message=?, date_debut=?, date_fin=? WHERE id=?");
            $stmt->execute([$msg, $date_debut, $date_fin, $id]);
            $success = "Message modifié.";
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification : " . $e->getMessage();
        }
    } else {
        $error = "Tous les champs sont obligatoires pour la modification.";
    }
}
// Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['date_debut'], $_POST['date_fin']) && !isset($_POST['edit_id'])) {
    $message = trim($_POST['message']);
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    if ($message && $date_debut && $date_fin) {
        try {
            $stmt = $pdo->prepare("INSERT INTO admin_messages (message, date_debut, date_fin) VALUES (?, ?, ?)");
            $stmt->execute([$message, $date_debut, $date_fin]);
            $success = "Message ajouté avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout du message : " . $e->getMessage();
        }
    } else {
        $error = "Tous les champs sont obligatoires.";
    }
}
// Récupération des messages
$messages = [];
try {
    $stmt = $pdo->query("SELECT * FROM admin_messages ORDER BY created_at DESC, id DESC");
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des messages : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f4f6f9;
        }
        .messages-container {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            padding: 2rem 2.5rem;
        }
        .btn-primary {
            background: #2c3e50;
            border: none;
        }
        .btn-primary:hover {
            background: #1a232c;
        }
        .no-messages {
            color: #888;
            text-align: center;
            margin-top: 2rem;
        }
        .message-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
            position: relative;
        }
        .message-item.inactive {
            opacity: 0.5;
            background: #f8f9fa;
        }
        .message-dates {
            font-size: 0.95em;
            color: #888;
        }
        .message-actions {
            position: absolute;
            top: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="container-fluid" style="margin-left:280px; min-height:100vh;">
        <div class="messages-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0"><i class="fas fa-envelope me-2"></i>Messages</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nouveauMessageModal">
                    <i class="fas fa-plus me-2"></i>Nouveau message
                </button>
            </div>
            <?php if ($success): ?>
                <div class="alert alert-success"> <?= htmlspecialchars($success) ?> </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
            <?php endif; ?>
            <?php if (count($messages) === 0): ?>
                <div class="no-messages">
                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                    Aucun message pour le moment.
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead class="table-light">
                        <tr>
                            <th>Message</th>
                            <th>Dates</th>
                            <th>Statut</th>
                            <th>Ajouté le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                        <tr class="<?= !$msg['is_active'] ? 'table-secondary' : '' ?>">
                            <td class="text-start">
                                <i class="fas fa-comment-dots me-2"></i>
                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                            </td>
                            <td>
                                <i class="fas fa-calendar-alt me-1"></i>
                                Du <?= date('d/m/Y H:i', strtotime($msg['date_debut'])) ?><br>
                                au <?= date('d/m/Y H:i', strtotime($msg['date_fin'])) ?>
                            </td>
                            <td>
                                <?php if ($msg['is_active']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <!-- Modifier -->
                                    <button class="btn btn-sm btn-warning edit-btn" 
                                        data-id="<?= $msg['id'] ?>" 
                                        data-message="<?= htmlspecialchars($msg['message'], ENT_QUOTES) ?>" 
                                        data-date_debut="<?= date('Y-m-d\TH:i', strtotime($msg['date_debut'])) ?>" 
                                        data-date_fin="<?= date('Y-m-d\TH:i', strtotime($msg['date_fin'])) ?>"
                                        title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <!-- Activer/Désactiver -->
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="toggle_id" value="<?= $msg['id'] ?>">
                                        <input type="hidden" name="current_active" value="<?= $msg['is_active'] ?>">
                                        <?php if ($msg['is_active']): ?>
                                            <button type="submit" class="btn btn-sm btn-success" title="Activer">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm btn-secondary" title="Désactiver">
                                                <i class="fas fa-eye-slash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    <!-- Supprimer -->
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce message ?');">
                                        <input type="hidden" name="delete_id" value="<?= $msg['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Nouveau Message -->
    <div class="modal fade" id="nouveauMessageModal" tabindex="-1" aria-labelledby="nouveauMessageModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="nouveauMessageModalLabel"><i class="fas fa-envelope me-2"></i>Nouveau Message</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="post" action="">
            <div class="modal-body">
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="4" placeholder="Votre message..." required></textarea>
                </div>
                <div class="mb-3">
                    <label for="date_debut" class="form-label">Date de début</label>
                    <input type="datetime-local" class="form-control" id="date_debut" name="date_debut" required>
                </div>
                <div class="mb-3">
                    <label for="date_fin" class="form-label">Date de fin</label>
                    <input type="datetime-local" class="form-control" id="date_fin" name="date_fin" required>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Envoyer</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Modifier Message -->
    <div class="modal fade" id="editMessageModal" tabindex="-1" aria-labelledby="editMessageModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editMessageModalLabel"><i class="fas fa-edit me-2"></i>Modifier le message</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="post" action="">
            <input type="hidden" name="edit_id" id="edit_id">
            <div class="modal-body">
                <div class="mb-3">
                    <label for="edit_message" class="form-label">Message</label>
                    <textarea class="form-control" id="edit_message" name="edit_message" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="edit_date_debut" class="form-label">Date de début</label>
                    <input type="datetime-local" class="form-control" id="edit_date_debut" name="edit_date_debut" required>
                </div>
                <div class="mb-3">
                    <label for="edit_date_fin" class="form-label">Date de fin</label>
                    <input type="datetime-local" class="form-control" id="edit_date_fin" name="edit_date_fin" required>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" class="btn btn-warning"><i class="fas fa-save me-2"></i>Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Remplir le modal d'édition avec les données du message
    document.querySelectorAll('.edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_message').value = btn.getAttribute('data-message');
            document.getElementById('edit_date_debut').value = btn.getAttribute('data-date_debut');
            document.getElementById('edit_date_fin').value = btn.getAttribute('data-date_fin');
            var editModal = new bootstrap.Modal(document.getElementById('editMessageModal'));
            editModal.show();
        });
    });
    </script>
</body>
</html> 