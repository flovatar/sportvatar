// Generated from resources/js/flow/pack-open.tx.js (testnet addresses). Keep in sync with the website tx.
import Sportbit, SportvatarPack from 0x4a9e28acee147fd0
import NonFungibleToken from 0x631e88ae7f1d7c20

// Opens a Pack owned by the signer; its Sportbits go to the signer's Sportbit collection.
transaction(packId: UInt64) {

    let sportvatarPackCollection: auth(SportvatarPack.WithdrawEnt) &SportvatarPack.Collection

    prepare(account: auth(Storage, Capabilities) &Account) {
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

        self.sportvatarPackCollection = account.storage.borrow<auth(SportvatarPack.WithdrawEnt) &SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)
            ?? panic("Could not borrow the Pack collection")
    }

    execute {
        self.sportvatarPackCollection.openPack(id: packId)
    }
}
