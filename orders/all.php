<?php
// Récupérer toutes les commandes
$status_filter = $_GET['status'] ?? '';

$query = "
    SELECT 
        o.*,
        GROUP_CONCAT(p.name SEPARATOR ', ') as products
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.affiliate_id = ?
";
$params = [$_SESSION['user_id']];
if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}
$query .= " GROUP BY o.id ORDER BY o.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Calculer les stats globales (toutes commandes de l'affilié)
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'delivered' THEN final_sale_price ELSE 0 END) as total_amount,
        SUM(CASE WHEN status = 'delivered' THEN IFNULL(commission_amount, 0) ELSE 0 END) as total_commission
    FROM orders
    WHERE affiliate_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$global_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculer les statistiques
$total_orders = count($orders);
$total_amount = array_sum(array_map(function($order) {
    return ($order['status'] === 'delivered') ? $order['final_sale_price'] : 0;
}, $orders));
$total_commission = array_sum(array_map(function($order) {
    if ($order['status'] === 'delivered') {
        return isset($order['commission']) ? $order['commission'] : (isset($order['affiliate_margin']) ? $order['affiliate_margin'] : 0);
    }
    return 0;
}, $orders));

// Compter les commandes par statut
$status_counts = array_count_values(array_column($orders, 'status'));

// Définir les classes de badge pour chaque statut
$status_badges = [
    'new' => 'primary',
    'unconfirmed' => 'danger',
    'confirmed' => 'success',
    'shipping' => 'info',
    'delivered' => 'success',
    'returned' => 'warning',
    'refused' => 'danger',
    'cancelled' => 'secondary',
    'duplicate' => 'dark',
    'changed' => 'warning'
];

// Définir les traductions des statuts
$status_translations = [
    'new' => 'Nouveau',
    'unconfirmed' => 'Non confirmé',
    'confirmed' => 'Confirmé',
    'shipping' => 'En livraison',
    'delivered' => 'Livré',
    'returned' => 'Retourné',
    'refused' => 'Refusé',
    'cancelled' => 'Annulé',
    'duplicate' => 'Dupliqué',
    'changed' => 'Changé'
];
?>

<div class="orders-container">
    <!-- Statistiques globales (toujours affichées) -->
    <div class="row mb-2">
        <div class="col-md-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Commandes (Global)</h6>
                    <h2 class="card-text"><?php echo $global_stats['total_orders'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h6 class="card-title">Chiffre d'Affaires Total (Global)</h6>
                    <h2 class="card-text"><?php echo number_format($global_stats['total_amount'] ?? 0, 2); ?> DH</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h6 class="card-title">Commission Totale (Global)</h6>
                    <h2 class="card-text"><?php echo number_format($global_stats['total_commission'] ?? 0, 2); ?> DH</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h6 class="card-title">Taux de Conversion (Global)</h6>
                    <h2 class="card-text">
                        <?php
                        $delivered_global = $global_stats['total_orders'] ? ($status_counts['delivered'] ?? 0) : 0;
                        echo $global_stats['total_orders'] ? number_format(($delivered_global / $global_stats['total_orders']) * 100, 1) : '0.0';
                        ?>%
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques générales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total des Commandes</h6>
                    <h2 class="card-text"><?php echo $total_orders; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Chiffre d'Affaires Total</h6>
                    <h2 class="card-text"><?php echo number_format($total_amount, 2); ?> DH</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Commission Totale</h6>
                    <h2 class="card-text"><?php echo number_format($total_commission, 2); ?> DH</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Taux de Conversion</h6>
                    <h2 class="card-text"><?php 
                        $delivered = $status_counts['delivered'] ?? 0;
                        echo number_format(($delivered / ($total_orders ?: 1)) * 100, 1);
                    ?>%</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres rapides -->
    <div class="mb-4">
        <div class="btn-group flex-wrap" style="width: 100%; gap: 5px;">
            <a href="orders.php" class="btn btn-sm <?php echo empty($_GET['status']) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                Tous (<?php echo $total_orders; ?>)
            </a>
            <?php foreach ($status_translations as $status => $label): ?>
            <a href="orders.php?status=<?php echo $status; ?>" 
               class="btn btn-sm <?php echo ($_GET['status'] ?? '') === $status ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <?php echo $label; ?> (<?php echo $status_counts[$status] ?? 0; ?>)
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tableau des commandes -->
    <div class="table-responsive">
        <table class="table table-hover" id="allOrdersTable">
            <thead>
                <tr>
                    <th>N° Commande</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Ville</th>
                    <th>Produits</th>
                    <th>Total</th>
                    <th>Commission</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr data-order-id="<?php echo $order['id']; ?>">
                    <td><?php echo $order['order_number']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_city'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($order['products'] ?? ''); ?></td>
                    <td><?php echo number_format($order['final_sale_price'] ?? 0, 2); ?> Dhs</td>
                    <td><?php echo number_format(isset($order['commission']) ? ($order['commission'] ?? 0) : (isset($order['affiliate_margin']) ? ($order['affiliate_margin'] ?? 0) : 0), 2); ?> DH</td>
                    <td>
                        <span class="badge bg-<?php echo $status_badges[$order['status']]; ?>">
                            <?php echo $status_translations[$order['status']]; ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-info" title="Voir les détails"
                                    onclick="viewOrder(<?php echo $order['id']; ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" title="Modifier"
                                    onclick="editOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning" title="Imprimer la facture"
                                    onclick="printInvoice(<?php echo $order['id']; ?>)">
                                <i class="fas fa-print"></i>
                            </button>
                            <?php if ($order['status'] === 'new'): ?>
                            <button type="button" class="btn btn-sm btn-danger" title="Supprimer"
                                    onclick="deleteOrder(<?php echo $order['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal d'édition -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOrderModalLabel">Modifier la commande</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="editOrderForm">
                    <input type="hidden" id="edit_order_id" name="order_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="customer_name" class="form-label">Nom du client</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required placeholder="Ex: Mohamed El Amrani">
                        </div>
                        <div class="col-md-6">
                            <label for="customer_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email" placeholder="Ex: client@email.com">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="customer_phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required pattern="0[5-7][0-9]{8}" placeholder="Ex: 0612345678">
                        </div>
                        <div class="col-md-6">
                            <label for="customer_city" class="form-label">Ville</label>
                            <input type="text" class="form-control" id="customer_city" name="customer_city" required placeholder="Ex: Casablanca">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="customer_address" class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="customer_address" name="customer_address" required placeholder="Ex: 123 Rue de Fès, Quartier Maarif">
                        </div>
                        <div class="col-md-4">
                            <label for="postal_code" class="form-label">Code postal</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" placeholder="Ex: 20000">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="shipping_method" class="form-label">Mode de livraison</label>
                            <input type="text" class="form-control" id="shipping_method" name="shipping_method" placeholder="Ex: Amana, Chronopost, ...">
                        </div>
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label">Mode de paiement</label>
                            <input type="text" class="form-control" id="payment_method" name="payment_method" placeholder="Ex: Espèces, Carte bancaire, ...">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Notes supplémentaires pour la commande"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveOrderChanges()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de changement de produits -->
<div class="modal fade" id="changeProductsModal" tabindex="-1" aria-labelledby="changeProductsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeProductsModalLabel">Changer les produits</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="changeProductsForm">
                    <input type="hidden" id="change_order_id" name="order_id">
                    
                    <div class="products-container mb-3">
                        <!-- Les produits seront ajoutés ici dynamiquement -->
                    </div>

                    <button type="button" class="btn btn-success" onclick="addProductRow()">
                        <i class="fas fa-plus"></i> Ajouter un produit
                    </button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveProductChanges()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de détails de commande -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="orderDetailsModalLabel">Détails de la commande</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="orderDetailsContent">
        <div class="text-center text-muted py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <div>Chargement...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    $('#allOrdersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
        },
        order: [[1, 'desc']],
        pageLength: 25,
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rtip'
    });

    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function viewOrder(orderId) {
  const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
  const content = document.getElementById('orderDetailsContent');
  content.innerHTML = `<div class='text-center text-muted py-5'><div class='spinner-border text-primary' role='status'></div><div>Chargement...</div></div>`;
  fetch('admin/get_order_details.php?order_id=' + orderId)
    .then(response => response.text())
    .then(html => {
      content.innerHTML = html;
    })
    .catch(() => {
      content.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des détails.</div>';
    });
  modal.show();
}

function printInvoice(orderId) {
    window.open(`order_label_image.php?order_id=${orderId}`, '_blank');
}

function deleteOrder(orderId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')) {
        window.location.href = `orders.php?action=delete&id=${orderId}`;
    }
}

function editOrder(order) {
    // Remplir le formulaire avec les données de la commande
    document.getElementById('edit_order_id').value = order.id;
    document.getElementById('customer_name').value = order.customer_name;
    document.getElementById('customer_email').value = order.customer_email || '';
    document.getElementById('customer_phone').value = order.customer_phone;
    document.getElementById('customer_address').value = order.customer_address;
    document.getElementById('customer_city').value = order.customer_city || '';
    document.getElementById('postal_code').value = order.postal_code || '';
    document.getElementById('shipping_method').value = order.shipping_method || '';
    document.getElementById('payment_method').value = order.payment_method || '';
    document.getElementById('notes').value = order.notes || '';

    // Afficher le modal
    new bootstrap.Modal(document.getElementById('editOrderModal')).show();
}

function saveOrderChanges() {
    const form = document.getElementById('editOrderForm');
    const formData = new FormData(form);

    // Envoyer la requête AJAX
    fetch('orders/edit_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            bootstrap.Modal.getInstance(document.getElementById('editOrderModal')).hide();
            
            // Mettre à jour les données dans le tableau
            const row = document.querySelector(`tr[data-order-id="${data.order.id}"]`);
            if (row) {
                row.querySelector('td:nth-child(3)').textContent = data.order.customer_name;
                row.querySelector('td:nth-child(4)').textContent = data.order.customer_phone;
                row.querySelector('td:nth-child(5)').textContent = data.order.customer_city;
            }

            // Afficher un message de succès
            alert('Commande mise à jour avec succès');
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la mise à jour de la commande');
    });
}

let allProducts = [];

function loadProducts() {
    fetch('products/get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allProducts = data.products;
                console.log(allProducts);
            }
        })
        .catch(error => console.error('Erreur:', error));
}

// Charger les produits au chargement de la page
document.addEventListener('DOMContentLoaded', loadProducts);

function changeProducts(orderId) {
    document.getElementById('change_order_id').value = orderId;
    
    // Vider le conteneur de produits
    const container = document.querySelector('.products-container');
    container.innerHTML = '';

    // Récupérer les produits actuels de la commande
    fetch(`orders/get_order_items.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.items.forEach(item => addProductRow(item));
            }
        })
        .catch(error => console.error('Erreur:', error));

    // Afficher le modal
    new bootstrap.Modal(document.getElementById('changeProductsModal')).show();
}

function addProductRow(item = null) {
    const container = document.querySelector('.products-container');
    const rowDiv = document.createElement('div');
    rowDiv.className = 'row mb-3 product-row';

    // Empêcher la sélection de doublons
    const selectedProductIds = Array.from(document.querySelectorAll('.product-select')).map(sel => sel.value);

    const productOptions = allProducts.map(p => 
        `<option value="${p.id}" ${item && item.product_id == p.id ? 'selected' : ''} ${selectedProductIds.includes(String(p.id)) && (!item || item.product_id != p.id) ? 'disabled' : ''}>
            ${p.name} (${p.price} DH) - Stock: ${p.quantity}
        </option>`
    ).join('');

    rowDiv.innerHTML = `
        <div class="col-md-6">
            <select class="form-select product-select" required onchange="validateProductRows()">
                <option value="">Sélectionner un produit</option>
                ${productOptions}
            </select>
        </div>
        <div class="col-md-4">
            <input type="number" class="form-control quantity-input" 
                   value="${item ? item.quantity : 1}" min="1" required placeholder="Quantité" onchange="validateProductRows()">
        </div>
        <div class="col-md-2 d-flex align-items-center">
            <button type="button" class="btn btn-danger" onclick="removeProductRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="col-12 mt-1 error-message text-danger small" style="display:none;"></div>
    `;

    container.appendChild(rowDiv);
    validateProductRows();
}

function validateProductRows() {
    const rows = document.querySelectorAll('.product-row');
    let valid = true;
    let selectedIds = [];
    rows.forEach(row => {
        const select = row.querySelector('.product-select');
        const quantityInput = row.querySelector('.quantity-input');
        const errorDiv = row.querySelector('.error-message');
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
        if (select.value) {
            if (selectedIds.includes(select.value)) {
                errorDiv.textContent = 'Produit déjà sélectionné.';
                errorDiv.style.display = 'block';
                valid = false;
            }
            selectedIds.push(select.value);
            // Vérifier le stock
            const product = allProducts.find(p => String(p.id) === select.value);
            if (product && Number(quantityInput.value) > product.quantity) {
                errorDiv.textContent = `Stock insuffisant (max: ${product.quantity})`;
                errorDiv.style.display = 'block';
                valid = false;
            }
        }
    });
    // Désactiver le bouton enregistrer si non valide
    document.querySelector('#changeProductsModal .btn-primary').disabled = !valid;
}

function removeProductRow(button) {
    button.closest('.product-row').remove();
}

function saveProductChanges() {
    const orderId = document.getElementById('change_order_id').value;
    const rows = document.querySelectorAll('.product-row');
    const products = [];

    rows.forEach(row => {
        const productId = row.querySelector('.product-select').value;
        const quantity = row.querySelector('.quantity-input').value;

        if (productId && quantity) {
            products.push({
                product_id: productId,
                quantity: parseInt(quantity)
            });
        }
    });

    if (products.length === 0) {
        alert('Veuillez ajouter au moins un produit');
        return;
    }

    // Envoyer la requête AJAX
    fetch('orders/change_products.php', {
        method: 'POST',
        body: new URLSearchParams({
            order_id: orderId,
            products: JSON.stringify(products)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            bootstrap.Modal.getInstance(document.getElementById('changeProductsModal')).hide();
            
            // Mettre à jour les données dans le tableau
            const row = document.querySelector(`tr[data-order-id="${data.order.id}"]`);
            if (row) {
                row.querySelector('td:nth-child(6)').textContent = data.order.products;
                row.querySelector('td:nth-child(7)').textContent = `${data.order.total_amount} DH`;
                row.querySelector('td:nth-child(8)').textContent = `${data.order.commission} DH`;
                
                // Mettre à jour le badge de statut
                const statusBadge = row.querySelector('td:nth-child(9) .badge');
                statusBadge.className = `badge bg-${status_badges['changed']}`;
                statusBadge.textContent = status_translations['changed'];
            }

            // Afficher un message de succès
            alert('Produits modifiés avec succès');
            
            // Recharger la page pour mettre à jour les statistiques
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la modification des produits');
    });
}
</script> 