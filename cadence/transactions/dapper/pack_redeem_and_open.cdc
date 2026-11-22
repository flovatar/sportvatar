// Generated from resources/js/flow/pack-redeem-and-open.tx.js (mainnet addresses). Keep in sync with the website tx.
import Sportbit, SportvatarPack from 0xca5c31c0c03e11be
import NonFungibleToken from 0x1d7e57aa55817448

// Claims a free Sportvatar Pack with a v2 authorisation (bound to this account, with expiry) and opens it.
// The authorisation (tokenId, expiry, signature) comes from the Sportvatar back-end after a verified wallet login.
transaction(tokenId: UInt64, expiry: UInt64, signature: String) {

    let collectionCap: Capability<&{SportvatarPack.CollectionPublic}>
    let sportvatarPackCollection: auth(SportvatarPack.WithdrawEnt) &SportvatarPack.Collection

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
        if account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath) == nil {
            account.storage.save<@{NonFungibleToken.Collection}>(<- Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>()), to: Sportbit.CollectionStoragePath)
        }
        if !account.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath).check() {
            account.capabilities.unpublish(Sportbit.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath),
                at: Sportbit.CollectionPublicPath
            )
        }

        self.collectionCap = account.capabilities.get<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath)
        self.sportvatarPackCollection = account.storage.borrow<auth(SportvatarPack.WithdrawEnt) &SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)
            ?? panic("Could not borrow the Pack collection")
    }

    execute {
        SportvatarPack.claimFreePack(tokenId: tokenId, recipientCap: self.collectionCap, expiry: expiry, signature: signature)

        self.sportvatarPackCollection.openPack(id: tokenId)
    }
}
