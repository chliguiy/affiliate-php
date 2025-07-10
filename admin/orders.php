<?php
require_once 'includes/auth.php';
require_once '../config/database.php';
require_once '../APIDelivery.php';
$database = new Database();
$apiDelivery = new APIDelivery();
$conn = $database->getConnection();

// Process POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'refresh_orders':
            try {
                $stmt = $conn->prepare("SELECT * FROM orders WHERE status IN ('processing', 'shipped') AND delivery_number IS NOT NULL");
                $stmt->execute();
                $orders = $stmt->fetchAll();

                foreach ($orders as $order) {
                    try {
                        $deliveryNumber = $order['delivery_number'];
                        $responseJson = $apiDelivery->getParcel($deliveryNumber);
                        $response = json_decode($responseJson, true);

                        if ($response && isset($response['staus_name'])) {
                            $apiStatus = strtolower($response['staus_name']);
                            $newStatus = match ($apiStatus) {
                                'nouvelle demande' => 'processing',
                                'prenez' => 'shipped',
                                'livré' => 'delivered',
                                'retourne' => 'returned',
                                'annulé' => 'cancelled',
                                default => $order['status']
                            };

                            if ($newStatus !== $order['status']) {
                                $stmtUpdate = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                                $stmtUpdate->execute([$newStatus, $order['id']]);
                            }
                        } else {
                            throw new Exception("Réponse API invalide pour le colis: " . $deliveryNumber);
                        }
                    } catch (Exception $e) {
                        error_log("Erreur sur la commande ID {$order['id']}: " . $e->getMessage());
                    }
                }

                $_SESSION['success_message'] = "Les statuts des commandes ont été mis à jour.";
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Erreur de mise à jour des commandes: " . $e->getMessage();
            }
            break;

        case 'send_delivery':
            try {

                $orderIds = $_POST['order_ids'] ?? [];
                if (!empty($orderIds)) {


                    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
                    echo $placeholders;
                    $stmt = $conn->prepare("SELECT * FROM orders WHERE id IN ($placeholders)");
                    $stmt->execute($orderIds);
                    $orders = $stmt->fetchAll();

                    foreach ($orders as $order) {
                        try {
                            $order_id = $order['id'];
                            $customer_name = $order['customer_name'];
                            $final_sale_price = $order['final_sale_price'];
                            $comment = $order['order_comment'] ?? '';

                            $parcelData = [
                                'external_id' => $order_id,
                                'receiver' => $order['customer_name'],
                                'phone' => $order['customer_phone'],
                                'city_id' => $order['customer_city'],  // adjust column names as needed
                                'product_nature' => 'Order from Website',
                                'price' => $order['final_sale_price'],
                                'address' => $order['customer_address'],
                                'note' => $order['order_comment'] ?? '',
                                'can_open' => false
                            ];

                            $responseJson = $apiDelivery->createParcel($parcelData);
                            $response = json_decode($responseJson, true);

                            if ($response && isset($response['original'])) {
                                $trackingNum = $response['original']['tracking_num'] ?? null;

                                if ($trackingNum) {
                                    $stmtUpdate = $conn->prepare("UPDATE orders SET status = 'processing', delivery_number = ? WHERE id = ?");
                                    $stmtUpdate->execute([$trackingNum, $order_id]);
                                } else {
                                    throw new Exception("Numéro de suivi manquant dans la réponse API.");
                                }
                            } else {
                                throw new Exception("Réponse API invalide : " . $responseJson);
                            }
                        } catch (Exception $e) {
                            $_SESSION['error_message'] = "Erreur commande ID {$order['id']}: " . $e->getMessage();
                        }
                    }

                    $_SESSION['success_message'] = "Commandes traitées.";
                } else {
                    $_SESSION['error_message'] = "Aucune commande sélectionnée.";
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Erreur traitement commandes: " . $e->getMessage();
            }
            break;

        case 'update_status':
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['status'], $_POST['id']]);
            $_SESSION['success_message'] = "Statut de la commande mis à jour.";
            break;

        case 'update_payment':
            $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $stmt->execute([$_POST['payment_status'], $_POST['id']]);
            $_SESSION['success_message'] = "Statut de paiement mis à jour.";
            break;

        case 'delete_order':
            try {
                $conn->beginTransaction();
                $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $conn->commit();
                $_SESSION['success_message'] = "Commande supprimée.";
            } catch (Exception $e) {
                $conn->rollBack();
                $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
            }
            break;
    }
    header('Location: orders.php');
    exit();
}

// Filters
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Query Orders
$query = "
    SELECT o.*, u.username AS affiliate_name, COUNT(oi.id) AS items_count
    FROM orders o
    LEFT JOIN users u ON o.affiliate_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE 1=1
";
if ($status_filter)
    $query .= " AND o.status = " . $conn->quote($status_filter);
if ($payment_filter)
    $query .= " AND o.payment_status = " . $conn->quote($payment_filter);
if ($date_filter)
    $query .= " AND DATE(o.created_at) = " . $conn->quote($date_filter);

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";
$orders = $conn->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des commandes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="container mt-4">
    <h1>Gestion des commandes</h1>

    <?php if (!empty($_SESSION['success_message'])): ?>
                                <div class="alert alert-success"><?php echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger"><?php echo $_SESSION['error_message'];
                                unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <form class="row mb-4">
        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <?php foreach (['new', 'unconfirmed', 'confirmed', 'processing', 'shipped', 'delivered', 'returned', 'cancelled'] as $status): ?>
                                            <option value="<?php echo $status; ?>" <?php if ($status_filter === $status)
                                                   echo 'selected'; ?>><?php echo ucfirst($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="payment" class="form-select" onchange="this.form.submit()">
                <option value="">Tous les paiements</option>
                <?php foreach (['pending', 'paid', 'refunded'] as $pay): ?>
                                            <option value="<?php echo $pay; ?>" <?php if ($payment_filter === $pay)
                                                   echo 'selected'; ?>><?php echo ucfirst($pay); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>" onchange="this.form.submit()">
        </div>
    </form>

    <form method="POST" class="d-inline">
        <input type="hidden" name="action" value="refresh_orders">
        <button type="submit" class="btn btn-info mb-3">🔄 Rafraîchir les statuts des commandes</button>
    </form>

    <!-- Orders Table -->
    <form method="POST">
        <input type="hidden" name="action" value="send_delivery">
        <button type="submit" class="btn btn-success mb-3" id="sendToDeliveryBtn" disabled>Envoyer à la livraison</button>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>N°</th>
                <th>Client</th>
                <th>Total</th>
                <th>Commission</th>
                <th>Articles</th>
                <th>Statut</th>
                <th>Paiement</th>
                <th>Date</th>
                <th>Affilié</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><input type="checkbox" name="order_ids[]" value="<?php echo $order['id']; ?>" class="order-checkbox" <?php echo ($order['status'] === 'confirmed') ? '' : 'disabled'; ?>></td>
                                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?><br><small><?php echo htmlspecialchars($order['customer_email']); ?></small></td>
                                            <td><?php echo number_format($order['final_sale_price'], 2); ?> MAD</td>
                                            <td><?php echo number_format($order['commission_amount'], 2); ?> MAD</td>
                                            <td><?php echo $order['items_count']; ?></td>
                                            <td><span class="badge bg-secondary"><?php echo $order['status']; ?></span></td>
                                            <td><span class="badge bg-secondary"><?php echo $order['payment_status'] ?? 'N/A'; ?></span></td>
                                            <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($order['affiliate_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <a href="order_view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">Voir</a>
                                                <button type="button" class="btn btn-sm btn-danger delete-order" data-id="<?php echo $order['id']; ?>">Supprimer</button>
                                            </td>
                                        </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Delete order
document.querySelectorAll('.delete-order').forEach(button => {
    button.addEventListener('click', () => {
        if (confirm('Supprimer cette commande ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'orders.php';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_order">
                <input type="hidden" name="id" value="${button.dataset.id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
});

// Enable Send Button if any checkbox selected
const checkboxes = document.querySelectorAll('.order-checkbox');
const sendBtn = document.getElementById('sendToDeliveryBtn');
document.getElementById('selectAll').addEventListener('change', function () {
    checkboxes.forEach(cb => {
        if (!cb.disabled) cb.checked = this.checked;
    });
    updateButton();
});
checkboxes.forEach(cb => cb.addEventListener('change', updateButton));
function updateButton() {
    const hasChecked = Array.from(checkboxes).some(cb => cb.checked);
    sendBtn.disabled = !hasChecked;
}
</script>
</body>
</html>
