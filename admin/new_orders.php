<?php
require_once 'includes/auth.php';
require_once '../config/database.php';
require_once '../includes/system_integration.php';

$database = new Database();
$conn = $database->getConnection();

// Récupérer les nouvelles commandes (status = 'new')
$stmt = $conn->prepare("
    SELECT 
        o.*,
        u.username as affiliate_name,
        COUNT(oi.id) as items_count,
        GROUP_CONCAT(CONCAT(oi.product_name, ' (', oi.quantity, ')') SEPARATOR ', ') as products_list
    FROM orders o
    LEFT JOIN users u ON o.affiliate_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.status = 'new'
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$new_orders = $stmt->fetchAll();

// Récupérer les confirmateurs disponibles
$stmt = $conn->prepare("
    SELECT id, username, email, phone, city
    FROM users 
    WHERE type = 'confirmateur' AND status = 'active'
    ORDER BY username
");
$stmt->execute();
$confirmateurs = $stmt->fetchAll();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign_confirmateur':
                $order_id = $_POST['order_id'];
                $confirmateur_id = $_POST['confirmateur_id'];
                
                // Assigner la commande au confirmateur
                $stmt = $conn->prepare("
                    UPDATE orders 
                    SET status = 'pending', 
                        confirmateur_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$confirmateur_id, $order_id]);
                
                $_SESSION['success'] = "Commande assignée au confirmateur avec succès !";
                break;
                
            case 'batch_assign':
                $order_ids = $_POST['order_ids'] ?? [];
                $confirmateur_id = $_POST['confirmateur_id'];
                
                if (!empty($order_ids) && $confirmateur_id) {
                    $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
                    $stmt = $conn->prepare("
                        UPDATE orders 
                        SET status = 'pending', 
                            confirmateur_id = ?,
                            updated_at = NOW()
                        WHERE id IN ($placeholders)
                    ");
                    $params = array_merge([$confirmateur_id], $order_ids);
                    $stmt->execute($params);
                    
                    $_SESSION['success'] = count($order_ids) . " commande(s) assignée(s) au confirmateur !";
                }
                break;
        }
        header('Location: new_orders.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelles Commandes - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .order-card {
            border-left: 4px solid #007bff;
            transition: all 0.3s ease;
        }
        .order-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .batch-actions {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .order-checkbox {
            transform: scale(1.2);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-plus-circle text-primary me-2"></i>Nouvelles Commandes</h1>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary fs-6"><?php echo count($new_orders); ?> commande(s)</span>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (empty($new_orders)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">Aucune nouvelle commande</h3>
                    <p class="text-muted">Toutes les commandes ont été traitées.</p>
                </div>
            <?php else: ?>
                <!-- Actions par lot -->
                <div class="batch-actions">
                    <form method="POST" id="batchForm">
                        <input type="hidden" name="action" value="batch_assign">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-users me-2"></i>Assigner à un confirmateur :</label>
                                <select name="confirmateur_id" class="form-select" required>
                                    <option value="">Choisir un confirmateur...</option>
                                    <?php foreach ($confirmateurs as $confirmateur): ?>
                                        <option value="<?php echo $confirmateur['id']; ?>">
                                            <?php echo htmlspecialchars($confirmateur['username']); ?> 
                                            (<?php echo htmlspecialchars($confirmateur['city']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary" id="batchAssignBtn" disabled>
                                    <i class="fas fa-share me-2"></i>Assigner les commandes sélectionnées
                                </button>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAll()">
                                    <i class="fas fa-check-square me-2"></i>Tout sélectionner
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deselectAll()">
                                    <i class="fas fa-square me-2"></i>Tout désélectionner
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Liste des nouvelles commandes -->
                <div class="row">
                    <?php foreach ($new_orders as $order): ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card order-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-shopping-cart me-2"></i>
                                        Commande #<?php echo $order['id']; ?>
                                    </h6>
                                    <div class="form-check">
                                        <input class="form-check-input order-checkbox" type="checkbox" 
                                               value="<?php echo $order['id']; ?>" 
                                               onchange="updateBatchButton()">
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Client</small>
                                            <div class="fw-bold"><?php echo htmlspecialchars($order['client_name']); ?></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Affilié</small>
                                            <div class="fw-bold text-primary"><?php echo htmlspecialchars($order['affiliate_name']); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">Produits</small>
                                        <div class="small"><?php echo htmlspecialchars($order['products_list']); ?></div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Total</small>
                                            <div class="fw-bold text-success"><?php echo number_format($order['final_sale_price'], 2); ?> MAD</div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Commission</small>
                                            <div class="fw-bold text-info"><?php echo number_format($order['commission_amount'], 2); ?> MAD</div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">Adresse</small>
                                        <div class="small"><?php echo htmlspecialchars($order['client_address']); ?>, <?php echo htmlspecialchars($order['client_city']); ?></div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                        </small>
                                        <span class="badge bg-warning status-badge">
                                            <i class="fas fa-clock me-1"></i>Nouvelle
                                        </span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-primary flex-fill" 
                                                onclick="assignOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-user-plus me-1"></i>Assigner
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal d'assignation -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Assigner la commande
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="assign_confirmateur">
                        <input type="hidden" name="order_id" id="assignOrderId">
                        
                        <div class="mb-3">
                            <label for="confirmateurSelect" class="form-label">Choisir un confirmateur :</label>
                            <select name="confirmateur_id" id="confirmateurSelect" class="form-select" required>
                                <option value="">Sélectionner un confirmateur...</option>
                                <?php foreach ($confirmateurs as $confirmateur): ?>
                                    <option value="<?php echo $confirmateur['id']; ?>">
                                        <?php echo htmlspecialchars($confirmateur['username']); ?> 
                                        (<?php echo htmlspecialchars($confirmateur['city']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Assigner
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function assignOrder(orderId) {
            document.getElementById('assignOrderId').value = orderId;
            new bootstrap.Modal(document.getElementById('assignModal')).show();
        }
        
        function viewOrderDetails(orderId) {
            window.open(`orders.php?view=${orderId}`, '_blank');
        }
        
        function updateBatchButton() {
            const checkboxes = document.querySelectorAll('.order-checkbox:checked');
            const batchBtn = document.getElementById('batchAssignBtn');
            batchBtn.disabled = checkboxes.length === 0;
            
            if (checkboxes.length > 0) {
                batchBtn.innerHTML = `<i class="fas fa-share me-2"></i>Assigner ${checkboxes.length} commande(s)`;
            } else {
                batchBtn.innerHTML = `<i class="fas fa-share me-2"></i>Assigner les commandes sélectionnées`;
            }
        }
        
        function selectAll() {
            document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = true);
            updateBatchButton();
        }
        
        function deselectAll() {
            document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = false);
            updateBatchButton();
        }
        
        // Mettre à jour le formulaire de lot avec les IDs sélectionnés
        document.getElementById('batchForm').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('.order-checkbox:checked');
            const orderIds = Array.from(checkboxes).map(cb => cb.value);
            
            // Créer des champs cachés pour chaque ID
            orderIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order_ids[]';
                input.value = id;
                this.appendChild(input);
            });
        });
    </script>
</body>
</html> 