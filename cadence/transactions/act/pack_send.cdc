import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


//this transaction will send a Pack to an address
transaction(packId: UInt64, address: Address) {
    
    let sportvatarPackCollection: &SportvatarPack.Collection
    let sportvatarPackReceiverCollection: Capability<&{SportvatarPack.CollectionPublic}>

    prepare(account: AuthAccount) {
        self.sportvatarPackCollection = account.borrow<&SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)!


        let receiverAccount = getAccount(address)
        self.sportvatarPackReceiverCollection = receiverAccount.getCapability<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath)
    }

    execute {
        let pack <- self.sportvatarPackCollection.withdraw(withdrawID: packId)
        if(pack == nil){
            panic("Pack not found!")
        }
        self.sportvatarPackReceiverCollection.borrow()!.deposit(token: <-pack)
    }
}