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
    'rcsProviderId' => env('RCS_PROVIDER_ID', ''),
    // Forward newsletter sign-ups with RCS consent to the RCS identity registration (creates an RCS account
    // for the submitted address without e-mail verification). Off unless explicitly enabled.
    'rcsRegistrationEnabled' => env('RCS_REGISTRATION_ENABLED', false),
    // Comma separated site origins accepted as FCL appIdentifier for wallet login (default: APP_URL origin)
    'accountProofOrigins' => env('SPORTVATAR_ACCOUNT_PROOF_ORIGINS', ''),
    // Minutes a paid pack reservation stays valid (also used by sportvatar:clean-packs)
    'packReservationMinutes' => env('PACK_RESERVATION_MINUTES', 60),

    /*
    | Relaunch feature flags (2026-11).
    | Pack sales and free-pack claims stay off until the owner has signed the contract updates
    | (PR #1 entitlements, PR #3 restrictions in SportvatarTemplate + pack verifier in SportvatarPack) and pack codes v2
    | are live. While off, the pack pages answer with `packSalesNotice` (HTTP 503) and the
    | reserve/authorise endpoints refuse.
    */
    'packSalesEnabled' => env('SPORTVATAR_PACK_SALES_ENABLED', false),
    'packClaimsEnabled' => env('SPORTVATAR_PACK_CLAIMS_ENABLED', false),
    'packSalesNotice' => env('SPORTVATAR_PACK_SALES_NOTICE') ?: ('Pack sales and free-pack claims are paused while Sportvatar moves to its new server and the contracts are upgraded. Packs and Sportvatars you already own are safe on the blockchain.'),

    // Exported images (gif/png/mp4/svg) live on the CDN bucket behind images.sportvatar.com.
    'imagesUrl' => env('SPORTVATAR_IMAGES_URL', 'https://images.sportvatar.com/sportvatar'),
    // redirect (default): serve exported images from the CDN; download: stream them from the s3 disk.
    'imageProxy' => env('SPORTVATAR_IMAGE_PROXY', 'redirect'),
    // Re-rendering needs webgif + headless Chromium + ffmpeg and writes to the s3 disk. Off until that
    // pipeline exists on the new server (ExportImage jobs return immediately).
    'imageExportEnabled' => env('SPORTVATAR_IMAGE_EXPORT_ENABLED', false),
    // Base URL the image renderer loads (/api/image/{id}); the legacy code hard-coded https://sportvatar.com.
    'renderBaseUrl' => env('SPORTVATAR_RENDER_BASE_URL') ?: env('APP_URL', 'https://sportvatar.com'),

    // Wallets: Dapper only today. Kept as a list so Flow Wallet can be added later without
    // touching the views (bootstrap.js reads MIX_WALLET_DISCOVERY).
    'wallets' => array_values(array_filter(array_map('trim', explode(',', (string) env('SPORTVATAR_WALLETS', 'dapper'))))),

    /*
    | Addresses involved in the 2026-11-16 incident. Their listings and sales (and NFTs recorded
    | in flagged_items) are hidden from the marketplace, search, stats and the API, and their pages
    | show a notice. Comma separated.
    */
    /*
    | Filament admin panel (/admin). Only wallet-login users whose address is listed here get in;
    | there is no e-mail/password login or registration. Comma separated Flow addresses.
    */
    'adminAddresses' => array_values(array_filter(array_map(
        fn ($a) => strtolower(trim($a)),
        explode(',', (string) env('ADMIN_ADDRESSES', ''))
    ))),

    'blockedAddresses' => array_values(array_filter(array_map(
        fn ($a) => strtolower(trim($a)),
        explode(',', (string) env('SPORTVATAR_BLOCKED_ADDRESSES', '0x0c7deb7a0c9cb424'))
    ))),
    'blockedNotice' => env('SPORTVATAR_BLOCKED_NOTICE') ?: ('This wallet or item is linked to the 2026-11-16 security incident. It is hidden from the marketplace while the team reviews it.'),

];
