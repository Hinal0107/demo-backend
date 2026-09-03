<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Access Worldpay Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for Access Worldpay payment gateway integration.
    |
    */

    'entity_id' => env('WORLDPAY_ENTITY_ID', 'mock-entity-id'),

    'username' => env('WORLDPAY_USERNAME', 'mock-username'),

    'password' => env('WORLDPAY_PASSWORD', 'mock-password'),

    'api_url' => env('WORLDPAY_API_URL', 'https://try.access.worldpay.com/checkout/sessions'),

    'success_url' => env('WORLDPAY_SUCCESS_URL', env('APP_URL', 'http://localhost:8000') . '/payment-success'),

    'failure_url' => env('WORLDPAY_FAILURE_URL', env('APP_URL', 'http://localhost:8000') . '/payment-failed'),

    'webhook_secret' => env('WORLDPAY_WEBHOOK_SECRET', ''),

];
