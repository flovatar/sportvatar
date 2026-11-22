<?php

namespace App\Services\Flow;

/**
 * Event types the app indexes: the Sportvatar contract events on FLOW_MARKET_ADDRESS
 * (0xca5c31c0c03e11be on mainnet) handled by ProcessGraffleEvents, plus the global
 * NFTStorefrontV2 listing events, filtered to our NFT types.
 *
 * FlovatarComponent events (0x921ea449dffec68a) are not indexed here: the Sportvatar database
 * has no Flobit table, and equipping a Flovatar background emits Sportvatar.Updated, which
 * re-reads the Sportvatar (including its background) from the chain.
 *
 * The restriction events (SportvatarTemplate.AddressRestricted / IDsRestricted ..., added to the existing
 * SportvatarTemplate by the PR #3 in-place update: mainnet accepts no new contracts) only exist once it is deployed; until
 * then the REST API simply returns no rows for them.
 */
class FlowEventTypes
{
    const CONTRACT_EVENTS = [
        'SportvatarTemplate.CreatedSeries', 'SportvatarTemplate.Created',
        'Sportbit.Created', 'Sportbit.Withdraw', 'Sportbit.Deposit', 'Sportbit.Destroyed',
        'SportvatarPack.Created', 'SportvatarPack.Withdraw', 'SportvatarPack.Deposit', 'SportvatarPack.Opened',
        'SportvatarPack.Purchased', 'SportvatarPack.Claimed',
        'Sportvatar.Created', 'Sportvatar.Withdraw', 'Sportvatar.Deposit', 'Sportvatar.Updated', 'Sportvatar.Destroyed',
        'Sportvatar.NameSet', 'Sportvatar.PositionChanged', 'Sportvatar.StoryAdded',
        'SportvatarTemplate.AddressRestricted', 'SportvatarTemplate.AddressUnrestricted',
        'SportvatarTemplate.IDsRestricted', 'SportvatarTemplate.IDsUnrestricted',
    ];

    const STOREFRONT_EVENTS = ['NFTStorefrontV2.ListingAvailable', 'NFTStorefrontV2.ListingCompleted'];

    /** NFT types whose storefront listings we keep (marketplace items). */
    const NFT_CONTRACTS = ['Sportvatar.NFT', 'Sportbit.NFT', 'SportvatarPack.Pack'];

    /** Event types whose only effect is a re-read of the NFT from the chain (collapsible during backfills). */
    const RESYNC_GROUPS = [
        ['Sportvatar.Updated', 'Sportvatar.NameSet', 'Sportvatar.PositionChanged', 'Sportvatar.StoryAdded'],
    ];

    public static function contractAddress(): string
    {
        return strtolower(str_replace('0x', '', (string) config('services.flow.address')));
    }

    public static function storefrontAddress(): string
    {
        return strtolower(str_replace('0x', '', (string) config('services.flow.storefrontaddress')));
    }

    public static function type(string $event): string
    {
        return 'A.'.static::contractAddress().'.'.$event;
    }

    public static function contractTypes(): array
    {
        return array_map(fn ($e) => static::type($e), self::CONTRACT_EVENTS);
    }

    public static function storefrontTypes(): array
    {
        $s = static::storefrontAddress();

        return array_map(fn ($e) => "A.$s.$e", self::STOREFRONT_EVENTS);
    }

    public static function all(): array
    {
        return array_merge(static::contractTypes(), static::storefrontTypes());
    }

    public static function isStorefront(string $type): bool
    {
        return str_starts_with($type, 'A.'.static::storefrontAddress().'.');
    }

    public static function ourNftTypes(): array
    {
        return array_map(fn ($c) => static::type($c), self::NFT_CONTRACTS);
    }

    public static function resyncGroups(): array
    {
        return array_map(fn ($g) => array_map(fn ($e) => static::type($e), $g), self::RESYNC_GROUPS);
    }

    /** Storefront events are global: keep only listings of our NFT types. */
    public static function keep(string $type, $payload): bool
    {
        if (! in_array($type, static::all(), true)) {
            return false;
        }
        if (! static::isStorefront($type)) {
            return true;
        }
        $nft = is_array($payload) ? ($payload['nftType'] ?? null) : null;

        return is_array($nft) && in_array($nft['typeID'] ?? null, static::ourNftTypes(), true);
    }
}
