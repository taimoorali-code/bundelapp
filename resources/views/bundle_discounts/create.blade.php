@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Create Bundle Discount</h1>

    <form action="{{ route('bundle_discounts.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="bundle_id" class="form-label">Select Bundle</label>
            <select name="bundle_id" id="bundle_id" class="form-control" required>
                <option value="">-- Choose Bundle --</option>
                @foreach($bundles as $bundle)
                    <option value="{{ $bundle->id }}">{{ $bundle->title }}</option>
                @endforeach
            </select>
            @error('bundle_id') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="min_qty" class="form-label">Minimum Quantity</label>
            <input type="number" name="min_qty" class="form-control" required>
            @error('min_qty') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Discount Type</label>
            <select name="discount_type" class="form-control" required>
                <option value="percentage">Percentage (%)</option>
                <option value="fixed">Fixed Amount ($)</option>
            </select>
            @error('discount_type') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="discount_value" class="form-label">Discount Value</label>
            <input type="number" name="discount_value" step="0.01" class="form-control" required>
            @error('discount_value') <div class="text-danger">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Save Discount</button>
    </form>
</div>
@endsection
