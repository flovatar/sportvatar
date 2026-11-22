import FungibleToken from 0xf233dcee88fe0abe
import NonFungibleToken from 0x1d7e57aa55817448
import Sportvatar, Sportbit from 0x921ea449dffec68a

transaction(
    sportvatarId: UInt64,
    address: Address) {

    let sportvatarCollection: &Sportvatar.Collection
    let sportvatarReceiverCollection: Capability<&Sportvatar.Collection>

    prepare(account: auth(Storage, Capabilities) &Account) {
        self.sportvatarCollection = account.storage.borrow<&Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath)!


        let receiverAccount = getAccount(address)
        self.sportvatarReceiverCollection = receiverAccount.capabilities.get<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath)
    }

    execute {
        let sportvatar <- self.sportvatarCollection.withdraw(withdrawID: sportvatarId)
        if(sportvatar == nil){
            panic("Sportvatar not found!")
        }
        self.sportvatarReceiverCollection.borrow()!.deposit(token: <-sportvatar)
    }
}
