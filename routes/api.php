<?php

use App\Http\Controllers\BundleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Bundle;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/create-draft-order', [BundleController::class, 'createDraftOrder']);


Route::get('/get-bundle', function (Request $request) {
    $shop = $request->query('shop');
    $productId = $request->query('product_id');

    $bundles = Bundle::where('shopify_product_id', $productId)
        ->with('discounts')
        ->get();

    return response()->json($bundles);
});

Route::get('/bundles/discounts', [BundleController::class, 'getDiscountRules']);
