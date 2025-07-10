<?php
// admin/nouveau_message.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Message</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f4f6f9;
        }
        .message-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44,62,80,0.08);
            padding: 2rem 2.5rem;
        }
        .form-label {
            font-weight: 500;
        }
        .btn-primary {
            background: #2c3e50;
            border: none;
        }
        .btn-primary:hover {
            background: #1a232c;
        }
        .form-control:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 0 0.2rem rgba(44,62,80,.15);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="container-fluid" style="margin-left:280px; min-height:100vh;">
        <div class="message-container">
            <h3 class="mb-4"><i class="fas fa-envelope me-2"></i>Nouveau Message</h3>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="subject" class="form-label">Sujet</label>
                    <input type="text" class="form-control" id="subject" name="subject" placeholder="Sujet du message" required>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="6" placeholder="Votre message..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-2"></i>Envoyer</button>
            </form>
        </div>
    </div>
</body>
</html> 