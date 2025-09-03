<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request; // instead of Facade

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
    if ($shop && !App\Models\Shop::where('shop', $shop)->exists()) {
        return redirect()->route('install.page', ['shop' => $shop]);
    }

    return view('welcome'); // normal landing page for others
});

Route::get('/install-page', function () {
    return view('install');
})->name('install.page');



Route::get('/install', [AuthController::class, 'install'])->name('shopify.install');
Route::get('/auth/callback', [AuthController::class, 'callback'])->name('auth.callback'); // Your /auth/callback route