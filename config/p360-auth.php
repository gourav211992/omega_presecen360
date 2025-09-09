<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SSO Authentication Configuration
    |--------------------------------------------------------------------------
    */

    'login_url' => env('AUTH_URL', 'https://auth.p360.local/login'),

    'oauth_keys' => [
        'private_key' => storage_path('oauth-private.key'),
        'public_key' => storage_path('oauth-public.key'),
    ],

    'token_keys' => [
        'sso_token' => 'sso_token',
        'auth_type_claim' => 'sso_auth',
        'instance_claim' => 'sso_instance',
    ],

    'master_db' => "master_db",

];