import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


//this transaction will send a Pack to an address
transaction(componentId: UInt64, address: Address) {

    let sportvatarComponentCollection: &Sportbit.Collection
    let sportvatarComponentReceiverCollection: Capability<&{Sportbit.CollectionPublic}>

    prepare(account: AuthAccount) {
        self.sportvatarComponentCollection = account.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!


        let receiverAccount = getAccount(address)
        self.sportvatarComponentReceiverCollection = receiverAccount.getCapability<&{Sportbit.CollectionPublic}>(Sportbit.CollectionPublicPath)
    }

    execute {
        let component <- self.sportvatarComponentCollection.withdraw(withdrawID: componentId)
        if(component == nil){
            panic("Component not found!")
        }
        self.sportvatarComponentReceiverCollection.borrow()!.deposit(token: <-component)
    }
}
