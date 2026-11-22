import Sportvatar, Sportbit from 0xca5c31c0c03e11be
import NonFungibleToken from 0x1d7e57aa55817448
import FungibleToken from 0xf233dcee88fe0abe

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