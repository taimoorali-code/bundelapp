<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Bundle</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

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

    <!-- Success Alert -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Bundle Form -->
    <form action="{{ route('bundle.store') }}" method="POST">
        @csrf
        <input type="hidden" name="shop" value="{{ request('shop') }}">

        <!-- Bundle Title -->
        <div class="mb-3">
            <label for="bundle_title" class="form-label">Bundle Title</label>
            <input type="text" class="form-control" name="title" id="bundle_title" placeholder="Enter bundle title" required>
        </div>

        <!-- Bundle Type Selector -->
        <div class="mb-3">
            <label for="bundle_type" class="form-label">Apply Bundle To:</label>
            <select id="bundle_type" class="form-select">
                <option value="all">All Products</option>
                <option value="specific">Specific Products</option>
            </select>
        </div>

        <!-- Product Selector -->
        <div id="product-selector" style="display: none;">
            <label for="product-search" class="form-label">Search Products:</label>
            <input type="text" id="product-search" class="form-control mb-2" placeholder="Search products...">
            <div id="product-results" class="product-list">
                @forelse($defaultProducts as $product)
                    <div>
                        <input type="checkbox" name="products[]" value="{{ $product['id'] }}"> {{ $product['title'] }}
                    </div>
                @empty
                    <p class="text-muted">No products to display. Use the search box to find products.</p>
                @endforelse
            </div>
        </div>

        <!-- Bundle Discounts -->
        <div id="bundle-discounts" class="mt-4">
            <h5>Bundle Discounts</h5>
            <div class="discount-row row g-2 mb-2">
                <div class="col-md-4">
                    <input type="number" name="discounts[0][min_qty]" class="form-control" placeholder="Buy X" required>
                </div>
                <div class="col-md-4">
                    <input type="number" name="discounts[0][discount]" class="form-control" placeholder="Save Y%" required>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-danger remove-discount w-100">Remove</button>
                </div>
            </div>
            <button type="button" id="add-discount" class="btn btn-primary mt-2">Add Another Discount</button>
        </div>

        <!-- Submit Button -->
        <div class="mt-4 text-center">
            <button type="submit" class="btn btn-success btn-lg">Save Bundle</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle product selector
    const bundleType = document.getElementById('bundle_type');
    const productSelector = document.getElementById('product-selector');
    bundleType.addEventListener('change', () => {
        productSelector.style.display = bundleType.value === 'specific' ? 'block' : 'none';
    });

    // Add/Remove Discount Rows
    let discountIndex = 1;
    const addDiscount = document.getElementById('add-discount');
    const discountContainer = document.getElementById('bundle-discounts');
    addDiscount.addEventListener('click', () => {
        const row = document.createElement('div');
        row.classList.add('discount-row', 'row', 'g-2', 'mb-2');
        row.innerHTML = `
            <div class="col-md-4">
                <input type="number" name="discounts[${discountIndex}][min_qty]" class="form-control" placeholder="Buy X" required>
            </div>
            <div class="col-md-4">
                <input type="number" name="discounts[${discountIndex}][discount]" class="form-control" placeholder="Save Y%" required>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-danger remove-discount w-100">Remove</button>
            </div>
        `;
        discountContainer.appendChild(row);
        attachRemoveListeners();
        discountIndex++;
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
            const shop = "{{ request('shop') }}";
            if(query.length < 2) return;

            fetch(`/search-products?q=${query}&shop=${shop}`)
                .then(res => res.json())
                .then(data => {
                    let html = '';
                    data.forEach(p => {
                        html += `<div>
                                    <input type="checkbox" name="products[]" value="${p['id']}"> ${p['title']}
                                 </div>`;
                    });
                    productResults.innerHTML = html;
                });
        }, 300);
    });
</script>
</body>
</html>
