import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function getCollectionsPackScript(address) {
    if (address == null) return null

    return await fcl
        .query({
            cadence: `
import Sportvatar, Sportbit, SportvatarTemplate, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import FlowToken from 0xFlowToken
access(all) struct Collections {

  access(all) let address: Address
  access(all) let sportvatars: [Sportvatar.SportvatarData]
  access(all) let components: [Sportbit.AccessoryData]
  access(all) let packs: [UInt64]
  init (_ address: Address, sportvatars: [Sportvatar.SportvatarData], components: [Sportbit.AccessoryData], packs: [UInt64]) {
    self.address = address
    self.sportvatars = sportvatars
    self.components = components
    self.packs = packs
  }
}

access(all) fun main(address: Address) : Collections {
    return Collections(address, sportvatars: [], components: [], packs: SportvatarPack.getPacks(address: address) ?? [])
}
`,
            args: (arg, t) => [
                arg(address, t.Address)
            ],
        });

}
