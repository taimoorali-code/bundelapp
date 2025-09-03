<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bundle Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .bundle-card {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .product-list div {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
<div class="bundle-card">
    <h2 class="mb-4 text-center">Create a Bundle</h2>

    <!-- Bundle Type Selector -->
    <div class="mb-3">
        <label for="bundle_type" class="form-label">Apply Bundle To:</label>
        <select id="bundle_type" class="form-select">
            <option value="all">All Products</option>
            <option value="specific">Specific Products</option>
        </select>
    </div>

    <!-- Product Selector (AJAX search) -->
    <div id="product-selector" style="display: none;">
        <label for="product-search" class="form-label">Search Products:</label>
        <input type="text" id="product-search" class="form-control mb-2" placeholder="Search products...">
        <div id="product-results" class="product-list">
            @foreach($defaultProducts as $product)
            {{ dd($product) }}
                <div>
                    <input type="checkbox" name="products[]" value="{{ $product->id }}"> {{ $product->title }}
                </div>
            @endforeach
        </div>
    </div>

    <!-- Bundle Discounts -->
    <div id="bundle-discounts" class="mt-4">
        <h5>Bundle Discounts</h5>
        <div class="discount-row row g-2 mb-2">
            <div class="col-md-4">
                <input type="number" name="quantity[]" class="form-control" placeholder="Buy X">
            </div>
            <div class="col-md-4">
                <input type="number" name="discount[]" class="form-control" placeholder="Save Y%">
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-danger remove-discount w-100">Remove</button>
            </div>
        </div>
        <button type="button" id="add-discount" class="btn btn-primary mt-2">Add Another Discount</button>
    </div>

    <!-- Save Button -->
    <div class="mt-4 text-center">
        <button type="submit" class="btn btn-success btn-lg">Save Bundle</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle product selector
    const bundleType = document.getElementById('bundle_type');
    const productSelector = document.getElementById('product-selector');
    bundleType.addEventListener('change', () => {
        if(bundleType.value === 'specific'){
            productSelector.style.display = 'block';
        } else {
            productSelector.style.display = 'none';
        }
    });

    // Add/Remove Discount Rows
    const addDiscount = document.getElementById('add-discount');
    const discountContainer = document.getElementById('bundle-discounts');
    addDiscount.addEventListener('click', () => {
        const row = document.createElement('div');
        row.classList.add('discount-row', 'row', 'g-2', 'mb-2');
        row.innerHTML = `
            <div class="col-md-4">
                <input type="number" name="quantity[]" class="form-control" placeholder="Buy X">
            </div>
            <div class="col-md-4">
                <input type="number" name="discount[]" class="form-control" placeholder="Save Y%">
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-danger remove-discount w-100">Remove</button>
            </div>
        `;
        discountContainer.appendChild(row);
        attachRemoveListeners();
    });

    function attachRemoveListeners() {
        document.querySelectorAll('.remove-discount').forEach(btn => {
            btn.onclick = () => btn.closest('.discount-row').remove();
        });
    }
    attachRemoveListeners();

    // AJAX product search
    const searchInput = document.getElementById('product-search');
    const productResults = document.getElementById('product-results');
    let timeout = null;
    searchInput.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const query = searchInput.value;
            if(query.length < 2) return;

            fetch(`/search-products?q=${query}`)
                .then(res => res.json())
                .then(data => {
                    let html = '';
                    data.forEach(p => {
                        html += `<div>
                                    <input type="checkbox" name="products[]" value="${p.id}"> ${p.title}
                                 </div>`;
                    });
                    productResults.innerHTML = html;
                });
        }, 300);
    });
</script>
</body>
</html>
