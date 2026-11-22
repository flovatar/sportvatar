import FungibleToken from "../contracts/FungibleToken.cdc"
import NonFungibleToken from "../contracts/NonFungibleToken.cdc"
import FlowToken from "../contracts/FlowToken.cdc"
import Sportvatar from "../contracts/Sportvatar.cdc"
import Sportbit from "../contracts/Sportbit.cdc"
import SportvatarTemplate from "../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../contracts/SportvatarPack.cdc"


pub fun main(address: Address): Bool {

  let account = getAccount(address)

  let sportvatarCap = account.getCapability<&{Sportvatar.CollectionPublic}>(Sportvatar.CollectionPublicPath)
  let sportvatarComponentCap = account.getCapability<&{Sportbit.CollectionPublic}>(Sportbit.CollectionPublicPath)
  let sportvatarPackCap = account.getCapability<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath)


  return (sportvatarCap.check() && sportvatarComponentCap.check() && sportvatarPackCap.check())
}
