import Sportvatar, Sportbit, SportvatarTemplate, SportvatarPack from 0x921ea449dffec68a
import NonFungibleToken from 0x1d7e57aa55817448
import FungibleToken from 0xf233dcee88fe0abe
import FlowToken from 0x1654653399040a61

//this transaction will remove the Eyeglasses from an existing Sportvatar
transaction(
    sportvatarId: UInt64
    ) {

    let sportvatarCollection: &Sportvatar.Collection
    let sportvatarComponentCollection: &Sportbit.Collection

    prepare(account: auth(Storage, Capabilities) &Account) {
        self.sportvatarCollection = account.storage.borrow<&Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath)!

        self.sportvatarComponentCollection = account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!
    }

    execute {

        let sportvatar: &{Sportvatar.Private} = self.sportvatarCollection.borrowSportvatarPrivate(id: sportvatarId)!

        let eyeglasses <-sportvatar.removeEyeglasses()
        if(eyeglasses != nil){
            self.sportvatarComponentCollection.deposit(token: <-eyeglasses!)
        } else {
            destroy eyeglasses
        }
    }
}
