import Sportbit, SportvatarPack from 0xacb1ef5cc68a95b8
import NonFungibleToken from 0x631e88ae7f1d7c20
import FungibleToken from 0x9a0766d93b6608b7
import MetadataViews from 0x631e88ae7f1d7c20

transaction(packId: UInt64) {

    let sportvatarPackCollection: &SportvatarPack.Collection

    prepare(account: auth(Storage, Capabilities) &Account) {
        if account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath) == nil {
            account.storage.save<@{NonFungibleToken.Collection}>(<- Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>()), to: Sportbit.CollectionStoragePath)
        }
        let sportbitCap = account.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath)
        if(!sportbitCap.check()) {
            account.capabilities.unpublish(Sportbit.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath),
                at: Sportbit.CollectionPublicPath
            )
        }

        self.sportvatarPackCollection = account.storage.borrow<&SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)!
    }

    execute {
        self.sportvatarPackCollection.openPack(id: packId)
    }

}
