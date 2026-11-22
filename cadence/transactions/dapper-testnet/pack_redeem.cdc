// Generated from resources/js/flow/pack-redeem.tx.js (testnet addresses). Keep in sync with the website tx.
import SportvatarPack from 0x4a9e28acee147fd0

// Claims a free Sportvatar Pack with a v2 authorisation (bound to this account, with expiry).
// The authorisation (tokenId, expiry, signature) comes from the Sportvatar back-end after a verified wallet login.
transaction(tokenId: UInt64, expiry: UInt64, signature: String) {

    let collectionCap: Capability<&{SportvatarPack.CollectionPublic}>

    prepare(account: auth(Storage, Capabilities) &Account) {
        // Make sure the buyer has a Pack collection and a working public capability
        if account.storage.borrow<&SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath) == nil {
            account.storage.save<@SportvatarPack.Collection>(<- SportvatarPack.createEmptyCollection(), to: SportvatarPack.CollectionStoragePath)
        }
        if !account.capabilities.get<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath).check() {
            account.capabilities.unpublish(SportvatarPack.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&SportvatarPack.Collection>(SportvatarPack.CollectionStoragePath),
                at: SportvatarPack.CollectionPublicPath
            )
        }

        self.collectionCap = account.capabilities.get<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath)
    }

    execute {
        SportvatarPack.claimFreePack(tokenId: tokenId, recipientCap: self.collectionCap, expiry: expiry, signature: signature)
    }
}
