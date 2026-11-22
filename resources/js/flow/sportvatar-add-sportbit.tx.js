import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function sportvatarAddSportbitTx(sportvatarId, sportbitId) {

    return await fcl
        .mutate({
            cadence: `
import Sportvatar, Sportbit from 0xSportvatar
import NonFungibleToken from 0xNonFungible

// Equips a Sportbit on a Sportvatar owned by the signer.
// The Sportvatar collection is borrowed from storage with the PrivateEnt entitlement,
// which the public collection capability does not carry.
transaction(
    sportvatarId: UInt64,
    sportbitId: UInt64
    ) {

    let sportvatarCollection: auth(Sportvatar.PrivateEnt) &Sportvatar.Collection
    let sportbitCollection: auth(NonFungibleToken.Withdraw) &Sportbit.Collection

    prepare(account: auth(BorrowValue) &Account) {
        self.sportvatarCollection = account.storage.borrow<auth(Sportvatar.PrivateEnt) &Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath)
            ?? panic("Could not borrow the Sportvatar collection")
        self.sportbitCollection = account.storage.borrow<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>(from: Sportbit.CollectionStoragePath)
            ?? panic("Could not borrow the Sportbit collection")
    }

    execute {
        let sportvatar = self.sportvatarCollection.borrowSportvatarPrivate(id: sportvatarId)
            ?? panic("Sportvatar not found in the signer's collection")

        let sportbit <- self.sportbitCollection.withdraw(withdrawID: sportbitId) as! @Sportbit.NFT
        let accessory <- sportvatar.setSportbit(layer: sportbit.getLayer(), sportbit: <-sportbit)
        if accessory != nil {
            self.sportbitCollection.deposit(token: <-accessory!)
        } else {
            destroy accessory
        }
    }
}
`,
            args: (arg, t) => [
                arg(''+sportvatarId, t.UInt64),
                arg(''+sportbitId, t.UInt64)
            ],
            limit: 9999
        });

}
