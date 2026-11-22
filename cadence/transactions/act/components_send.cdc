import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


//this transaction will send a Pack to an address
transaction(componentIds: [UInt64], address: Address) {

    let sportvatarComponentCollection: &Sportbit.Collection
    let sportvatarComponentReceiverCollection: Capability<&{Sportbit.CollectionPublic}>

    prepare(account: AuthAccount) {
        self.sportvatarComponentCollection = account.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!


        let receiverAccount = getAccount(address)
        self.sportvatarComponentReceiverCollection = receiverAccount.getCapability<&{Sportbit.CollectionPublic}>(Sportbit.CollectionPublicPath)
    }

    execute {
        var i: UInt32 = 0

        while i < UInt32(componentIds.length) {
            let component <- self.sportvatarComponentCollection.withdraw(withdrawID: componentIds[i])
            if(component == nil){
                panic("Component not found!")
            }
            self.sportvatarComponentReceiverCollection.borrow()!.deposit(token: <-component)

            i = i + UInt32(1)
        }
    }
}
