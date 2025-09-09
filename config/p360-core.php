<?php

use P360\Core\Constants\ErrorCodes;

return [

    /*
    |--------------------------------------------------------------------------
    | P360/CORE Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('P360_CORE_ENABLED', true),

     /*
    |--------------------------------------------------------------------------
    | Auto-apply SSO middleware to route groups (from your earlier setup)
    |--------------------------------------------------------------------------
    */
    'auto_apply_middleware' => env('P360_CORE_AUTO_MW', true),
    'middleware_groups' => ['web', 'api'],
    'apply_globally' => env('P360_CORE_GLOBAL_MW', false),

    /*
    |--------------------------------------------------------------------------
    | VERBOSE LOGGING TOGGLES
    |--------------------------------------------------------------------------
    | Turn these on/off from .env without touching app code.
    */
    'verbose_log'        => env('APP_VERBOSE_LOG', false),   // master switch
    'log_sql'            => env('LOG_SQL_QUERIES', false),   // log all queries
    'log_requests'       => env('LOG_REQUESTS', false),      // log route/controller/timing
    'log_slow_query_ms'  => (int) env('LOG_SLOW_QUERY_MS', 0), // warn slow queries (>0 to enable)
    'log_summary'        => env('LOG_REQUEST_SUMMARY', true),      // emit one summary line at the end with all metrics , time memory, delta, etc.

    'sms' => [
        'auth_url'  => env('SMS_AUTH_URL',  'https://http.myvfirst.com/smpp/api/sendsms/token?action=generate'),
        'api_url'   => env('SMS_API_URL',   'https://http.myvfirst.com/smpp/api/sendsms'),
        'username'  => env('SMS_USERNAME'),
        'password'  => env('SMS_PASSWORD'),
        'sender_id' => env('SMS_SENDER_ID'),
        'dlr_mask'  => env('SMS_DLR_MASK', 19),
    ],

    'upload' => [
        'disk' => env('P360_UPLOAD_DISK', env('FILESYSTEM_DRIVER', 'public')),
        'base' => env('P360_UPLOAD_PATH', 'P360-UAT'),
    ],

    'email' => [
        'default_layout'  => env('EMAIL_DEFAULT_LAYOUT', 'emails.generic'),
        'queue'           => env('EMAIL_QUEUE_NAME',     'emails'),
        'from_address'    => env('MAIL_FROM_ADDRESS'),
        'from_name'       => env('MAIL_FROM_NAME'),
    ],

    'error_messages' => [
        ErrorCodes::USER_INACTIVE              => 'User is inactive',
        ErrorCodes::GROUP_INACTIVE             => 'Group is inactive',
        ErrorCodes::GROUP_LICENSE_EXPIRED      => 'Group license has expired',
        ErrorCodes::COMPANY_INACTIVE           => 'Company is inactive',
        ErrorCodes::COMPANY_LICENSE_EXPIRED    => 'Company license has expired',
        ErrorCodes::ORGANIZATION_INACTIVE      => 'Organization is inactive',
        ErrorCodes::ORGANIZATION_LICENSE_EXPIRED => 'Organization license has expired',

        ErrorCodes::AUTH_LOGIN_REQUIRED        => 'Login is required',
        ErrorCodes::AUTH_INVALID_CREDENTIALS   => 'Invalid credentials provided',
        ErrorCodes::AUTH_TOKEN_EXPIRED         => 'Authentication token has expired',
        ErrorCodes::AUTH_TOKEN_INVALID         => 'Invalid authentication token',
        ErrorCodes::AUTH_USER_NOT_FOUND        => 'User not found',
        ErrorCodes::AUTH_USER_NOT_ACTIVE       => 'User is not active',
        ErrorCodes::AUTH_USER_NOT_AUTHORIZED   => 'User is not authorized',
        ErrorCodes::AUTH_SESSION_EXPIRED       => 'User session has expired',
        ErrorCodes::AUTH_INVALID_REQUEST       => 'Invalid request',
    ],
];