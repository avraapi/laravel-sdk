<?php

// File: config/avraapi.php

/**
 * AvraAPI (APIX) Gateway — Laravel SDK Configuration
 *
 * ── Environment Variable Reference ───────────────────────────────────────────
 *
 * Add the following keys to your application's .env file:
 *
 *   # Required — Your project credentials from the APIX dashboard
 *   APIX_PROJECT_KEY=your-project-client-id
 *   APIX_API_SECRET=your-project-api-secret
 *
 *   # Optional — Target environment (default: production → 'prod')
 *   APIX_ENV=production
 *
 *   # Optional — Override the gateway base URL (useful for local development
 *   # when the APIX gateway is running on Laravel Sail at localhost)
 *   APIX_BASE_URL=http://localhost/api/v1
 *
 * ── Quick Usage ───────────────────────────────────────────────────────────────
 *
 *   // In a controller or anywhere in your Laravel app:
 *   use Avraapi\Laravel\Facades\AvraAPI;
 *
 *   $geo     = AvraAPI::location()->lookupIp('112.134.205.126');
 *   $sms     = AvraAPI::sms()->sendSingle('0771234567', 'Hello!');
 *   $pdf     = AvraAPI::utilities()->generatePdf('<h1>Invoice</h1>');
 *   $result  = AvraAPI::call('POST', 'sms/send', ['send_method' => 'single', ...]);
 *
 * ── Notes on the APIX / AvraAPI Dual-Brand Architecture ──────────────────────
 *
 * - The Laravel Facade is named AvraAPI (the public brand).
 * - The environment variables use the APIX_ prefix (the core engine identity).
 * - Configuration keys in this file use snake_case for clarity.
 * - The SDK automatically maps 'production' → 'prod' and 'development' → 'dev'
 *   so you may use either the long or short form in your .env file.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Project Key (X-API-KEY)
    |--------------------------------------------------------------------------
    |
    | Your project client identifier, shown in the APIX dashboard under
    | Project → Credentials. Sent as the X-API-KEY request header.
    |
    | Environment variable: APIX_PROJECT_KEY
    |
    */

    'project_key' => env('APIX_PROJECT_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Secret (X-API-SECRET)
    |--------------------------------------------------------------------------
    |
    | Your project API secret, generated when you create a project credential
    | in the APIX dashboard. Sent as the X-API-SECRET request header.
    |
    | ⚠  Never commit this value to source control. Always use .env.
    |
    | Environment variable: APIX_API_SECRET
    |
    */

    'api_secret' => env('APIX_API_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Environment (X-ENV)
    |--------------------------------------------------------------------------
    |
    | Controls which project environment (Development or Production) the SDK
    | routes requests to. Maps the APIX gateway's X-ENV header.
    |
    | Accepted values (the SDK normalizes all of these):
    |   'production' | 'prod'       → routes to your Production environment
    |   'development' | 'dev'       → routes to your Development environment
    |
    | Default: 'production' — safe for deployed applications.
    |
    | Environment variable: APIX_ENV
    |
    */

    'env' => env('APIX_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of the APIX gateway. Override this for local development
    | when the APIX backend is running on Sail / Docker at localhost.
    |
    | Production default: 'https://avraapi.com/api/v1'
    | Local Sail example: 'http://localhost/api/v1'
    |
    | Leave as null to use the SDK's built-in production default.
    |
    | Environment variable: APIX_BASE_URL
    |
    */

    'base_url' => env('APIX_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Request Timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for a gateway response before the
    | SDK throws an ApixNetworkException. Increase for large PDF generation
    | or bulk SMS sends that may take longer.
    |
    | Default: 30 seconds.
    |
    | Environment variable: APIX_TIMEOUT
    |
    */

    'timeout' => (int) env('APIX_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | TCP Connection Timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for the initial TCP connection to the
    | gateway to be established. Useful for detecting network outages quickly.
    |
    | Default: 10 seconds.
    |
    | Environment variable: APIX_CONNECT_TIMEOUT
    |
    */

    'connect_timeout' => (int) env('APIX_CONNECT_TIMEOUT', 10),

];
