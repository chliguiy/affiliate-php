function toggleDiscount() {
    const discountFields = document.getElementById('discountFields');
    const hasDiscount = document.getElementById('hasDiscount').checked;
    discountFields.style.display = hasDiscount ? 'block' : 'none';
}

function toggleAffiliateSelection() {
    const specificAffiliates = document.getElementById('specificAffiliates');
    const visibility = document.getElementById('affiliateVisibility').value;
    specificAffiliates.style.display = visibility === 'specific' ? 'block' : 'none';
}

// Supprimer ces fonctions
function addColor() {
    const container = document.getElementById('colorsContainer');
    const newColor = document.createElement('div');
    newColor.className = 'color-entry mb-2';
    newColor.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="color" name="colors[]" class="form-control form-control-color" required>
                    <input type="number" name="color_stocks[]" class="form-control" placeholder="Stock" min="0" required>
                </div>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-danger remove-color" onclick="removeColor(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newColor);
}

function removeColor(button) {
    button.closest('.color-entry').remove();
}

function addSize() {
    const container = document.getElementById('sizesContainer');
    const newSize = document.createElement('div');
    newSize.className = 'size-entry mb-2';
    newSize.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" name="sizes[]" class="form-control" placeholder="Taille" required>
                    <input type="number" name="size_stocks[]" class="form-control" placeholder="Stock" min="0" required>
                </div>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-danger remove-size" onclick="removeSize(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newSize);
}

function removeSize(button) {
    button.closest('.size-entry').remove();
}