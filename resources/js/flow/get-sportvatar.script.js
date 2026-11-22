import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function getSportvatarScript(address, sportvatarId) {
    if (address == null) return null

    return await fcl
        .query({
            cadence: `
import Sportvatar, Sportbit, SportvatarTemplate, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import FlowToken from 0xFlowToken

access(all) fun main(address:Address, sportvatarId: UInt64) : Sportvatar.SportvatarData? {

    return Sportvatar.getSportvatar(address: address, sportvatarId: sportvatarId)

}
`,
            args: (arg, t) => [
                arg(address, t.Address),
                arg(''+sportvatarId, t.UInt64)
            ],
        });

}
