import FungibleToken from "../contracts/FungibleToken.cdc"
import NonFungibleToken from "../contracts/NonFungibleToken.cdc"
import FlowToken from "../contracts/FlowToken.cdc"
import Sportvatar from "../contracts/Sportvatar.cdc"
import Sportbit from "../contracts/Sportbit.cdc"
import SportvatarTemplate from "../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../contracts/SportvatarPack.cdc"


pub fun main(address: Address): Int {
  if let collection = getAccount(address).getCapability(Sportvatar.CollectionPublicPath).borrow<&{Sportvatar.CollectionPublic}>() {
      var count: Int = 0;
      let sportvatarsData: [Sportvatar.SportvatarData] = Sportvatar.getSportvatars(address: address)
      for sportvatar in sportvatarsData {
        let str = sportvatar.metadata.combination.slice(from: sportvatar.metadata.combination.length - Int(4), upTo: sportvatar.metadata.combination.length)
        if(str == "C133" || str == "C134" || str == "C135" || str == "C136" || str == "C137"){
          count = count + Int(1)
        }
      }
      return count
  }
  return -1
}
