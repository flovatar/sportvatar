import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function sportvatarSaleCancelTx(listingResourceID) {
    return await fcl
        .mutate({
            cadence: `
import NFTStorefrontV2 from 0xStorefront2

transaction(listingResourceID: UInt64) {
    let storefront: &NFTStorefrontV2.Storefront

    prepare(acct: auth(Storage, Capabilities) &Account) {

        self.storefront = acct.storage.borrow<&NFTStorefrontV2.Storefront>(from: NFTStorefrontV2.StorefrontStoragePath)
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

