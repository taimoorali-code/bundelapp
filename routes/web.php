<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BundleController;
use Illuminate\Http\Request; // instead of Facade
use App\Http\Controllers\BundleDiscountController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function (Request $request) {
    $shop = $request->query('shop');

    // If shop parameter exists and no token in DB
    if (!$shop && !App\Models\Shop::where('shop', $shop)->exists()) {
        return redirect()->route('install.page', ['shop' => $shop]);
    }
    //  return redirect()->route('install.page', ['shop' => $shop]);

    return redirect()->route('bundles.index');
});


Route::get('/install-page', function () {
    return view('install');
})->name('install.page');

Route::get('/bundle-setup', [BundleController::class, 'showBundleSetup'])->name('bundle.setup');
Route::get('/search-products', [BundleController::class, 'searchProducts']);
Route::post('/bundles-store', [BundleController::class, 'store'])->name('bundle.store');


Route::get('/install', [AuthController::class, 'install'])->name('shopify.install');
Route::get('/auth/callback', [AuthController::class, 'callback'])->name('auth.callback'); // Your /auth/callback route
// routes/web.php
Route::get('/apps/bundle-checkout', [BundleController::class, 'checkout'])->name('bundle.checkout');

// routes/web.php
    // Route::get('/bundles', [BundleController::class, 'index'])->name('bundles.index');

Route::resource('bundles', BundleController::class);

    Route::get('/bundle-discounts', [BundleDiscountController::class, 'index'])->name('bundle_discounts.index');
    Route::get('/bundle-discounts/create', [BundleDiscountController::class, 'create'])->name('bundle_discounts.create');
    Route::post('/bundle-discounts', [BundleDiscountController::class, 'store'])->name('bundle_discounts.store');
