import Sportvatar, Sportbit from 0xacb1ef5cc68a95b8
import NonFungibleToken from 0x631e88ae7f1d7c20
import FungibleToken from 0x9a0766d93b6608b7

//this transaction will add a new Accessory to an existing Sportvatar
transaction(
    sportvatarId: UInt64,
    sportbitId: UInt64
    ) {

    let sportvatarCollection: &Sportvatar.Collection
    let sportvatarComponentCollection: &Sportbit.Collection
    let sportbitId: UInt64
    let sportvatarId: UInt64

    prepare(account: auth(Storage, Capabilities) &Account) {
        self.sportvatarCollection = account.storage.borrow<&Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath)!
        self.sportvatarComponentCollection = account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!
        self.sportbitId = sportbitId
        self.sportvatarId = sportvatarId
    }

    execute {
        let sportvatar: &{Sportvatar.Private} = self.sportvatarCollection.borrowSportvatarPrivate(id: self.sportvatarId)!

        let sportbit <- self.sportvatarComponentCollection.withdraw(withdrawID: self.sportbitId) as! @Sportbit.NFT
        let accessory <- sportvatar.setSportbit(layer: sportbit.getLayer(), sportbit: <-sportbit)
        if(accessory != nil){
            self.sportvatarComponentCollection.deposit(token: <-accessory!)
        } else {
            destroy accessory
        }
    }
}