<?php

namespace App\Support;

/**
 * Helpers to rebuild the message that FCL wallets sign for an
 * "account-proof" service, so it can be verified on chain with
 * FCLCrypto.verifyAccountProofSignatures.
 *
 * The encoding mirrors @onflow/fcl `WalletUtils.encodeAccountProof(data, false)`:
 *   hex( RLP([ appIdentifier, address (8 bytes), nonce (bytes) ]) )
 * The "FCL-ACCOUNT-PROOF-V0.0" domain tag is added by FCLCrypto itself.
 */
class FlowAccountProof
{
    public static function isValidAddress($address)
    {
        return is_string($address) && preg_match('/^0x[0-9a-f]{16}$/', $address) === 1;
    }

    public static function isValidNonce($nonce)
    {
        return is_string($nonce) && preg_match('/^[0-9a-f]{64,128}$/', $nonce) === 1 && strlen($nonce) % 2 === 0;
    }

    public static function encodeMessage($appIdentifier, $address, $nonce)
    {
        $addressBytes = hex2bin(str_pad(substr($address, 2), 16, '0', STR_PAD_LEFT));
        $nonceBytes = hex2bin($nonce);

        return bin2hex(self::rlpList([
            self::rlpString((string) $appIdentifier),
            self::rlpString($addressBytes),
            self::rlpString($nonceBytes),
        ]));
    }

    protected static function rlpString($bytes)
    {
        $length = strlen($bytes);
        if ($length === 1 && ord($bytes) < 0x80) {
            return $bytes;
        }
        return self::rlpLength($length, 0x80).$bytes;
    }

    protected static function rlpList(array $encodedItems)
    {
        $payload = implode('', $encodedItems);
        return self::rlpLength(strlen($payload), 0xc0).$payload;
    }

    protected static function rlpLength($length, $offset)
    {
        if ($length <= 55) {
            return chr($offset + $length);
        }
        $lengthBytes = ltrim(pack('J', $length), "\0");
        return chr($offset + 55 + strlen($lengthBytes)).$lengthBytes;
    }
}
