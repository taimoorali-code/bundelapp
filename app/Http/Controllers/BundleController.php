<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function index()
    {
        $bundles = Bundle::with('discounts')->get();
        return view('bundles.index', compact('bundles'));
    }

    public function create()
    {
        return view('bundles.create');
    }

    public function store(Request $request)
    {
        $bundle = Bundle::create([
            'shop_id' => auth()->user()->id, // current shop
            'shopify_product_id' => $request->product_id,
            'title' => $request->title,
        ]);

        foreach ($request->discounts as $discount) {
            $bundle->discounts()->create($discount);
        }

        return redirect()->route('bundles.index');
    }
}
