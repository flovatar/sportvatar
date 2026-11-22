// Generated from resources/js/flow/sportvatar-sale-cancel.tx.js (testnet addresses). Keep in sync with the website tx.
import NFTStorefrontV2 from 0x2d55b98eb200daef

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
