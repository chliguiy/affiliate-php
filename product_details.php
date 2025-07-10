<?php
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once 'config/database.php';
    require_once 'ApiDelivery.php';

    // Vérifier si l'ID du produit est fourni
    if (!isset($_GET['id'])) {
        header('Location: products.php');
        exit;
    }

    // Connexion à la base de données
    $database = new Database();
    $conn = $database->getConnection();
    $apiDelivery = new ApiDelivery();

    $cities = json_decode($apiDelivery->getCities(), true);

    // Récupération du produit
    $stmt = $conn->prepare("
    SELECT p.*, c.name as category_name,
           COALESCE(p.sale_price, p.seller_price) as display_price,
           p.seller_price,
           p.reseller_price,
           p.sale_price,
           p.commission_rate
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch();

    // Rediriger si le produit n'existe pas
    if (!$product) {
        header('Location: products.php');
        exit;
    }

    // Récupérer les images du produit
    $stmt = $conn->prepare("
    SELECT image_url, is_primary, alt_text, sort_order 
    FROM product_images 
    WHERE product_id = ? 
    ORDER BY is_primary DESC, sort_order ASC, id ASC
");
    $stmt->execute([$_GET['id']]);
    $productImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Extraire les URLs des images pour la compatibilité
    $images = array_column($productImages, 'image_url');

    // Récupérer les couleurs du produit avec stock
    $stmt = $conn->prepare("
    SELECT c.id, c.name, c.color_code, pc.stock
    FROM product_colors pc
    JOIN colors c ON pc.color_id = c.id
    WHERE pc.product_id = ? AND pc.stock > 0
    ORDER BY c.name
");
    $stmt->execute([$_GET['id']]);
    $productColors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les tailles du produit avec stock
    $stmt = $conn->prepare("
    SELECT s.id, s.name, ps.stock as stock
    FROM product_sizes ps
    JOIN sizes s ON ps.size_id = s.id
    WHERE ps.product_id = ? AND ps.stock > 0
    ORDER BY s.name
");
    $stmt->execute([$_GET['id']]);
    $productSizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .product-main-image {
            width: 100%;
            max-height: 600px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .product-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .product-thumbnail:hover {
            border-color: #0d6efd;
            transform: scale(1.05);
        }

        .product-thumbnail.active {
            border-color: #0d6efd;
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
        }

        .thumbnails-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .image-counter {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .main-image-container {
            position: relative;
        }

        .color-option,
        .size-option {
            position: relative;
        }

        .color-option input[type="radio"],
        .size-option input[type="radio"] {
            display: none;
        }

        .color-option label,
        .size-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            padding: 0.5rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .color-option input[type="radio"]:checked+label,
        .size-option input[type="radio"]:checked+label {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .color-preview {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 0.5rem;
        }

        .color-name {
            font-size: 0.9rem;
            font-weight: 500;
            color: #333;
            text-align: center;
            margin-top: 0.25rem;
            min-height: 1.2rem;
        }

        .size-name {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stock-info {
            font-size: 0.8rem;
            color: #666;
        }

        .colors-container,
        .sizes-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
            background-color: #fff;
            border-radius: 8px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background-color: #f8f9fa;
        }

        #quantity {
            width: 60px;
            height: 40px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1.1rem;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 1rem;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background-color: #0056b3;
        }

        .price {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }

        .original-price {
            text-decoration: line-through;
            color: #666;
            margin-right: 1rem;
        }

        .discount-price {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
        }

        /* Styles professionnels pour les prix */
        .pricing-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .pricing-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .pricing-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
            color: #28a745;
        }

        .pricing-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #495057;
            margin: 0;
        }

        .price-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .price-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .price-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .price-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .price-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #212529;
        }

        .price-currency {
            font-size: 1rem;
            color: #6c757d;
            margin-left: 0.25rem;
        }

        .sale-price {
            color: #dc3545;
        }

        .original-price-display {
            color: #6c757d;
            text-decoration: line-through;
        }

        .commission-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 6px;
            padding: 0.75rem;
            margin-top: 1rem;
        }

        .commission-text {
            font-size: 0.9rem;
            color: #1976d2;
            margin: 0;
            text-align: center;
        }

        .discount-badge {
            background: linear-gradient(45deg, #dc3545, #e74c3c);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-left: 0.5rem;
        }

        .price-highlight {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="main-image-container">
                    <img src="<?php echo !empty($images) ? $images[0] : 'assets/images/placeholder.jpg'; ?>"
                        class="product-main-image" id="mainImage"
                        alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php if (!empty($productImages)): ?>
                        <div class="image-counter">
                            <?php echo count($productImages); ?> image<?php echo count($productImages) > 1 ? 's' : ''; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($productImages)): ?>
                    <div class="thumbnails-container">
                        <div class="w-100 mb-2">
                            <h6 class="text-muted mb-2">
                                <i class="fas fa-images me-1"></i>
                                Images du produit (<?php echo count($productImages); ?>)
                            </h6>
                        </div>
                        <?php foreach ($productImages as $index => $imageData): ?>
                            <div class="thumbnail-wrapper" style="position: relative;">
                                <img src="<?php echo $imageData['image_url']; ?>"
                                    class="product-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                    onclick="changeMainImage('<?php echo $imageData['image_url']; ?>', this)"
                                    alt="<?php echo $imageData['alt_text'] ?: 'Image ' . ($index + 1) . ' - ' . htmlspecialchars($product['name']); ?>"
                                    title="<?php echo $imageData['alt_text'] ?: 'Image ' . ($index + 1); ?>">
                                <?php if ($index === 0): ?>
                                    <div
                                        style="position: absolute; top: -5px; right: -5px; background: #28a745; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: bold;">
                                        <i class="fas fa-star"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>

                    <!-- Section des prix professionnelle -->
                    <div class="pricing-section">
                        <div class="pricing-header">
                            <i class="fas fa-tags pricing-icon"></i>
                            <h3 class="pricing-title">Informations de prix</h3>
                        </div>

                        <div class="price-grid">
                            <div class="price-item">
                                <div class="price-label">Prix de vente</div>
                                <div class="price-value">
                                    <?php echo number_format($product['seller_price'] ?? 0, 2); ?>
                                    <span class="price-currency">MAD</span>
                                </div>
                            </div>

                            <div class="price-item">
                                <div class="price-label">Prix revendeur</div>
                                <div class="price-value">
                                    <?php echo number_format($product['reseller_price'] ?? 0, 2); ?>
                                    <span class="price-currency">MAD</span>
                                </div>
                            </div>
                        </div>

                        <div class="commission-info">
                            <p class="commission-text">
                                <i class="fas fa-percentage me-1"></i>
                                Marge revendeur :
                                <?php echo number_format(($product['reseller_price'] ?? 0) - ($product['seller_price'] ?? 0), 2); ?>
                                MAD
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($productColors)): ?>
                        <div class="colors-section mb-4">
                            <h4>🎨 Couleurs disponibles</h4>
                            <div class="colors-container">
                                <?php foreach ($productColors as $color): ?>
                                    <div class="color-option">
                                        <input type="radio" name="color" id="color-<?php echo $color['id']; ?>"
                                            value="<?php echo $color['id']; ?>"
                                            data-color-name="<?php echo htmlspecialchars($color['name']); ?>"
                                            data-color-code="<?php echo $color['color_code']; ?>" required>
                                        <label for="color-<?php echo $color['id']; ?>">
                                            <div class="color-preview"
                                                style="background-color: <?php echo $color['color_code']; ?>;"></div>
                                            <span class="color-name"><?php echo htmlspecialchars($color['name']); ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="selectedColorDisplay" class="mt-3" style="display: none;">
                                <strong>Couleur sélectionnée :</strong>
                                <div class="d-flex align-items-center mt-2">
                                    <div class="color-preview me-2" id="selectedColorPreview"></div>
                                    <span id="selectedColorName"></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($productSizes)): ?>
                        <div class="sizes-section mb-4">
                            <h4>📏 Tailles disponibles</h4>
                            <div class="sizes-container">
                                <?php foreach ($productSizes as $size): ?>
                                    <div class="size-option">
                                        <input type="radio" name="size" id="size-<?php echo $size['id']; ?>"
                                            value="<?php echo $size['id']; ?>" required>
                                        <label for="size-<?php echo $size['id']; ?>">
                                            <span class="size-name"><?php echo htmlspecialchars($size['name']); ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="actions-section">
                        <form method="POST" action="process_order.php" id="orderForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="quantity" class="form-label">Quantité</label>
                                    <div class="quantity-selector">
                                        <button type="button" class="quantity-btn"
                                            onclick="decrementQuantity()">-</button>
                                        <input type="number" name="products[<?php echo $product['id']; ?>]"
                                            id="quantity" value="1" min="1" max="99" required>
                                        <button type="button" class="quantity-btn"
                                            onclick="incrementQuantity()">+</button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="final_sale_price" class="form-label">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        Prix de vente final (prix payé par le client)
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="final_sale_price"
                                            id="final_sale_price" min="<?php echo $product['seller_price']; ?>"
                                            step="0.01" value="<?php echo $product['reseller_price']; ?>" required>
                                        <span class="input-group-text">MAD</span>
                                    </div>
                                    <div class="input-group mt-2">
                                        <span class="input-group-text">Profit affilié</span>
                                        <input type="text" class="form-control" id="affiliate_profit_display" readonly>
                                        <span class="input-group-text">MAD</span>
                                    </div>
                                    <input type="hidden" name="affiliate_margin" id="affiliate_margin_hidden" value="0">
                                    <div id="affiliate-margin-info" class="mt-2 p-2 bg-light rounded"
                                        style="display: none;">
                                        <small>
                                            <strong>Marge affilié estimée :</strong>
                                            <span id="affiliate-margin-amount" class="text-success"></span> MAD
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Deuxième ligne : Nom client, Téléphone, Email -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="customer_name" class="form-label">Nom Destinataire (client)</label>
                                    <input type="text" class="form-control" name="customer_name" id="customer_name"
                                        required>
                                </div>
                                <div class="col-md-4">
                                    <label for="customer_phone" class="form-label">Numéro téléphone</label>
                                    <input type="text" class="form-control" name="customer_phone" id="customer_phone"
                                        pattern="0[6-7][0-9]{8}" placeholder="06/07xxxxxxxx" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="customer_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="customer_email" id="customer_email"
                                        required>
                                </div>
                            </div>

                            <!-- Troisième ligne : Ville et Tarif de Livraison -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="customer_city" class="form-label">Ville</label>
                                    <select class="form-control" name="customer_city" id="customer_city" required>
                                        <option value="">Sélectionnez une ville</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?php echo htmlspecialchars($city['id']); ?>"
                                                data-delivery="<?php echo htmlspecialchars($city['delivered_fees']); ?>">
                                                <?php echo htmlspecialchars($city['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="delivery_fee" class="form-label">Tarif de Livraison</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="delivery_fee" id="delivery_fee"
                                            min="0" step="0.01" readonly>
                                        <span class="input-group-text">MAD</span>
                                    </div>
                                    <div class="form-text">
                                        <small class="text-muted">Tarif calculé automatiquement selon la ville</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Quatrième ligne : Adresse (pleine largeur) -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="customer_address" class="form-label">Adresse de livraison</label>
                                    <textarea class="form-control" name="customer_address" id="customer_address"
                                        rows="2" required></textarea>
                                </div>
                            </div>

                            <!-- Cinquième ligne : Commentaire (pleine largeur) -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="comment" class="form-label">Commentaire</label>
                                    <textarea class="form-control" name="comment" id="comment" rows="2"></textarea>
                                </div>
                            </div>

                            <input type="hidden" name="color_id" id="selectedColor">
                            <input type="hidden" name="size_id" id="selectedSize">
                            <button type="submit" class="add-to-cart-btn"><i
                                    class="fas fa-shopping-cart me-2"></i>Commander</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeMainImage(src, thumbnailElement) {
            document.getElementById('mainImage').src = src;

            // Retirer la classe active de tous les thumbnails
            document.querySelectorAll('.product-thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });

            // Ajouter la classe active au thumbnail cliqué
            if (thumbnailElement) {
                thumbnailElement.classList.add('active');
            }
        }

        function decrementQuantity() {
            const input = document.getElementById('quantity');
            if (input.value > 1) {
                input.value = parseInt(input.value) - 1;
                updateAffiliateMargin(); // Recalculer le total
            }
        }

        function incrementQuantity() {
            const input = document.getElementById('quantity');
            if (input.value < 99) {
                input.value = parseInt(input.value) + 1;
                updateAffiliateMargin(); // Recalculer le total
            }
        }

        // Sélection couleur/size obligatoire et envoi dans le formulaire
        document.querySelectorAll('input[name="color"]').forEach(el => {
            el.addEventListener('change', function () {
                document.getElementById('selectedColor').value = this.value;

                // Afficher la couleur sélectionnée
                const colorName = this.getAttribute('data-color-name');
                const colorCode = this.getAttribute('data-color-code');

                if (colorName && colorCode) {
                    document.getElementById('selectedColorName').textContent = colorName;
                    document.getElementById('selectedColorPreview').style.backgroundColor = colorCode;
                    document.getElementById('selectedColorDisplay').style.display = 'block';
                }
            });
        });
        document.querySelectorAll('input[name="size"]').forEach(el => {
            el.addEventListener('change', function () {
                document.getElementById('selectedSize').value = this.value;
            });
        });

        // Calcul automatique de la marge affilié
        const finalSalePriceInput = document.getElementById('final_sale_price');
        const affiliateMarginInfo = document.getElementById('affiliate-margin-info');
        const affiliateMarginAmount = document.getElementById('affiliate-margin-amount');
        const affiliateMarginHidden = document.getElementById('affiliate_margin_hidden');
        const affiliateProfitDisplay = document.getElementById('affiliate_profit_display');

        function updateAffiliateMargin() {
            const finalSalePrice = parseFloat(finalSalePriceInput.value) || 0;
            const sellerPrice = <?php echo $product['seller_price']; ?>;
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            const deliveryFee = parseFloat(document.getElementById('delivery_fee').value) || 0;
            // Profit affilié = prix de vente final - (prix admin * quantité) - livraison
            const margin = finalSalePrice - (sellerPrice * quantity) - deliveryFee;
            affiliateMarginAmount.textContent = margin.toFixed(2);
            affiliateMarginHidden.value = margin.toFixed(2);
            affiliateProfitDisplay.value = margin.toFixed(2);
            affiliateMarginInfo.style.display = (margin >= 0) ? 'block' : 'none';
            affiliateMarginAmount.className = margin > 0 ? 'text-success' : 'text-danger';
        }

        // S'assurer que le champ final_sale_price a une valeur par défaut
        function initializeFinalSalePrice() {
            const finalSalePrice = finalSalePriceInput.value;
            if (!finalSalePrice || finalSalePrice === '0' || finalSalePrice === '') {
                finalSalePriceInput.value = <?php echo $product['reseller_price']; ?>;
            }
            updateAffiliateMargin();
        }

        finalSalePriceInput.addEventListener('input', updateAffiliateMargin);
        document.getElementById('quantity').addEventListener('input', updateAffiliateMargin);
        document.getElementById('delivery_fee').addEventListener('input', updateAffiliateMargin);

        // Calcul automatique des frais de livraison
        document.addEventListener('DOMContentLoaded', function () {
            const citySelect = document.getElementById('customer_city');
            const deliveryFeeInput = document.getElementById('delivery_fee');

            function updateDeliveryFee() {
                const selectedOption = citySelect.options[citySelect.selectedIndex];
                console.log(selectedOption.attributes);  // ✅ Logs attributes
                const deliveryFee = selectedOption.getAttribute('data-delivery');
                console.log(deliveryFee); // ✅ Will now work after user selects a city

                // Optional: Update delivery fee field
                if (deliveryFee) {
                    deliveryFeeInput.value = parseFloat(deliveryFee).toFixed(2);
                } else {
                    deliveryFeeInput.value = '';
                }
                updateAffiliateMargin(); // Recalcule le profit affilié
            }

            // Initialiser le prix de vente final et les frais de livraison
            initializeFinalSalePrice();
            updateDeliveryFee();

            // Forcer le calcul du total au chargement de la page
            setTimeout(function () {
                updateAffiliateMargin();
            }, 100);

            // Update when the city selection changes
            citySelect.addEventListener('change', updateDeliveryFee);
        });

        // Validation du formulaire
        document.getElementById('orderForm').addEventListener('submit', function (e) {
            if (!document.getElementById('selectedColor').value) {
                alert('Veuillez sélectionner une couleur');
                e.preventDefault();
                return false;
            }
            if (!document.getElementById('selectedSize').value) {
                alert('Veuillez sélectionner une taille');
                e.preventDefault();
                return false;
            }

            // Validation du prix de vente
            const finalSalePrice = parseFloat(document.getElementById('final_sale_price').value);
            const sellerPrice = <?php echo $product['seller_price']; ?>;
            if (finalSalePrice < sellerPrice) {
                alert('Le prix de vente ne peut pas être inférieur au prix de vente minimum (' + sellerPrice + ' MAD)');
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>

</html>