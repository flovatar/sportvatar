// Generated from resources/js/flow/sportvatar-remove-sportbit.tx.js (mainnet addresses). Keep in sync with the website tx.
import Sportvatar, Sportbit from 0xca5c31c0c03e11be
import NonFungibleToken from 0x1d7e57aa55817448

// Unequips the Sportbit on the given layer of a Sportvatar owned by the signer
// and puts it back into the signer's Sportbit collection.
transaction(
    sportvatarId: UInt64,
    layer: UInt32
    ) {

    let sportvatarCollection: auth(Sportvatar.PrivateEnt) &Sportvatar.Collection
    let sportbitCollection: &Sportbit.Collection

    prepare(account: auth(BorrowValue) &Account) {
        self.sportvatarCollection = account.storage.borrow<auth(Sportvatar.PrivateEnt) &Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath)
            ?? panic("Could not borrow the Sportvatar collection")
        self.sportbitCollection = account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)
            ?? panic("Could not borrow the Sportbit collection")
    }

    execute {
        let sportvatar = self.sportvatarCollection.borrowSportvatarPrivate(id: sportvatarId)
            ?? panic("Sportvatar not found in the signer's collection")

        let accessory <- sportvatar.removeAccessory(layer: layer)
        if accessory != nil {
            self.sportbitCollection.deposit(token: <-accessory!)
        } else {
            destroy accessory
        }
    }
}
