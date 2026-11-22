import NFTStorefrontV2 from 0x4eb8a10cb9f87357

transaction(listingResourceID: UInt64) {
    let storefront: &NFTStorefrontV2.Storefront{NFTStorefrontV2.StorefrontManager}

    prepare(acct: auth(Storage, Capabilities) &Account) {

        self.storefront = acct.storage.borrow<&NFTStorefrontV2.Storefront{NFTStorefrontV2.StorefrontManager}>(from: NFTStorefrontV2.StorefrontStoragePath)
            ?? panic("Missing or mis-typed NFTStorefrontV2.Storefront")

    }

    execute {
        self.storefront.removeListing(listingResourceID: listingResourceID)
    }
}
