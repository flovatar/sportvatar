import FungibleToken from "../contracts/FungibleToken.cdc"
import NonFungibleToken from "../contracts/NonFungibleToken.cdc"
import FlowToken from "../contracts/FlowToken.cdc"
import Sportvatar from "../contracts/Sportvatar.cdc"
import Sportbit from "../contracts/Sportbit.cdc"
import SportvatarTemplate from "../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../contracts/SportvatarPack.cdc"



// This script returns the available components

pub fun main(address:Address) : [UInt64] {

    var componentData: [UInt64] = []
    let account = getAccount(address)

    if let componentCollection = account.getCapability(Sportbit.CollectionPublicPath).borrow<&Sportbit.Collection{Sportbit.CollectionPublic}>()  {
        componentData = componentCollection.getIDs()
    }
    return componentData



}
