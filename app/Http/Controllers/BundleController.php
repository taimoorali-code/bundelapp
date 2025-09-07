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
        $shop = $request->query('shop') ?? session('shopify_shop');
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
        $shop = $request->query('shop') ?? session('shopify_shop');
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


public function index(Request $request)
{
    $shopDomain = $request->query('shop') ?? session('shopify_shop');

    $query = Bundle::with('discounts', 'shop');

    if (!empty($shopDomain)) {
        // Get the shop's ID first
        $shop = Shop::where('shop', $shopDomain)->first();
        if ($shop) {
            $query->where('shop_id', $shop->id);
        }
    }

    $bundles = $query->get();

    return view('bundles.index', compact('bundles'));
}



    public function create(Request $request)
    {
        $shop = $request->query('shop') ?? session('shopify_shop');
        $accessToken = Shop::where('shop', $shop)->first()->token;

        // Get first 5 products
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken
        ])->get("https://{$shop}/admin/api/2025-01/products.json?limit=5");

        $defaultProducts = $response->json()['products'] ?? [];

        return view('bundles.create', compact('defaultProducts'));
    }
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'bundle_type' => 'required|in:all,specific',
                'discounts' => 'required|array|min:1',
                'discounts.*.min_qty' => 'required|integer|min:1',
                'discounts.*.discount_value' => 'required|numeric|min:0',
                'products' => 'array'
            ]);
             $shopdata = $request->query('shop') ?? session('shopify_shop');

            $shop = Shop::where('shop', $shopdata)->firstOrFail();

            $products = $validated['bundle_type'] === 'specific' ? $validated['products'] : [null];

            foreach ($products as $productId) {
                $bundle = Bundle::firstOrNew([
                    'shop_id' => $shop->id,
                    'shopify_product_id' => $productId
                ]);

                $bundle->title = $validated['title'] ?? '';
                $bundle->save();

                // Remove old discounts
                $bundle->discounts()->delete();

                foreach ($validated['discounts'] as $discount) {
                    // 1. Create Shopify discount via API
                    $discountCode = $this->createShopifyDiscount(
                        $shop->shop,
                        $shop->token,
                        $discount['discount_value'],
                        $discount['min_qty'],
                        $productId
                    );

                    // 2. Save discount in your database
                    $bundle->discounts()->create([
                        'min_qty' => $discount['min_qty'],
                        'discount_value' => $discount['discount_value'],
                        'shopify_discount_code' => $discountCode['code'],
                        'shopify_price_rule_id' => $discountCode['price_rule_id'],
                    ]);
                }
            }

            return redirect()->route('bundle.setup', ['shop' => $shop->shop])
                ->with('success', 'Bundle saved and Shopify discount created!');

        } catch (\Exception $e) {
            Log::error('Bundle Store Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Create a Shopify discount code
     */
    // private function createShopifyDiscount($shop, $token, $discountPercent, $minQty, $productId = null)
    // {
    //     $client = new \GuzzleHttp\Client();

    //     // Create Price Rule
    //     $res = $client->post("https://$shop/admin/api/2025-07/price_rules.json", [
    //         'headers' => [
    //             'X-Shopify-Access-Token' => $token,
    //             'Content-Type' => 'application/json'
    //         ],
    //         'body' => json_encode([
    //             "price_rule" => [
    //                 "title" => "Bundle " . uniqid(),
    //                 "target_type" => "line_item",
    //                 "target_selection" => $productId ? "entitled" : "all",
    //                 "entitled_product_ids" => $productId ? [$productId] : [],
    //                 "allocation_method" => "across",
    //                 "value_type" => "percentage",
    //                 "value" => -$discountPercent,
    //                 "customer_selection" => "all",
    //                 "starts_at" => now()->toIso8601String(),
    //                 "prerequisite_quantity_range" => [
    //                     "greater_than_or_equal_to" => $minQty
    //                 ]
    //             ]
    //         ])
    //     ]);

    //     $priceRuleData = json_decode($res->getBody(), true);
    //     $priceRuleId = $priceRuleData['price_rule']['id'];

    //     // Create Discount Code
    //     $res2 = $client->post("https://$shop/admin/api/2025-07/price_rules/$priceRuleId/discount_codes.json", [
    //         'headers' => [
    //             'X-Shopify-Access-Token' => $token,
    //             'Content-Type' => 'application/json'
    //         ],
    //         'body' => json_encode([
    //             'discount_code' => [
    //                 'code' => 'BUNDLE-' . strtoupper(uniqid())
    //             ]
    //         ])
    //     ]);

    //     $discountData = json_decode($res2->getBody(), true);

    //     return [
    //         'code' => $discountData['discount_code']['code'],
    //         'price_rule_id' => $priceRuleId
    //     ];
    // }
/**
 * Create an automatic Shopify discount (no code needed)
 */
private function createShopifyDiscount($shop, $token, $discountPercent, $minQty, $productId = null)
{
    $client = new \GuzzleHttp\Client();

    // Create Price Rule (Automatic Discount)
    $res = $client->post("https://$shop/admin/api/2025-07/price_rules.json", [
        'headers' => [
            'X-Shopify-Access-Token' => $token,
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            "price_rule" => [
                "title" => "Bundle " . uniqid(),
                "target_type" => "line_item",
                "target_selection" => $productId ? "entitled" : "all",
                "entitled_product_ids" => $productId ? [$productId] : [],
                "allocation_method" => "across",
                "value_type" => "percentage",
                "value" => -$discountPercent, // Shopify expects negative number for discount
                "customer_selection" => "all",
                "starts_at" => now()->toIso8601String(),
                "prerequisite_quantity_range" => [
                    "greater_than_or_equal_to" => $minQty
                ],
                "once_per_customer" => false,
                "usage_limit" => null,
                "combines_with" => [
                    "order_discounts" => true,
                    "product_discounts" => true,
                    "shipping_discounts" => true
                ]
            ]
        ])
    ]);

    $priceRuleData = json_decode($res->getBody(), true);

    return [
        'price_rule_id' => $priceRuleData['price_rule']['id'],
        'code' => null // automatic discount has no code
    ];
}


    public function destroy($id)
    {
        try {
            $bundle = Bundle::with(['discounts', 'shop'])->findOrFail($id);
            $shop = $bundle->shop;

            foreach ($bundle->discounts as $discount) {
                if (!empty($discount->shopify_price_rule_id)) {
                    try {
                        $client = new \GuzzleHttp\Client();
                        $client->delete("https://{$shop->shop}/admin/api/2025-07/price_rules/{$discount->shopify_price_rule_id}.json", [
                            'headers' => [
                                'X-Shopify-Access-Token' => $shop->token,
                                'Content-Type' => 'application/json'
                            ]
                        ]);
                    } catch (\Exception $ex) {
                        Log::warning("Failed to delete price rule from Shopify: " . $ex->getMessage());
                    }
                }
            }

            // Delete related discounts from DB
            $bundle->discounts()->delete();

            // Delete the bundle itself
            $bundle->delete();

            return redirect()->route('bundles.index')
                ->with('success', 'Bundle and related discounts deleted from Shopify & DB successfully!');
        } catch (\Exception $e) {
            Log::error('Bundle Delete Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->route('bundles.index')
                ->with('error', 'Failed to delete bundle: ' . $e->getMessage());
        }
    }





}
