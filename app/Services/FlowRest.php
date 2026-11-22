<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Minimal read-only Flow REST client (scripts only). */
class FlowRest
{
    public static function baseUrl(): string
    {
        return env('FLOW_NETWORK', 'mainnet') === 'mainnet' ? 'https://rest-mainnet.onflow.org' : 'https://rest-testnet.onflow.org';
    }

    /** Runs a Cadence script and returns the decoded value. */
    public static function script(string $cadence, array $args = [])
    {
        $resp = Http::timeout(60)->post(self::baseUrl() . '/v1/scripts?block_height=sealed', [
            'script' => base64_encode($cadence),
            'arguments' => array_map(fn ($a) => base64_encode(json_encode($a)), $args),
        ]);
        if (!$resp->successful()) {
            throw new RuntimeException('Flow script failed: ' . substr($resp->body(), 0, 500));
        }
        return self::decode(json_decode(base64_decode(trim($resp->body(), "\" \n")), true));
    }

    public static function decode($v)
    {
        if (!is_array($v) || !array_key_exists('type', $v)) {
            return $v;
        }
        $t = $v['type'];
        $val = $v['value'] ?? null;
        switch ($t) {
            case 'Optional':
                return $val === null ? null : self::decode($val);
            case 'Array':
                return array_map([self::class, 'decode'], $val);
            case 'Dictionary':
                $out = [];
                foreach ($val as $e) {
                    $out[(string) self::decode($e['key'])] = self::decode($e['value']);
                }
                return $out;
            case 'Struct':
            case 'Resource':
            case 'Event':
                $out = [];
                foreach ($val['fields'] as $f) {
                    $out[$f['name']] = self::decode($f['value']);
                }
                return $out;
            default:
                return $val;
        }
    }
}
