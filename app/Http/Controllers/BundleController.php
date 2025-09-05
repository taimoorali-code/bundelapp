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
            'shop'  => $request->shop,
            'data'  => $request->all()
        ]);

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Something went wrong: ' . $e->getMessage());
    }
}

public function createDraftOrder(Request $request)
{
    try {
        $validated = $request->validate([
            'shop'             => 'required|string',
            'items'            => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer',
            'items.*.quantity'   => 'required|integer|min:1',
            'discount_percent' => 'required|numeric|min:0|max:100',
            'bundle_id'        => 'nullable|string',
            'note'             => 'nullable|string|max:500',
            'customer'         => 'nullable|array', // { email, id, etc } optional
        ]);

        // get shop access token
        $shopRec = Shop::where('shop', $validated['shop'])->first();
        if (!$shopRec || !$shopRec->token) {
            return response()->json(['message' => 'Shop or token not found'], 422);
        }
        $accessToken = $shopRec->token;
        $shopDomain  = $shopRec->shop; // e.g. my-store.myshopify.com

        // line items for draft order
        $lineItems = collect($validated['items'])->map(function ($i) {
            return [
                'variant_id' => (int) $i['variant_id'],
                'quantity'   => (int) $i['quantity'],
            ];
        })->values()->all();

        // applied discount across the draft order
        $appliedDiscount = [
            'description' => 'Bundle Discount',
            'value_type'  => 'percentage',
            'value'       => (float) $validated['discount_percent'],
            // 'amount' => null // omit: Shopify calculates
        ];

        // optional note attributes (tracking)
        $noteAttributes = [];
        if (!empty($validated['bundle_id'])) {
            $noteAttributes[] = ['name' => 'bundle_id', 'value' => $validated['bundle_id']];
        }
        if (!empty($validated['discount_percent'])) {
            $noteAttributes[] = ['name' => 'bundle_discount_percent', 'value' => (string)$validated['discount_percent']];
        }

        // base payload
        $payload = [
            'draft_order' => [
                'line_items'                  => $lineItems,
                'applied_discount'            => $appliedDiscount,      // overall bundle discount
                'use_customer_default_address'=> true,
                'tags'                        => 'bundle,generated-by-app',
                'note'                        => $validated['note'] ?? 'Bundle checkout',
                'note_attributes'             => $noteAttributes,
            ],
        ];

        // optionally attach customer (so taxes/shipping auto-prefill)
        if (!empty($validated['customer'])) {
            // If you have customer.id use that; otherwise you can pass email to attach customer draft (Shopify may create a customer on invoice)
            if (!empty($validated['customer']['id'])) {
                $payload['draft_order']['customer'] = ['id' => (int) $validated['customer']['id']];
            } elseif (!empty($validated['customer']['email'])) {
                $payload['draft_order']['customer'] = ['email' => $validated['customer']['email']];
            }
        }

        $resp = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Content-Type'           => 'application/json',
        ])->post("https://{$shopDomain}/admin/api/2025-01/draft_orders.json", $payload);

        if (!$resp->successful()) {
            Log::error('Draft order create failed', ['shop' => $shopDomain, 'body' => $resp->body(), 'payload' => $payload]);
            return response()->json([
                'message' => 'Shopify draft order create failed',
                'error'   => $resp->json()
            ], 422);
        }

        $draft = $resp->json('draft_order') ?? [];

        // OPTIONAL: mark as invoice (send invoice email) — only if you want
        // You can also skip this; redirect to invoice_url works fine.
        // Http::withHeaders([
        //     'X-Shopify-Access-Token' => $accessToken,
        // ])->put("https://{$shopDomain}/admin/api/2025-01/draft_orders/{$draft['id']}.json", [
        //     'draft_order' => ['invoice_sent' => true]
        // ]);

        return response()->json([
            'draft_order_id' => $draft['id'] ?? null,
            'name'           => $draft['name'] ?? null,
            'invoice_url'    => $draft['invoice_url'] ?? null,
        ]);
    } catch (\Illuminate\Validation\ValidationException $ve) {
        return response()->json(['message' => 'Validation failed', 'errors' => $ve->errors()], 422);
    } catch (\Throwable $e) {
        Log::error('createDraftOrder error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Server error'], 500);
    }
}



}
