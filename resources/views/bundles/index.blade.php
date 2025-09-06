@extends('layouts.app')

@section('title', 'Bundles')

@section('content')
    <h2>Bundles</h2>

<a href="{{ route('bundles.create', ['shop' => request('shop')]) }}" class="btn btn-primary mb-3">+ Create Bundle</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Shop</th>
                <th>Shopify Product ID</th>
                <th>Title</th>
                <th>Discounts</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bundles as $bundle)
                <tr>
                    <td>{{ $bundle->id }}</td>
                    <td>{{ $bundle->shop }}</td>
                    <td>{{ $bundle->shopify_product_id }}</td>
                    <td>{{ $bundle->title }}</td>
                    <td>
                        @if($bundle->discounts->count())
                            <ul>
                                @foreach($bundle->discounts as $discount)
                                    <li>Buy {{ $discount->min_qty }} - Get  {{ $discount->discount_value }} {{ $discount->discount_type }} Off</li>
                                @endforeach
                            </ul>
                        @else
                            <span class="text-muted">No discounts</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('bundles.edit', $bundle->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('bundles.destroy', $bundle->id) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
