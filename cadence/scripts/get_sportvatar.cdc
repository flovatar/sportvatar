import FungibleToken from "../contracts/FungibleToken.cdc"
import NonFungibleToken from "../contracts/NonFungibleToken.cdc"
import FlowToken from "../contracts/FlowToken.cdc"
import Sportvatar from "../contracts/Sportvatar.cdc"
import Sportbit from "../contracts/Sportbit.cdc"
import SportvatarTemplate from "../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../contracts/SportvatarPack.cdc"



// This script returns the available webshots

pub fun main(address:Address, sportvatarId: UInt64) : Sportvatar.SportvatarData? {

    return Sportvatar.getSportvatar(address: address, sportvatarId: sportvatarId)

}
