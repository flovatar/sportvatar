<?php

namespace App\Services\Render;

/**
 * Selection logic for `render:prune` (weekly R2 clean-up of superseded render versions).
 *
 * Render keys are immutable and content-hashed: {prefix}{type}/r/{id}/{hash}[-b]/{variant} (+ {id}/latest.json).
 * A new {hash} folder appears only when the item's look changes; `{hash}` (server render) and `{hash}-b` (browser
 * render) are the SAME version. This class decides, per item, which old versions may go.
 *
 * Conservative by design - when in doubt, keep:
 *  - the current version (rendered_images.hash AND the hash in latest.json, both folders) is never deleted;
 *  - no DB row, latest.json unreadable or pointing at another hash than the DB, or the current version not on R2
 *    => keep everything for that item;
 *  - the moment an old version was superseded is not stored, so it is taken as the FIRST upload of the current
 *    version (R2 LastModified). That is never earlier than the real moment (an intermediate version may have
 *    replaced it sooner), so "superseded > N days ago" can only be under-estimated;
 *  - an old version with any object uploaded at/after the current version's first upload (e.g. A -> B -> A, or a
 *    late upload of an abandoned job) => keep (order unknown);
 *  - keys that don't look like `{hash}[-b]/{variant}` are ignored (never deleted).
 */
class RenderPrune
{
    public const HASH_RE = '/^([0-9a-f]{8,40})(-b)?\/([A-Za-z0-9._-]+)$/';

    /**
     * @param  array<int, array{key: string, size: int, modified: int}>  $objects  item objects, `key` relative to
     *                                                                            `{prefix}{type}/r/{id}/`
     * @param  string|null  $dbHash  rendered_images.hash (null = no row)
     * @param  string|null|false  $latestHash  hash in latest.json (null = no latest.json, false = unreadable)
     * @param  int  $cutoff  unix time; versions superseded at or before it may be deleted
     * @return array{keep_all: ?string, current: array<int,string>, delete: array<string, array{keys: array<int,string>, bytes: int, superseded_at: int}>, recent: array<string,int>, unsure: array<string,string>}
     */
    public static function select(array $objects, ?string $dbHash, string|null|false $latestHash, int $cutoff): array
    {
        $out = ['keep_all' => null, 'current' => [], 'delete' => [], 'recent' => [], 'unsure' => []];

        $groups = [];
        foreach ($objects as $o) {
            if (! preg_match(self::HASH_RE, $o['key'], $m)) {
                continue; // latest.json or anything unexpected: never touched
            }
            $g = &$groups[$m[1]];
            $g['keys'][] = $o['key'];
            $g['bytes'] = ($g['bytes'] ?? 0) + (int) $o['size'];
            $g['first'] = min($g['first'] ?? PHP_INT_MAX, (int) $o['modified']);
            $g['last'] = max($g['last'] ?? 0, (int) $o['modified']);
            unset($g);
        }

        if ($dbHash === null || $dbHash === '') {
            $out['keep_all'] = 'no rendered_images row';
        } elseif ($latestHash === false) {
            $out['keep_all'] = 'latest.json unreadable';
        } elseif ($latestHash !== null && $latestHash !== $dbHash) {
            $out['keep_all'] = 'latest.json and rendered_images disagree';
        } elseif (! isset($groups[$dbHash])) {
            $out['keep_all'] = 'current version not on R2';
        }
        if ($out['keep_all'] !== null) {
            return $out;
        }

        $out['current'] = [$dbHash];
        $currentFirst = $groups[$dbHash]['first'];
        foreach ($groups as $hash => $g) {
            if ($hash === $dbHash) {
                continue;
            }
            if ($g['last'] >= $currentFirst) {
                $out['unsure'][$hash] = 'uploaded at/after the current version';
            } elseif ($currentFirst > $cutoff) {
                $out['recent'][$hash] = $g['bytes'];
            } else {
                $out['delete'][$hash] = ['keys' => $g['keys'], 'bytes' => $g['bytes'], 'superseded_at' => $currentFirst];
            }
        }

        return $out;
    }

    /** Hash in a latest.json body (null when absent/invalid JSON shape). */
    public static function latestHash(?string $json): ?string
    {
        if ($json === null || $json === '') {
            return null;
        }
        $d = json_decode($json, true);

        return is_array($d) && isset($d['hash']) && is_string($d['hash']) ? $d['hash'] : null;
    }
}
