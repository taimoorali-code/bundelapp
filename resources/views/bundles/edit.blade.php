@extends('layouts.app')

@section('title', 'Edit Bundle')

@section('content')
    <div class="bundle-card">
        <h2 class="mb-4 text-center">Edit Bundle</h2>

        <!-- Alerts -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Bundle Form -->
        <form action="{{ route('bundles.update', $bundle->id) }}" method="POST">
            @csrf
            @method('PUT')

            <input type="hidden" name="shop" value="{{ $bundle->shop }}">
            <input type="hidden" name="bundle_type" id="bundle_type_input" value="{{ $bundle->bundle_type }}">

            <!-- Title -->
            <div class="mb-3">
                <label for="bundle_title" class="form-label">Bundle Title</label>
                <input type="text" class="form-control" name="title" id="bundle_title"
                    value="{{ old('title', $bundle->title) }}" placeholder="e.g. Buy More & Save" required>
            </div>

            <!-- Bundle Type -->
            <div class="mb-3">
                <label for="bundle_type" class="form-label">Apply Bundle To:</label>
                <select id="bundle_type" class="form-select">
                    <option value="all" {{ is_null($bundle->shopify_product_id) ? 'selected' : '' }}>All Products
                    </option>
                    <option value="specific" {{ !is_null($bundle->shopify_product_id) ? 'selected' : '' }}>Specific Products
                    </option>
                </select>
            </div>

            <!-- Product Selector -->
            <div id="product-selector" style="display: {{ $bundle->bundle_type == 'specific' ? 'block' : 'none' }};">
                <label for="product-search" class="form-label">Search Products:</label>
                <input type="text" id="product-search" class="form-control mb-2" placeholder="Search products...">
                <div id="product-results" class="product-list">
                    @foreach ($defaultProducts as $product)
                        <div>
                            <input type="checkbox" name="products[]" value="{{ $product['id'] }}"
                                {{ $bundle->shopify_product_id == $product['id'] ? 'checked' : '' }}>
                            {{ $product['title'] }}
                        </div>
                    @endforeach
                </div>

            </div>

            <!-- Discounts -->
            <div id="bundle-discounts" class="mt-4">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h5 class="mb-3">Bundle Discounts</h5>
                    <button type="button" id="add-discount" class="btn btn-outline-primary mb-3">+ Add Discount</button>
                </div>

                @foreach ($bundle->discounts as $index => $discount)
                    <div class="discount-row row g-2 mb-2">
                        <div class="col-md-4">
                            <input type="number" name="discounts[{{ $index }}][min_qty]" class="form-control"
                                value="{{ $discount->min_qty }}" placeholder="Buy X" required>
                        </div>
                        <div class="col-md-4">
                            <input type="number" name="discounts[{{ $index }}][discount_value]"
                                class="form-control" value="{{ $discount->discount_value }}" placeholder="Save Y%"
                                required>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-outline-danger remove-discount w-100">Remove</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Submit -->
            <div class="mt-4 text-center">
                <button type="submit" class="btn btn-success btn-lg">Update Bundle</button>
            </div>
        </form>
    </div>

    <script>
        // Toggle product selector
        const bundleType = document.getElementById('bundle_type');
        const productSelector = document.getElementById('product-selector');
        const bundleTypeInput = document.getElementById('bundle_type_input');

        bundleType.addEventListener('change', () => {
            const isSpecific = bundleType.value === 'specific';
            productSelector.style.display = isSpecific ? 'block' : 'none';
            bundleTypeInput.value = bundleType.value;
        });

        // Add/Remove discounts
        let discountIndex = {{ $bundle->discounts->count() }};
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
                <input type="number" name="discounts[${discountIndex}][discount_value]" class="form-control" placeholder="Save Y%" required>
            </div>
            <div class="col-md-4">
                <button type="button" class="btn btn-outline-danger remove-discount w-100">Remove</button>
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

        // AJAX product search (same as create)
        const searchInput = document.getElementById('product-search');
        const productResults = document.getElementById('product-results');
        let timeout = null;

      searchInput.addEventListener('input', () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        const query = searchInput.value;
        const shop = "{{ request('shop') }}";
        if (query.length < 2) return;

        fetch(`/search-products?q=${query}&shop=${shop}`)
            .then(res => res.json())
            .then(data => {
                let html = '';

                // Add the currently selected product first (if not in results)
                const currentProductId = {{ $bundle->shopify_product_id ?? 'null' }};
                let currentIncluded = false;

                data.forEach(p => {
                    const isChecked = p.id == currentProductId;
                    if (isChecked) currentIncluded = true;
                    html += `<div>
                        <input type="checkbox" name="products[]" value="${p.id}" ${isChecked ? 'checked' : ''}>
                        ${p.title} ${isChecked ? '<span class="badge bg-success">Selected</span>' : ''}
                    </div>`;
                });

                // If the current product is not in search results, show it at top
                if (currentProductId && !currentIncluded) {
                    html = `<div>
                        <input type="checkbox" name="products[]" value="${currentProductId}" checked>
                        Current Product (ID: ${currentProductId}) <span class="badge bg-success">Selected</span>
                    </div>` + html;
                }

                productResults.innerHTML = html;
            });
    }, 300);
});

    </script>
@endsection
