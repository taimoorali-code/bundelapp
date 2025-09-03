<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
            isEmbeddedApp: true
        );

    }

    // Step 1: Redirect merchant to Shopify install URL
    public function install(Request $request)
    {
        $shop = $request->query('shop'); // e.g. mystore.myshopify.com

        $installUrl = OAuth::begin(
            $shop,
            redirectPath: '/auth/callback',
            isOnline: false
        );
        dd($installUrl);

        return redirect($installUrl);
    }

    // Step 2: Shopify redirects back here after auth
    public function callback(Request $request)
    {
        try {
            $session = OAuth::callback($request->query(), $request->cookie());

            // Access token
            $accessToken = $session->getAccessToken();
            $shop = $session->getShop();

            // TODO: Save $shop + $accessToken in DB for later use
            // Example:
            // Shop::updateOrCreate(['shop' => $shop], ['token' => $accessToken]);

            return "App installed successfully on {$shop}!";
        } catch (\Exception $e) {
            return "OAuth error: " . $e->getMessage();
        }
    }
}
