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
                    $shop->access_token,
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
        Log::error('Bundle Store Error: ' . $e->getMessage(), ['trace'=>$e->getTraceAsString()]);
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


        // public function store(Request $request)
        // {
        //     try {
        //         // Validation
        //         $rules = [
        //             'title' => 'nullable|string|max:255',
        //             'bundle_type' => 'required|in:all,specific',
        //             'discounts' => 'required|array|min:1',
        //             'discounts.*.min_qty' => 'required|integer|min:1',
        //             'discounts.*.discount_value' => 'required|numeric|min:0',
        //         ];

        //         if ($request->bundle_type === 'specific') {
        //             $rules['products'] = 'required|array|min:1';
        //         }

        //         $validated = $request->validate($rules);

        //         $shop = Shop::where('shop', $request->shop)->firstOrFail();

        //         // CASE 1: ALL PRODUCTS
        //         if ($validated['bundle_type'] === 'all') {
        //             // find existing bundle
        //             $bundle = Bundle::where('shop_id', $shop->id)
        //                 ->whereNull('shopify_product_id')
        //                 ->first();

        //             if (!$bundle) {
        //                 $bundle = new Bundle([
        //                     'shop_id' => $shop->id,
        //                     'shopify_product_id' => null,
        //                 ]);
        //             }

        //             $bundle->title = $validated['title'] ?? '';
        //             $bundle->save();

        //             // delete old discounts
        //             $bundle->discounts()->delete();

        //             // insert new discounts
        //             foreach ($validated['discounts'] as $discount) {
        //                 $bundle->discounts()->create($discount);
        //             }
        //         }

        //         // CASE 2: SPECIFIC PRODUCTS
        //         if ($validated['bundle_type'] === 'specific') {
        //             foreach ($validated['products'] as $productId) {
        //                 // find existing bundle for product
        //                 $bundle = Bundle::where('shop_id', $shop->id)
        //                     ->where('shopify_product_id', $productId)
        //                     ->first();

        //                 if (!$bundle) {
        //                     $bundle = new Bundle([
        //                         'shop_id' => $shop->id,
        //                         'shopify_product_id' => $productId,
        //                     ]);
        //                 }

        //                 $bundle->title = $validated['title'] ?? '';
        //                 $bundle->save();

        //                 // delete old discounts
        //                 $bundle->discounts()->delete();

        //                 // insert new discounts
        //                 foreach ($validated['discounts'] as $discount) {
        //                     $bundle->discounts()->create($discount);
        //                 }
        //             }
        //         }

        //         return redirect()
        //             ->route('bundle.setup', ['shop' => $request->shop])
        //             ->with('success', 'Bundle saved successfully!');
        //     } catch (\Exception $e) {
        //         Log::error('Bundle Store Error: ' . $e->getMessage(), [
        //             'trace' => $e->getTraceAsString(),
        //             'shop' => $request->shop,
        //             'data' => $request->all()
        //         ]);

        //         return redirect()
        //             ->back()
        //             ->withInput()
        //             ->with('error', 'Something went wrong: ' . $e->getMessage());
        //     }
        // }
    public function getDiscountRules(Request $request)
{
        Log::info('Discount API hit', $request->all());

    $shop = Shop::where('shop', $request->query('shop'))->first();
    $bundles = Bundle::with('discounts')->where('shop_id', $shop->id)->get();

    return response()->json($bundles);
}

    public function checkout(Request $request)
    {
        // dd($request->all());
        try {
            $shop = $request->get('shop');
            $variant = $request->get('main_variant');
            $qty = $request->get('quantity', 1);
            $discount = $request->get('discount', 0);

            if (!$shop || !$variant) {
                return response("Missing shop or variant", 400);
            }

            $shopModel = Shop::where('shop', $shop)->firstOrFail();
            $shoptoken = $shopModel->token;
            $storefrontToken = '9a07bcc664e1ec1f8e6d370b5af6c527';

            // STEP 1: Create a cart with the variant and qty
          $cartResponse = Http::withHeaders([
    'X-Shopify-Storefront-Access-Token' => $storefrontToken,
    'Content-Type' => 'application/json',
])
->post("https://{$shop}/api/2025-01/graphql.json", [
    'query' => '
        mutation cartCreate($input: CartInput!) {
          cartCreate(input: $input) {
            cart {
              id
              checkoutUrl
            }
            userErrors {
              field
              message
            }
          }
        }
    ',
    'variables' => [
        'input' => [
            'lines' => [
                [
                    'merchandiseId' => "gid://shopify/ProductVariant/{$variant}",
                    'quantity' => (int) $qty,
                ]
            ]
        ]
    ],
]);


            if ($cartResponse->failed()) {
                Log::error("Bundle Checkout Cart Create Failed", [
                    'shop' => $shop,
                    'variant' => $variant,
                    'qty' => $qty,
                    'response' => $cartResponse->body()
                ]);
                return response()->json([
                    'message' => 'Failed to create cart',
                    'details' => $cartResponse->json(),
                ], 400);
            }

            $cartData = $cartResponse->json();
            
            $checkoutUrl = $cartData['data']['cartCreate']['cart']['checkoutUrl'] ?? null;

            // STEP 2: Redirect to checkout URL
            if ($checkoutUrl) {
                return redirect()->away($checkoutUrl);
            }

            return response("Checkout URL not found", 500);

        } catch (\Exception $e) {
            Log::error("Bundle Checkout Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response("Something went wrong: " . $e->getMessage(), 500);
        }
    }





}
