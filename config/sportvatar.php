<?php

return [

    'isLive' => env('SPORTVATAR_LIVE', false),
    'isInternal' => env('SPORTVATAR_INTERNAL', false),

    'packLive' => env('SPORTVATAR_PACK_LIVE', false),

    'signer' => env('FLOW_SIGNER', 'testnet-account'),


    'marketplace_categories' => [
        'flame' => 'Flame',
        'flobits' => 'Flobits'
    ],
    'svgOpen' => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 3000 3000" width="100%" height="100%"><g id="floor-shadow" class="shadow"><ellipse id="shadow" cx="1533.5" cy="2723.5" rx="675.5" ry="146.5" style="opacity: .2;"/></g>',
    'svgClose' => '</svg>',

    'discordUrl' => env('DISCORD_URL', 'https://discord.gg/Rtcn5CJh'),
    'twitterUrl' => env('TWITTER_URL', 'https://twitter.com/sportvatar'),
    'mediumUrl' => env('MEDIUM_URL', 'https://blog.sportvatar.com/'),
    'githubUrl' => env('GITHUB_URL', 'https://github.com/crash13override/sportvatar'),

    'contractAddress' => env('MIX_CONTRACT_FLOW', ''),
    'packSaleEnabled' => env('PACK_SALE_ENABLED', false),
    'rcsAppId' => env('RCS_APP_ID', ''),
    'rcsProviderId' => env('RCS_PROVIDER_ID', '')

];
