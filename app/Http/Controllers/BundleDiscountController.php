<?php

// app/Http/Controllers/BundleDiscountController.php
namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\BundleDiscount;
use Illuminate\Http\Request;

class BundleDiscountController extends Controller
{
    public function index()
    {
        $discounts = BundleDiscount::with('bundle')->latest()->get();
        return view('bundle_discounts.index', compact('discounts'));
    }

    public function create()
    {
        $bundles = Bundle::all();
        return view('bundle_discounts.create', compact('bundles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bundle_id' => 'required|exists:bundles,id',
            'min_qty' => 'required|integer|min:1',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
        ]);

        BundleDiscount::create($data);

        return redirect()->route('bundle_discounts.index')->with('success', 'Discount created successfully!');
    }
}
