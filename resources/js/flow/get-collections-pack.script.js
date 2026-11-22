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

  access(all) var address: Address
  access(all) var sportvatars: [Sportvatar.SportvatarData]
  access(all) var components: [Sportbit.AccessoryData]
  access(all) var packs: [UInt64]
  init (_ address:Address) {
    self.address = address
    self.sportvatars = []
    self.components = []
    self.packs = []
  }
}

access(all) fun main(address:Address) : Collections {
    // get the accounts' public address objects
    let account = getAccount(address)
    let status = Collections(address)

    status.packs = SportvatarPack.getPacks(address: address) ?? []

    return status
}
`,
            args: (arg, t) => [
                arg(address, t.Address)
            ],
        });

}
