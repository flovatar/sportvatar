<?php

namespace App\Services;

use RuntimeException;

/**
 * Pack codes v2: signs on-chain authorisations checked by the pack verifier inside SportvatarPack.
 *
 * Message (UTF-8, no domain tag; must match SportvatarPack.buildVerifierMessage exactly):
 *   SPORTVATAR-PACK-V2|<kind>|<packId>|<recipient>|<expiry>|<epoch>
 *     kind      "claim" | "purchase"
 *     recipient "0x" + 16 lowercase hex digits (Cadence Address.toString())
 *     expiry    unix seconds
 *     epoch     SportvatarPack.getVerifierEpoch() (config packcodes.epoch; bump both together)
 *
 * Signature: ECDSA P-256 over SHA3-256(message), returned as hex r||s (64 bytes).
 * The private key is a dedicated pack-signer key (NOT an account key), read from PACK_SIGNER_KEY_PATH (chmod 600).
 */
class PackAuthorizer
{
    public const PREFIX = 'SPORTVATAR-PACK-V2';

    public static function normalizeAddress(string $address): string
    {
        $hex = strtolower(preg_replace('/^0x/i', '', trim($address)));
        if (!preg_match('/^[0-9a-f]{1,16}$/', $hex)) {
            throw new RuntimeException('Invalid Flow address');
        }
        return '0x' . str_pad($hex, 16, '0', STR_PAD_LEFT);
    }

    public static function message(string $kind, int $packId, string $recipient, int $expiry, int $epoch): string
    {
        if (!in_array($kind, ['claim', 'purchase'], true)) {
            throw new RuntimeException('Invalid kind');
        }
        return implode('|', [self::PREFIX, $kind, (string) $packId, self::normalizeAddress($recipient), (string) $expiry, (string) $epoch]);
    }

    /**
     * @return array{packId:int, expiry:int, epoch:int, signature:string}
     */
    public function authorize(string $kind, int $packId, string $recipient, ?int $ttl = null): array
    {
        $ttl = $ttl ?? (int) config('packcodes.authorization_ttl', 900);
        $expiry = time() + max(60, $ttl);
        $epoch = (int) config('packcodes.epoch', 1);
        $message = self::message($kind, $packId, $recipient, $expiry, $epoch);
        return [
            'packId' => $packId,
            'expiry' => $expiry,
            'epoch' => $epoch,
            'signature' => $this->sign($message),
        ];
    }

    public function sign(string $message): string
    {
        $pem = $this->privateKeyPem();
        $der = '';
        if (!openssl_sign($message, $der, $pem, 'sha3-256')) {
            throw new RuntimeException('openssl_sign failed (OpenSSL >= 1.1.1 with sha3-256 is required)');
        }
        return self::derToRawHex($der);
    }

    public function publicKeyHex(): string
    {
        $details = openssl_pkey_get_details(openssl_pkey_get_private($this->privateKeyPem()));
        $x = str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
        return bin2hex($x . $y);
    }

    private function privateKeyPem(): string
    {
        $path = config('packcodes.signer_key_path');
        if (!$path || !is_readable($path)) {
            throw new RuntimeException('Pack signer key not configured (PACK_SIGNER_KEY_PATH)');
        }
        if ((fileperms($path) & 0077) !== 0) {
            throw new RuntimeException('Pack signer key file must be chmod 600');
        }
        $hex = strtolower(trim(preg_replace('/^0x/i', '', trim(file_get_contents($path)))));
        if (!preg_match('/^[0-9a-f]{64}$/', $hex)) {
            throw new RuntimeException('Pack signer key must be 64 hex characters (flow keys generate format)');
        }
        // SEC1 ECPrivateKey DER for prime256v1, OpenSSL derives the public key.
        $der = hex2bin('30310201010420' . $hex . 'a00a06082a8648ce3d030107');
        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
    }

    /** DER ECDSA signature -> 64-byte r||s hex (what Cadence expects). */
    public static function derToRawHex(string $der): string
    {
        $pos = 0;
        $read = function () use ($der, &$pos) {
            return ord($der[$pos++]);
        };
        if ($read() !== 0x30) {
            throw new RuntimeException('bad DER');
        }
        $len = $read();
        if ($len & 0x80) {
            $pos += $len & 0x7f;
        }
        $out = '';
        for ($i = 0; $i < 2; $i++) {
            if ($read() !== 0x02) {
                throw new RuntimeException('bad DER int');
            }
            $l = $read();
            $int = substr($der, $pos, $l);
            $pos += $l;
            $int = ltrim($int, "\0");
            $out .= str_pad($int, 32, "\0", STR_PAD_LEFT);
        }
        return bin2hex($out);
    }

    /** Voucher codes are stored only as HMAC-SHA256(code, PACK_CODE_PEPPER). */
    public static function hashCode(string $code): string
    {
        $pepper = (string) config('packcodes.code_pepper');
        if (strlen($pepper) < 32) {
            throw new RuntimeException('PACK_CODE_PEPPER must be set (>= 32 chars)');
        }
        return hash_hmac('sha256', self::normalizeCode($code), $pepper);
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $code));
    }

    /** 20 characters of Crockford base32 (100 bits), shown as XXXXX-XXXXX-XXXXX-XXXXX. */
    public static function newCode(): string
    {
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $bytes = random_bytes(20);
        $s = '';
        for ($i = 0; $i < 20; $i++) {
            $s .= $alphabet[ord($bytes[$i]) & 31];
        }
        return implode('-', str_split($s, 5));
    }
}
