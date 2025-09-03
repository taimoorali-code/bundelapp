<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


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
