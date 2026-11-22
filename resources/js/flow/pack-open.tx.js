import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function packOpenTx(packId) {
    return await fcl
        .mutate({
            cadence: `
import Sportbit, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import MetadataViews from 0xMetadataViews

transaction(packId: UInt64) {

    let sportvatarPackCollection: &SportvatarPack.Collection

    prepare(account: auth(Storage, Capabilities) &Account) {
        if account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath) == nil {
            account.storage.save<@{NonFungibleToken.Collection}>(<- Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>()), to: Sportbit.CollectionStoragePath)
        }
        let sportbitCap = account.capabilities.get<&Sportbit.CollectionPublic>(Sportbit.CollectionPublicPath)
        if(!sportbitCap.check()) {
            account.capabilities.unpublish(Sportbit.CollectionPublicPath)
            account.capabilities.publish(account.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath), at: Sportbit.CollectionPublicPath)
        }

        self.sportvatarPackCollection = account.storage.borrow<&SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)!
    }

    execute {
        self.sportvatarPackCollection.openPack(id: packId)
    }

}
`,
            args: (arg, t) => [
                arg(''+packId, t.UInt64)
            ],
            limit: 9999
        });

}
