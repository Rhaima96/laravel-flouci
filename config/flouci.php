<?php

return [
    'base_url' => env('FLOUCI_BASE_URL', 'https://developers.flouci.com/api'),

    'public_key' => env('FLOUCI_PUBLIC_KEY'),

    'private_key' => env('FLOUCI_PRIVATE_KEY'),

    'success_link' => env('FLOUCI_SUCCESS_LINK'),

    'fail_link' => env('FLOUCI_FAIL_LINK'),

    'card_payment' => env('FLOUCI_CARD_PAYMENT', true),

    'image_url' => env('FLOUCI_IMAGE_URL'),

    'timeout' => env('FLOUCI_TIMEOUT', 15),

    'webhook' => env('FLOUCI_WEBHOOK_URL'),

    'session_timeout' => env('FLOUCI_SESSION_TIMEOUT'),
];
