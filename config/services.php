<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'flow' => [
        'address' => env('FLOW_MARKET_ADDRESS'),
        'storefrontaddress' => env('MIX_CONTRACT_STOREFRONT2', '0x2d55b98eb200daef'),
        'path' => env('FLOW_PATH', '~/.local/bin'),
        'network' => env('FLOW_NETWORK', 'emulator'),
        'minBlockHeight' => env('FLOW_MIN_BLOCK_HEIGHT', 0),

        // Built-in event indexer that replaces Graffle and the Flow CLI pull sync
        // (flow:catchup / flow:stream / flow:process).
        'rest' => env('FLOW_REST_URL', 'https://rest-mainnet.onflow.org'),
        'ws' => env('FLOW_WS_URL', 'wss://rest-mainnet.onflow.org/v1/ws'),
        'indexer' => [
            'enabled' => env('FLOW_INDEXER_ENABLED', true),
            'start_height' => env('FLOW_INDEXER_START_HEIGHT'),
            'rps' => env('FLOW_INDEXER_RPS', 20),
            'max_blocks_per_run' => env('FLOW_INDEXER_MAX_BLOCKS', 25000),
            'process_batch' => env('FLOW_INDEXER_PROCESS_BATCH', 200),
            'max_attempts' => env('FLOW_INDEXER_MAX_ATTEMPTS', 3),
        ],
    ],
    'sender_mail' => [
        'token' => env('SENDER_MAIL_TOKEN'),
        'url' => env('SENDER_MAIL_URL', 'https://api.sender.net/v2/subscribers')
    ],
    'graffle' => [
        // Graffle has delivered nothing since the Crescendo spork (2024-09) and is replaced by the
        // built-in indexer. The /graffle route is only registered when this is true.
        'enabled' => env('GRAFFLE_WEBHOOK_ENABLED', false),
        // Shared secret required on POST /graffle (header X-Graffle-Secret, Bearer token or ?secret=)
        'secret' => env('GRAFFLE_WEBHOOK_SECRET'),
    ]

];
