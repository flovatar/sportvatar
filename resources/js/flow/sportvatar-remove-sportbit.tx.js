import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function sportvatarRemoveSportbitTx(sportvatarId, layer) {

    return await fcl
        .mutate({
            cadence: `
import Sportvatar, Sportbit from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible

//this transaction will add a new Accessory to an existing Sportvatar
transaction(
    sportvatarId: UInt64,
    layer: UInt32
    ) {

    let sportvatarCollection: &Sportvatar.Collection
    let sportvatarComponentCollection: &Sportbit.Collection
    let sportvatarId: UInt64
    let layer: UInt32

    prepare(account: auth(Storage, Capabilities) &Account) {
        self.sportvatarCollection = account.storage.borrow<&Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath)!
        self.sportvatarComponentCollection = account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!
        self.layer = layer
        self.sportvatarId = sportvatarId
    }

    execute {
        let sportvatar: &{Sportvatar.Private} = self.sportvatarCollection.borrowSportvatarPrivate(id: self.sportvatarId)!

        let accessory <- sportvatar.removeAccessory(layer: self.layer)
        if(accessory != nil){
            self.sportvatarComponentCollection.deposit(token: <-accessory!)
        } else {
            destroy accessory
        }
    }
}
`,
            args: (arg, t) => [
                arg(''+sportvatarId, t.UInt64),
                arg(''+layer, t.UInt32)
            ],
            limit: 9999
        });

}
