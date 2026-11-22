import FungibleToken from 0xf233dcee88fe0abe
import NonFungibleToken from 0x1d7e57aa55817448
import Sportvatar, Sportbit from 0x921ea449dffec68a

transaction(
    componentId: UInt64,
    address: Address) {

    let sportvatarComponentCollection: &Sportbit.Collection
    let sportvatarComponentReceiverCollection: Capability<&Sportbit.Collection>

    prepare(account: auth(Storage, Capabilities) &Account) {
        self.sportvatarComponentCollection = account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!


        let receiverAccount = getAccount(address)
        self.sportvatarComponentReceiverCollection = receiverAccount.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath)
    }

    execute {
        let component <- self.sportvatarComponentCollection.withdraw(withdrawID: componentId)
        if(component == nil){
            panic("Component not found!")
        }
        self.sportvatarComponentReceiverCollection.borrow()!.deposit(token: <-component)
    }
}
