import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function packOpenTx(packId) {
    return await fcl
        .mutate({
            cadence: `
import Sportbit, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible

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
`,
            args: (arg, t) => [
                arg(''+packId, t.UInt64)
            ],
            limit: 9999
        });

}
