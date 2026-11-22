import FungibleToken from "../contracts/FungibleToken.cdc"
import NonFungibleToken from "../contracts/NonFungibleToken.cdc"
import FlowToken from "../contracts/FlowToken.cdc"
import Sportvatar from "../contracts/Sportvatar.cdc"
import Sportbit from "../contracts/Sportbit.cdc"
import SportvatarTemplate from "../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../contracts/SportvatarPack.cdc"
//import SportvatarTemplate from 0x15c15f0ee50148ba


// This script returns the available websites

pub fun main() : [SportvatarTemplate.SeriesData] {

    return SportvatarTemplate.getSeriesAll()

}
