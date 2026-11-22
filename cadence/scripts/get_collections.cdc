import FungibleToken from "../contracts/FungibleToken.cdc"
import NonFungibleToken from "../contracts/NonFungibleToken.cdc"
import FlowToken from "../contracts/FlowToken.cdc"
import Sportvatar from "../contracts/Sportvatar.cdc"
import Sportbit from "../contracts/Sportbit.cdc"
import SportvatarTemplate from "../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../contracts/SportvatarPack.cdc"

pub struct Collections {

  pub(set) var address: Address
  pub(set) var sportvatars: [Sportvatar.SportvatarData]
  pub(set) var components: [Sportbit.AccessoryData]
  pub(set) var packs: [UInt64]
  init (_ address:Address) {
    self.address = address
    self.sportvatars = []
    self.components = []
    self.packs = []
  }
}

pub fun main(address:Address) : Collections {
    // get the accounts' public address objects
    let account = getAccount(address)
    let status = Collections(address)

    status.sportvatars = Sportvatar.getSportvatars(address: address)
    status.components = Sportbit.getComponents(address: address)
    status.packs = SportvatarPack.getPacks(address: address)!

    return status
}