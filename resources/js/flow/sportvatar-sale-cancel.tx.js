import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function sportvatarSaleCancelTx(listingResourceID) {
    return await fcl
        .mutate({
            cadence: `
import NFTStorefrontV2 from 0xStorefront2

// Removes a Storefront listing owned by the signer.
transaction(listingResourceID: UInt64) {
    let storefront: auth(NFTStorefrontV2.RemoveListing) &NFTStorefrontV2.Storefront

    prepare(account: auth(BorrowValue) &Account) {
        self.storefront = account.storage.borrow<auth(NFTStorefrontV2.RemoveListing) &NFTStorefrontV2.Storefront>(from: NFTStorefrontV2.StorefrontStoragePath)
            ?? panic("Missing or mis-typed NFTStorefrontV2.Storefront")
    }

    execute {
        self.storefront.removeListing(listingResourceID: listingResourceID)
    }
}
`,
            args: (arg, t) => [
                arg(''+listingResourceID, t.UInt64)
            ],
            limit: 9999
        });

}

