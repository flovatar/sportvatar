<?php

namespace Tests\Unit;

use App\Services\Render\RenderPrune;
use PHPUnit\Framework\TestCase;

/** Selection logic of `render:prune` (which superseded render versions may be deleted from R2). */
class RenderPruneTest extends TestCase
{
    private const DAY = 86400;

    private int $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = 1_800_000_000;
    }

    private function cutoff(int $days = 30): int
    {
        return $this->now - $days * self::DAY;
    }

    /** objects of one version folder uploaded $daysAgo days ago */
    private function version(string $hash, int $daysAgo, bool $browser = false, array $variants = ['full.png', 'og.jpg']): array
    {
        return array_map(fn ($v) => ['key' => $hash.($browser ? '-b' : '').'/'.$v, 'size' => 1000, 'modified' => $this->now - $daysAgo * self::DAY], $variants);
    }

    public function test_old_version_superseded_long_ago_is_deleted_and_current_kept_with_browser_folder(): void
    {
        $objs = array_merge(
            $this->version('aaaaaaaaaaaa', 100), $this->version('aaaaaaaaaaaa', 99, true, ['full.png', 'anim.gif', 'anim.mp4']),
            $this->version('bbbbbbbbbbbb', 60), $this->version('bbbbbbbbbbbb', 59, true),
            [['key' => 'latest.json', 'size' => 200, 'modified' => $this->now - 59 * self::DAY]],
        );
        $sel = RenderPrune::select($objs, 'bbbbbbbbbbbb', 'bbbbbbbbbbbb', $this->cutoff());

        $this->assertNull($sel['keep_all']);
        $this->assertSame(['bbbbbbbbbbbb'], $sel['current']);
        $this->assertSame(['aaaaaaaaaaaa'], array_keys($sel['delete']));
        // both the server and the -b folder of the OLD version go, latest.json and the current version never
        $this->assertCount(5, $sel['delete']['aaaaaaaaaaaa']['keys']);
        $this->assertSame(5000, $sel['delete']['aaaaaaaaaaaa']['bytes']);
        foreach ($sel['delete']['aaaaaaaaaaaa']['keys'] as $k) {
            $this->assertStringStartsWith('aaaaaaaaaaaa', $k);
        }
        // superseded_at = first upload of the current version
        $this->assertSame($this->now - 60 * self::DAY, $sel['delete']['aaaaaaaaaaaa']['superseded_at']);
    }

    public function test_recently_superseded_version_is_kept(): void
    {
        $objs = array_merge($this->version('aaaaaaaaaaaa', 100), $this->version('bbbbbbbbbbbb', 10));
        $sel = RenderPrune::select($objs, 'bbbbbbbbbbbb', null, $this->cutoff());

        $this->assertSame([], $sel['delete']);
        $this->assertSame(['aaaaaaaaaaaa' => 2000], $sel['recent']);
        // --days changes the answer
        $this->assertSame(['aaaaaaaaaaaa'], array_keys(RenderPrune::select($objs, 'bbbbbbbbbbbb', null, $this->cutoff(7))['delete']));
    }

    public function test_chain_of_versions_uses_the_current_versions_first_upload(): void
    {
        // A (200 d) -> B (100 d) -> C (20 d, current): A and B were superseded at most 20 days ago as far as we can prove
        $objs = array_merge($this->version('aaaaaaaaaaaa', 200), $this->version('bbbbbbbbbbbb', 100), $this->version('cccccccccccc', 20));
        $sel = RenderPrune::select($objs, 'cccccccccccc', 'cccccccccccc', $this->cutoff());
        $this->assertSame([], $sel['delete']);
        $this->assertEqualsCanonicalizing(['aaaaaaaaaaaa', 'bbbbbbbbbbbb'], array_keys($sel['recent']));
    }

    public function test_version_uploaded_after_the_current_one_is_kept(): void
    {
        // A -> B -> A again (A's objects are older than B's), or an abandoned job uploading after the switch
        $objs = array_merge($this->version('aaaaaaaaaaaa', 200), $this->version('bbbbbbbbbbbb', 100));
        $sel = RenderPrune::select($objs, 'aaaaaaaaaaaa', 'aaaaaaaaaaaa', $this->cutoff());
        $this->assertSame([], $sel['delete']);
        $this->assertArrayHasKey('bbbbbbbbbbbb', $sel['unsure']);

        // an old version with ONE late object (late browser upload) is unsure too
        $objs = array_merge($this->version('aaaaaaaaaaaa', 200), $this->version('aaaaaaaaaaaa', 40, true), $this->version('bbbbbbbbbbbb', 50));
        $sel = RenderPrune::select($objs, 'bbbbbbbbbbbb', 'bbbbbbbbbbbb', $this->cutoff());
        $this->assertSame([], $sel['delete']);
        $this->assertArrayHasKey('aaaaaaaaaaaa', $sel['unsure']);
    }

    public function test_keeps_everything_when_the_current_version_is_not_certain(): void
    {
        $objs = array_merge($this->version('aaaaaaaaaaaa', 200), $this->version('bbbbbbbbbbbb', 100));

        $this->assertSame('no rendered_images row', RenderPrune::select($objs, null, 'bbbbbbbbbbbb', $this->cutoff())['keep_all']);
        $this->assertSame('latest.json unreadable', RenderPrune::select($objs, 'bbbbbbbbbbbb', false, $this->cutoff())['keep_all']);
        $this->assertSame('latest.json and rendered_images disagree', RenderPrune::select($objs, 'bbbbbbbbbbbb', 'aaaaaaaaaaaa', $this->cutoff())['keep_all']);
        $this->assertSame('current version not on R2', RenderPrune::select($objs, 'cccccccccccc', null, $this->cutoff())['keep_all']);
        foreach ([[null, 'bbbbbbbbbbbb'], ['bbbbbbbbbbbb', false], ['bbbbbbbbbbbb', 'aaaaaaaaaaaa'], ['cccccccccccc', null]] as [$db, $latest]) {
            $this->assertSame([], RenderPrune::select($objs, $db, $latest, $this->cutoff())['delete']);
        }
    }

    public function test_unexpected_keys_are_never_selected(): void
    {
        $objs = array_merge($this->version('aaaaaaaaaaaa', 200), $this->version('bbbbbbbbbbbb', 100), [
            ['key' => 'latest.json', 'size' => 1, 'modified' => $this->now - 300 * self::DAY],
            ['key' => 'notes/readme.txt', 'size' => 1, 'modified' => $this->now - 300 * self::DAY],
            ['key' => 'aaaaaaaaaaaa/sub/dir.png', 'size' => 1, 'modified' => $this->now - 300 * self::DAY],
            ['key' => 'AAAAAAAAAAAA/full.png', 'size' => 1, 'modified' => $this->now - 300 * self::DAY],
            ['key' => 'aaaaaaaaaaaa-x/full.png', 'size' => 1, 'modified' => $this->now - 300 * self::DAY],
        ]);
        $sel = RenderPrune::select($objs, 'bbbbbbbbbbbb', 'bbbbbbbbbbbb', $this->cutoff());
        $this->assertSame(['aaaaaaaaaaaa/full.png', 'aaaaaaaaaaaa/og.jpg'], $sel['delete']['aaaaaaaaaaaa']['keys']);
    }

    public function test_single_version_items_have_nothing_to_delete(): void
    {
        $objs = array_merge($this->version('bbbbbbbbbbbb', 100), $this->version('bbbbbbbbbbbb', 90, true));
        $sel = RenderPrune::select($objs, 'bbbbbbbbbbbb', 'bbbbbbbbbbbb', $this->cutoff());
        $this->assertSame([], $sel['delete']);
        $this->assertSame([], $sel['recent']);
        $this->assertSame([], $sel['unsure']);
    }

    public function test_latest_json_parsing(): void
    {
        $this->assertSame('bbbbbbbbbbbb', RenderPrune::latestHash('{"type":"flovatar","hash":"bbbbbbbbbbbb","source":"browser"}'));
        $this->assertNull(RenderPrune::latestHash(null));
        $this->assertNull(RenderPrune::latestHash('not json'));
        $this->assertNull(RenderPrune::latestHash('{"hash":5}'));
    }
}
