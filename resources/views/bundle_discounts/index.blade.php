@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Bundle Discounts</h1>

    <a href="{{ route('bundle_discounts.create') }}" class="btn btn-success mb-3">+ Add Discount</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Bundle</th>
                <th>Min Qty</th>
                <th>Type</th>
                <th>Value</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
        @forelse($discounts as $d)
            <tr>
                <td>{{ $d->bundle->title ?? 'N/A' }}</td>
                <td>{{ $d->min_qty }}</td>
                <td>{{ ucfirst($d->discount_type) }}</td>
                <td>
                    {{ $d->discount_type == 'percentage' ? $d->discount_value . '%' : '$' . $d->discount_value }}
                </td>
                <td>{{ $d->created_at->format('Y-m-d') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">No discounts found</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
