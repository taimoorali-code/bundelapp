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
            // Validation
            $rules = [
                'title' => 'nullable|string|max:255',
                'bundle_type' => 'required|in:all,specific',
                'discounts' => 'required|array|min:1',
                'discounts.*.min_qty' => 'required|integer|min:1',
                'discounts.*.discount_value' => 'required|numeric|min:0',
            ];

            if ($request->bundle_type === 'specific') {
                $rules['products'] = 'required|array|min:1';
            }

            $validated = $request->validate($rules);

            $shop = Shop::where('shop', $request->shop)->firstOrFail();

            // CASE 1: ALL PRODUCTS
            if ($validated['bundle_type'] === 'all') {
                // find existing bundle
                $bundle = Bundle::where('shop_id', $shop->id)
                    ->whereNull('shopify_product_id')
                    ->first();

                if (!$bundle) {
                    $bundle = new Bundle([
                        'shop_id' => $shop->id,
                        'shopify_product_id' => null,
                    ]);
                }

                $bundle->title = $validated['title'] ?? '';
                $bundle->save();

                // delete old discounts
                $bundle->discounts()->delete();

                // insert new discounts
                foreach ($validated['discounts'] as $discount) {
                    $bundle->discounts()->create($discount);
                }
            }

            // CASE 2: SPECIFIC PRODUCTS
            if ($validated['bundle_type'] === 'specific') {
                foreach ($validated['products'] as $productId) {
                    // find existing bundle for product
                    $bundle = Bundle::where('shop_id', $shop->id)
                        ->where('shopify_product_id', $productId)
                        ->first();

                    if (!$bundle) {
                        $bundle = new Bundle([
                            'shop_id' => $shop->id,
                            'shopify_product_id' => $productId,
                        ]);
                    }

                    $bundle->title = $validated['title'] ?? '';
                    $bundle->save();

                    // delete old discounts
                    $bundle->discounts()->delete();

                    // insert new discounts
                    foreach ($validated['discounts'] as $discount) {
                        $bundle->discounts()->create($discount);
                    }
                }
            }

            return redirect()
                ->route('bundle.setup', ['shop' => $request->shop])
                ->with('success', 'Bundle saved successfully!');
        } catch (\Exception $e) {
            Log::error('Bundle Store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'shop' => $request->shop,
                'data' => $request->all()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
    public function checkout(Request $request)
    {
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




            // $cartData = $cartResponse->json();
            // $checkoutUrl = $cartData['cart']['checkout_url'] ?? null;

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
