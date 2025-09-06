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
        $shop = $request->query('shop');
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

            $shop = Shop::where('shop', $request->shop)->firstOrFail();

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
                        'shopify_discount_code' => $discountCode
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
    private function createShopifyDiscount($shop, $token, $discountPercent, $minQty, $productId = null)
    {
        $shopDomain = $shop; // or $shop->shop
        $priceRule = [
            "price_rule" => [
                "title" => "Bundle " . uniqid(),
                "target_type" => "line_item",
                "target_selection" => $productId ? "entitled" : "all",
                "entitled_product_ids" => $productId ? [$productId] : [],
                "allocation_method" => "across",
                "value_type" => "percentage",
                "value" => -$discountPercent,
                "customer_selection" => "all",
                "starts_at" => now()->toIso8601String(),
                "prerequisite_quantity_range" => [
                    "greater_than_or_equal_to" => $minQty
                ]
            ]
        ];

        $client = new \GuzzleHttp\Client();
        $res = $client->post("https://$shopDomain/admin/api/2025-07/price_rules.json", [
            'headers' => [
                'X-Shopify-Access-Token' => $token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($priceRule)
        ]);

        $priceRuleData = json_decode($res->getBody(), true);
        $priceRuleId = $priceRuleData['price_rule']['id'];

        // Create Discount Code
        $res2 = $client->post("https://$shopDomain/admin/api/2025-07/price_rules/$priceRuleId/discount_codes.json", [
            'headers' => [
                'X-Shopify-Access-Token' => $token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'discount_code' => [
                    'code' => 'BUNDLE-' . strtoupper(uniqid())
                ]
            ])
        ]);

        $discountData = json_decode($res2->getBody(), true);
        return $discountData['discount_code']['code'];
    }



    // BundleController.php

    public function edit($id)
    {
        $bundle = Bundle::with('discounts')->findOrFail($id);

        // Fetch default products for the selector (first 5 products from Shopify)
        $shopId = $bundle->shop_id;

        // Fetch the shop by ID
        $shopModel = Shop::findOrFail($shopId);

        // Get the token
        $accessToken = $shopModel->token;

        // Optionally, get the shop domain
        $shopDomain = $shopModel->shop;


        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken
        ])->get("https://{$shopDomain}/admin/api/2025-01/products.json?limit=5");

        $defaultProducts = $response->json()['products'] ?? [];

        return view('bundles.edit', compact('bundle', 'defaultProducts'));
    }

    public function update(Request $request, $id)
    {
        $bundle = Bundle::with('discounts')->findOrFail($id);

        try {
            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'bundle_type' => 'required|in:all,specific',
                'discounts' => 'required|array|min:1',
                'discounts.*.min_qty' => 'required|integer|min:1',
                'discounts.*.discount_value' => 'required|numeric|min:0',
                'products' => 'array'
            ]);

            $shop = Shop::where('shop', $request->shop)->firstOrFail();

            $products = $validated['bundle_type'] === 'specific' ? $validated['products'] : [null];

            // Update bundle for each selected product
            foreach ($products as $productId) {
                $bundle->shopify_product_id = $productId;
                $bundle->title = $validated['title'] ?? '';
                $bundle->bundle_type = $validated['bundle_type'];
                $bundle->save();

                // Remove old discounts
                $bundle->discounts()->delete();

                // Create new discounts
                foreach ($validated['discounts'] as $discount) {
                    $discountCode = $this->createShopifyDiscount(
                        $shop->shop,
                        $shop->token,
                        $discount['discount_value'],
                        $discount['min_qty'],
                        $productId
                    );

                    $bundle->discounts()->create([
                        'min_qty' => $discount['min_qty'],
                        'discount_value' => $discount['discount_value'],
                        'shopify_discount_code' => $discountCode
                    ]);
                }
            }

            return redirect()->route('bundles.edit', $bundle->id)
                ->with('success', 'Bundle updated and Shopify discounts updated!');

        } catch (\Exception $e) {
            Log::error('Bundle Update Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
    public function destroy($id)
    {
        try {
            $bundle = Bundle::with('discounts')->findOrFail($id);

            // Delete related discounts first
            $bundle->discounts()->delete();

            // Delete the bundle itself
            $bundle->delete();

            return redirect()->route('bundles.index')
                ->with('success', 'Bundle deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Bundle Delete Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->route('bundles.index')
                ->with('error', 'Failed to delete bundle: ' . $e->getMessage());
        }
    }




}
