import FungibleToken from "../contracts/FungibleToken.cdc"
import NonFungibleToken from "../contracts/NonFungibleToken.cdc"
import FlowToken from "../contracts/FlowToken.cdc"
import Sportvatar from "../contracts/Sportvatar.cdc"
import Sportbit from "../contracts/Sportbit.cdc"
import SportvatarTemplate from "../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../contracts/SportvatarPack.cdc"



// This script returns all the Sportvatars for a specific address

pub fun main(address:Address) : [Sportvatar.SportvatarData] {

    return Sportvatar.getSportvatars(address: address)

}
