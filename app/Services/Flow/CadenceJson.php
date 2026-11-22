<?php

namespace App\Services\Flow;

/**
 * Decodes JSON-Cadence (https://cadence-lang.org/docs/json-cadence-spec) into the flat,
 * Graffle-compatible payload stored in events.flow_metadata:
 *  - integers -> int, UFix64/Fix64 -> float (int when whole), Address -> "0x..." string
 *  - Optional -> value or null, composites -> {field: value}, arrays/dictionaries -> arrays
 *  - Type values -> the static type object, so $payload['nftType']['typeID'] works like before.
 * Port of flovatar-kb/indexer/cadence_json.py (631/632 payloads byte-identical to Graffle).
 */
class CadenceJson
{
    const INT_TYPES = ['Int', 'Int8', 'Int16', 'Int32', 'Int64', 'Int128', 'Int256',
        'UInt', 'UInt8', 'UInt16', 'UInt32', 'UInt64', 'UInt128', 'UInt256',
        'Word8', 'Word16', 'Word32', 'Word64', 'Word128', 'Word256'];

    const FIX_TYPES = ['UFix64', 'Fix64', 'UFix128', 'Fix128'];

    const COMPOSITES = ['Struct', 'Resource', 'Event', 'Contract', 'Enum', 'InclusiveRange'];

    /** REST and WebSocket APIs deliver payloads as base64(JSON-Cadence). */
    public static function decodePayload($payload)
    {
        if (is_string($payload)) {
            $payload = json_decode(base64_decode($payload), true, 512, JSON_BIGINT_AS_STRING);
        }

        return self::decode($payload);
    }

    public static function decode($v)
    {
        if (! is_array($v)) {
            return null;
        }
        $t = $v['type'] ?? null;
        $val = $v['value'] ?? null;

        if (in_array($t, self::INT_TYPES, true)) {
            // Keep values beyond PHP_INT_MAX as strings instead of losing precision.
            return (is_numeric($val) && bccomp((string) $val, (string) PHP_INT_MAX) <= 0 && bccomp((string) $val, (string) PHP_INT_MIN) >= 0)
                ? (int) $val : $val;
        }
        if (in_array($t, self::FIX_TYPES, true)) {
            $s = (string) $val;
            if (preg_match('/^-?\d+(\.0*)?$/', $s)) {
                return (int) $s;
            }

            return (float) $s;
        }
        if (in_array($t, ['String', 'Character', 'Address', 'Bool'], true)) {
            return $val;
        }
        if ($t === 'Void') {
            return null;
        }
        if ($t === 'Optional') {
            return $val === null ? null : self::decode($val);
        }
        if ($t === 'Array') {
            return array_map([self::class, 'decode'], $val ?? []);
        }
        if ($t === 'Dictionary') {
            $out = [];
            foreach ($val ?? [] as $kv) {
                $k = self::decode($kv['key']);
                $out[is_bool($k) ? ($k ? 'true' : 'false') : (string) $k] = self::decode($kv['value']);
            }

            return $out;
        }
        if (in_array($t, self::COMPOSITES, true)) {
            $out = [];
            foreach (($val['fields'] ?? []) as $f) {
                $out[$f['name']] = self::decode($f['value']);
            }

            return $out;
        }
        if ($t === 'Type') {
            $st = is_array($val) ? ($val['staticType'] ?? null) : null;

            return is_array($st) ? $st : ['typeID' => $st];
        }
        if ($t === 'Path') {
            return '/'.($val['domain'] ?? '').'/'.($val['identifier'] ?? '');
        }

        // Capability and unknown kinds: keep the raw value so nothing is lost.
        return $val;
    }
}
