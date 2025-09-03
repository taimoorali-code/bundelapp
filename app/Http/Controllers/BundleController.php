<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BundleController extends Controller
{

 public function showBundleSetup(Request $request)
{
    $shop = $request->query('shop');
    $accessToken = Shop::where('shop', $shop)->first()->token;

    // Get first 5 products
    $response = Http::withHeaders([
        'X-Shopify-Access-Token' => $accessToken
    ])->get("https://{$shop}/admin/api/2025-01/products.json?limit=5");

    $defaultProducts = $response->json()['products'] ?? [];

    return view('welcome', compact('defaultProducts'));
}

// AJAX route for product search
public function searchProducts(Request $request)
{
    $shop = $request->query('shop');
    $accessToken = Shop::where('shop', $shop)->first()->token;
    $query = $request->query('q');

    $response = Http::withHeaders([
        'X-Shopify-Access-Token' => $accessToken
    ])->get("https://{$shop}/admin/api/2025-01/products.json", [
        'title' => $query,
        'limit' => 10
    ]);

    $products = $response->json()['products'] ?? [];
    return response()->json($products);
}


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
    try {
        // Common validation first
        $rules = [
            'title' => 'nullable|string|max:255',
            'bundle_type' => 'required|in:all,specific',
            'discounts' => 'required|array|min:1',
            'discounts.*.min_qty' => 'required|integer|min:1',
            'discounts.*.discount_value' => 'required|numeric|min:0',
        ];

        // Only validate products if specific selected
        if ($request->bundle_type === 'specific') {
            $rules['products'] = 'required|array|min:1';
        }

        $request->validate($rules);

        $shop = Shop::where('shop', $request->shop)->firstOrFail();

        // Create bundle
        $bundle = Bundle::create([
            'shop_id' => $shop->id,
            'title' => $request->title ?? '',
            'applies_to_all' => $request->bundle_type === 'all',
        ]);

        // If specific products, attach them
        if ($request->bundle_type === 'specific' && !empty($request->products)) {
            foreach ($request->products as $productId) {
                $bundle->products()->create([
                    'shopify_product_id' => $productId,
                ]);
            }
        }

        // Add discounts
        foreach ($request->discounts as $discount) {
            $bundle->discounts()->create($discount);
        }

        return redirect()
            ->route('bundle.setup', ['shop' => $request->shop])
            ->with('success', 'Bundle saved successfully!');
    } catch (\Exception $e) {
        Log::error('Bundle Store Error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'shop'  => $request->shop,
            'data'  => $request->all()
        ]);

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Something went wrong: ' . $e->getMessage());
    }
}


}
