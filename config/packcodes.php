<?php

// Pack codes v2 (2026-11 reissue). See docs/PACK-CODES-V2.md.
return [
    // Dedicated pack-signer private key (64 hex chars, `flow keys generate` format), chmod 600, outside the web root.
    // Its PUBLIC key is registered on-chain in the SportvatarPack pack verifier (owner-kit step S4). Never an account key.
    'signer_key_path' => env('PACK_SIGNER_KEY_PATH'),

    // Must equal SportvatarPack.getVerifierEpoch(). Bump both together to invalidate all outstanding authorisations.
    'epoch' => (int) env('PACK_EPOCH', 1),

    // Lifetime of an authorisation (seconds). The user must submit the claim/purchase transaction within this time.
    'authorization_ttl' => (int) env('PACK_AUTH_TTL', 900),

    // HMAC key for voucher codes: the database stores only HMAC-SHA256(code, pepper). >= 32 random characters.
    'code_pepper' => env('PACK_CODE_PEPPER'),

    // Pack names that count as free packs for vouchers.
    'free_pack_names' => ['Genesis Free Pack'],
];
