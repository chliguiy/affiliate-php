<?php
require_once 'includes/auth.php';
require_once 'includes/AdminLogger.php';

// Vérifier les permissions
$permissions = new AdminPermissions($pdo, $_SESSION['admin_id']);
if (!$permissions->canManageAdmins()) {
    header('Location: dashboard.php');
    exit;
}

$logger = new AdminLogger($pdo, $_SESSION['admin_id']);

// Configuration
$backup_dir = __DIR__ . '/../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Fonction pour créer une sauvegarde
function createBackup($pdo, $backup_dir) {
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "backup_{$timestamp}.sql";
    $filepath = $backup_dir . $filename;
    
    // Obtenir la liste des tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $output = "-- Backup de la base de données chic_affiliate\n";
    $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Pour chaque table
    foreach ($tables as $table) {
        // Structure de la table
        $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $output .= "\n\n" . $create_table['Create Table'] . ";\n\n";
        
        // Données de la table
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $values = array_map(function($value) use ($pdo) {
                if ($value === null) return 'NULL';
                return $pdo->quote($value);
            }, $row);
            
            $output .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
        }
    }
    
    // Sauvegarder le fichier
    file_put_contents($filepath, $output);
    
    // Compresser le fichier
    $zip = new ZipArchive();
    $zipname = $filepath . '.zip';
    if ($zip->open($zipname, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($filepath, $filename);
        $zip->close();
        unlink($filepath); // Supprimer le fichier SQL original
        return $zipname;
    }
    
    return false;
}

// Fonction pour restaurer une sauvegarde
function restoreBackup($pdo, $backup_file) {
    try {
        // Décompresser le fichier
        $zip = new ZipArchive();
        if ($zip->open($backup_file) === TRUE) {
            $zip->extractTo(dirname($backup_file));
            $zip->close();
            
            // Lire le fichier SQL
            $sql_file = str_replace('.zip', '', $backup_file);
            $sql = file_get_contents($sql_file);
            
            // Exécuter les requêtes
            $pdo->exec($sql);
            
            // Nettoyer
            unlink($sql_file);
            return true;
        }
    } catch (Exception $e) {
        error_log("Erreur de restauration : " . $e->getMessage());
    }
    return false;
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $backup_file = createBackup($pdo, $backup_dir);
                if ($backup_file) {
                    $logger->log('create', 'backup', null, ['file' => basename($backup_file)]);
                    $success = "Sauvegarde créée avec succès.";
                } else {
                    $error = "Erreur lors de la création de la sauvegarde.";
                }
                break;
                
            case 'restore':
                if (isset($_POST['backup_file'])) {
                    $backup_file = $backup_dir . $_POST['backup_file'];
                    if (restoreBackup($pdo, $backup_file)) {
                        $logger->log('restore', 'backup', null, ['file' => $_POST['backup_file']]);
                        $success = "Sauvegarde restaurée avec succès.";
                    } else {
                        $error = "Erreur lors de la restauration de la sauvegarde.";
                    }
                }
                break;
                
            case 'delete':
                if (isset($_POST['backup_file'])) {
                    $backup_file = $backup_dir . $_POST['backup_file'];
                    if (unlink($backup_file)) {
                        $logger->log('delete', 'backup', null, ['file' => $_POST['backup_file']]);
                        $success = "Sauvegarde supprimée avec succès.";
                    } else {
                        $error = "Erreur lors de la suppression de la sauvegarde.";
                    }
                }
                break;
        }
    }
}

// Lister les sauvegardes existantes
$backups = [];
if ($handle = opendir($backup_dir)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != ".." && pathinfo($entry, PATHINFO_EXTENSION) == 'zip') {
            $backups[] = [
                'name' => $entry,
                'size' => filesize($backup_dir . $entry),
                'date' => date('Y-m-d H:i:s', filemtime($backup_dir . $entry))
            ];
        }
    }
    closedir($handle);
}

// Trier les sauvegardes par date (plus récentes d'abord)
usort($backups, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Sauvegardes - Administration</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .backup-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .backup-actions {
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .backup-list {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="backup-container">
        <h1>Gestion des Sauvegardes</h1>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <div class="backup-actions">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="create">
                <button type="submit" class="btn btn-primary">Créer une sauvegarde</button>
            </form>
        </div>

        <div class="backup-list">
            <h2>Sauvegardes existantes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nom du fichier</th>
                        <th>Date</th>
                        <th>Taille</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><?= htmlspecialchars($backup['name']) ?></td>
                            <td><?= $backup['date'] ?></td>
                            <td><?= number_format($backup['size'] / 1024 / 1024, 2) ?> MB</td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="backup_file" value="<?= $backup['name'] ?>">
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Toutes les données actuelles seront remplacées.')">
                                        Restaurer
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="backup_file" value="<?= $backup['name'] ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette sauvegarde ?')">
                                        Supprimer
                                    </button>
                                </form>
                                
                                <a href="backups/<?= $backup['name'] ?>" class="btn btn-primary" download>
                                    Télécharger
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 