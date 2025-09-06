@extends('layouts.app')

@section('title', 'Edit Bundle')

@section('content')
    <h2>Edit Bundle</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('bundles.update', $bundle->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="shop_id" class="form-label">Shop ID</label>
            <input type="text" name="shop_id" id="shop_id" class="form-control" value="{{ old('shop_id', $bundle->shop_id) }}" required>
        </div>

        <div class="mb-3">
            <label for="shopify_product_id" class="form-label">Shopify Product ID</label>
            <input type="text" name="shopify_product_id" id="shopify_product_id" class="form-control" value="{{ old('shopify_product_id', $bundle->shopify_product_id) }}" required>
        </div>

        <div class="mb-3">
            <label for="title" class="form-label">Bundle Title</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $bundle->title) }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Update Bundle</button>
        <a href="{{ route('bundles.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
@endsection
