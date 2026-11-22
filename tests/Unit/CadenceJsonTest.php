<?php

namespace Tests\Unit;

use App\Services\Flow\CadenceJson;
use App\Services\Flow\FlowEventTypes;
use PHPUnit\Framework\TestCase;

class CadenceJsonTest extends TestCase
{
    public function test_decodes_a_storefront_event_like_graffle(): void
    {
        $event = ['type' => 'Event', 'value' => ['id' => 'A.4eb8a10cb9f87357.NFTStorefrontV2.ListingAvailable', 'fields' => [
            ['name' => 'storefrontAddress', 'value' => ['type' => 'Address', 'value' => '0x3d0c834f767ca25b']],
            ['name' => 'listingResourceID', 'value' => ['type' => 'UInt64', 'value' => '123456']],
            ['name' => 'nftType', 'value' => ['type' => 'Type', 'value' => ['staticType' => ['kind' => 'Resource', 'typeID' => 'A.921ea449dffec68a.FlovatarComponent.NFT', 'fields' => [], 'initializers' => [], 'type' => '']]]],
            ['name' => 'nftID', 'value' => ['type' => 'UInt64', 'value' => '130680']],
            ['name' => 'salePrice', 'value' => ['type' => 'UFix64', 'value' => '6.18496376']],
            ['name' => 'wholePrice', 'value' => ['type' => 'UFix64', 'value' => '20.00000000']],
            ['name' => 'expiry', 'value' => ['type' => 'Optional', 'value' => null]],
            ['name' => 'commission', 'value' => ['type' => 'Optional', 'value' => ['type' => 'Array', 'value' => [['type' => 'Address', 'value' => '0x1']]]]],
            ['name' => 'purchased', 'value' => ['type' => 'Bool', 'value' => false]],
        ]]];

        $decoded = CadenceJson::decodePayload(base64_encode(json_encode($event)));

        $this->assertSame('0x3d0c834f767ca25b', $decoded['storefrontAddress']);
        $this->assertSame(123456, $decoded['listingResourceID']);
        $this->assertSame('A.921ea449dffec68a.FlovatarComponent.NFT', $decoded['nftType']['typeID']);
        $this->assertSame(6.18496376, $decoded['salePrice']);
        $this->assertSame(20, $decoded['wholePrice']);
        $this->assertNull($decoded['expiry']);
        $this->assertSame(['0x1'], $decoded['commission']);
        $this->assertFalse($decoded['purchased']);
    }

    public function test_dictionary_and_struct_values(): void
    {
        $v = ['type' => 'Dictionary', 'value' => [
            ['key' => ['type' => 'String', 'value' => 'body'], 'value' => ['type' => 'UInt64', 'value' => '7']],
            ['key' => ['type' => 'Bool', 'value' => true], 'value' => ['type' => 'Struct', 'value' => ['id' => 'S', 'fields' => [['name' => 'a', 'value' => ['type' => 'Int', 'value' => '-3']]]]]],
        ]];

        $this->assertSame(['body' => 7, 'true' => ['a' => -3]], CadenceJson::decode($v));
    }

    public function test_storefront_filter_keeps_only_our_nfts(): void
    {
        $types = new class extends FlowEventTypes
        {
            public static function contractAddress(): string { return 'ca5c31c0c03e11be'; }
            public static function storefrontAddress(): string { return '4eb8a10cb9f87357'; }
        };
        $listing = 'A.4eb8a10cb9f87357.NFTStorefrontV2.ListingAvailable';

        $this->assertTrue($types::keep($listing, ['nftType' => ['typeID' => 'A.ca5c31c0c03e11be.Sportvatar.NFT']]));
        $this->assertTrue($types::keep($listing, ['nftType' => ['typeID' => 'A.ca5c31c0c03e11be.Sportbit.NFT']]));
        $this->assertFalse($types::keep($listing, ['nftType' => ['typeID' => 'A.921ea449dffec68a.Flovatar.NFT']]));
        $this->assertFalse($types::keep($listing, ['nftType' => ['typeID' => 'A.0b2a3299cc857e29.TopShot.NFT']]));
        $this->assertTrue($types::keep('A.ca5c31c0c03e11be.Sportbit.Deposit', ['id' => 1, 'to' => '0x1']));
        $this->assertFalse($types::keep('A.ca5c31c0c03e11be.Unknown.Event', []));
    }
}
