import Sportvatar, Sportbit from 0xacb1ef5cc68a95b8
import NonFungibleToken from 0x631e88ae7f1d7c20
import FungibleToken from 0x9a0766d93b6608b7

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