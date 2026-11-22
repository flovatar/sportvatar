import FungibleToken from "../contracts/FungibleToken.cdc"
import NonFungibleToken from "../contracts/NonFungibleToken.cdc"
import FlowToken from "../contracts/FlowToken.cdc"
import Sportvatar from "../contracts/Sportvatar.cdc"
import Sportbit from "../contracts/Sportbit.cdc"
import SportvatarTemplate from "../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../contracts/SportvatarPack.cdc"



pub fun main(address:Address) : [UInt64] {
    return SportvatarPack.getPacks(address: address)!
}
