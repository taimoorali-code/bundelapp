<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Shopify\Auth\OAuth;
use Shopify\Clients\OAuth as OAuthClient;
use Shopify\Context;
use Shopify\Auth\AccessToken;
use Shopify\Auth\FileSessionStorage;


class AuthController extends Controller
{
    public function __construct()
    {
        // Shopify SDK ko init karo
        Context::initialize(
            apiKey: env('SHOPIFY_API_KEY'),
            apiSecretKey: env('SHOPIFY_API_SECRET'),
            scopes: explode(',', env('SHOPIFY_API_SCOPES')),
            hostName: parse_url(env('SHOPIFY_APP_URL'), PHP_URL_HOST),
            sessionStorage: new FileSessionStorage(storage_path('shopify_sessions')),
            apiVersion: '2025-01',
            isEmbeddedApp: false
        );

    }

    // Step 1: Redirect merchant to Shopify install URL
   public function install(Request $request)
{
    $shop = $request->query('shop'); // e.g., mystore.myshopify.com

    // Generate a random state token for CSRF protection
    $state = bin2hex(random_bytes(16));
    // Save it temporarily (e.g., encrypted in Laravel cache or signed cookie)
    cookie()->queue('oauth_state', $state, 5); // 5 minutes

    $scopes = 'read_products,write_draft_orders,read_draft_orders';
    $redirectUri = route('auth.callback'); // Your /auth/callback route

    $installUrl = "https://{$shop}/admin/oauth/authorize?client_id=" . env('SHOPIFY_API_KEY') .
                  "&scope={$scopes}" .
                  "&redirect_uri={$redirectUri}" .
                  "&state={$state}&grant_options[]=per-user";

    return redirect($installUrl);
}

    // Step 2: Shopify redirects back here after auth
 public function callback(Request $request)
{
    $state = $request->query('state');
    $code = $request->query('code');
    $shop = $request->query('shop');

    // Verify state to prevent CSRF
    if ($state !== $request->cookie('oauth_state')) {
        abort(403, 'Invalid state');
    }

    // Exchange code for access token
    $response = Http::asForm()->post("https://{$shop}/admin/oauth/access_token", [
        'client_id' => env('SHOPIFY_API_KEY'),
        'client_secret' => env('SHOPIFY_API_SECRET'),
        'code' => $code,
    ]);

    $accessToken = $response->json()['access_token'];

    // Save $shop + $accessToken in DB
    Shop::updateOrCreate(
        ['shop' => $shop],
        ['token' => $accessToken]
    );

    return "App installed successfully on {$shop}!";
}

}
