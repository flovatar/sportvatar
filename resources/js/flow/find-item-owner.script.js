import * as fcl from "@onflow/fcl"

/**
 * Chain-first owner + listing lookup for headless item pages: returns {owner, listing} for the first candidate
 * address whose collection holds the item, or null. listing = the live NFTStorefrontV2 listing of that item in
 * the owner's storefront ({listingId, price, vault}) or nil. Storefront listings keep the NFT in the collection,
 * so owner = collection membership. One script for all candidates; cheap (ID lists + listing details, no SVG).
 * kind: sportvatar | sportbit
 */
export async function findItemOwnerScript(kind, itemId, candidates) {
    if (!candidates || !candidates.length) return null

    return await fcl
        .query({
            cadence: `
import Sportvatar, Sportbit from 0xSportvatar
import NFTStorefrontV2 from 0xStorefront2

access(all) struct ItemOwner {
    access(all) let owner: Address
    access(all) let listingId: UInt64?
    access(all) let price: UFix64?
    access(all) let vault: String?
    init(owner: Address, listingId: UInt64?, price: UFix64?, vault: String?) {
        self.owner = owner
        self.listingId = listingId
        self.price = price
        self.vault = vault
    }
}

access(all) fun main(kind: String, itemId: UInt64, candidates: [Address]): ItemOwner? {
    let nftType = kind == "sportvatar" ? Type<@Sportvatar.NFT>() : Type<@Sportbit.NFT>()
    for a in candidates {
        let acct = getAccount(a)
        var ids: [UInt64] = []
        if kind == "sportvatar" {
            if let c = acct.capabilities.borrow<&{Sportvatar.CollectionPublic}>(Sportvatar.CollectionPublicPath) { ids = c.getIDs() }
        } else {
            if let c = acct.capabilities.borrow<&{Sportbit.CollectionPublic}>(Sportbit.CollectionPublicPath) { ids = c.getIDs() }
        }
        if !ids.contains(itemId) {
            continue
        }
        if let sf = acct.capabilities.borrow<&{NFTStorefrontV2.StorefrontPublic}>(NFTStorefrontV2.StorefrontPublicPath) {
            for lid in sf.getListingIDs() {
                if let l = sf.borrowListing(listingResourceID: lid) {
                    let d = l.getDetails()
                    if d.nftID == itemId && d.nftType == nftType && !d.purchased {
                        return ItemOwner(owner: a, listingId: lid, price: d.salePrice, vault: d.salePaymentVaultType.identifier)
                    }
                }
            }
        }
        return ItemOwner(owner: a, listingId: nil, price: nil, vault: nil)
    }
    return nil
}
`,
            args: (arg, t) => [
                arg(kind, t.String),
                arg('' + itemId, t.UInt64),
                arg(candidates, t.Array(t.Address))
            ],
        });
}
