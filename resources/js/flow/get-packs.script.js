import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function getPacksScript(address) {
    if (address == null) return null

    return await fcl
        .query({
            cadence: `
import Sportvatar, Sportbit, SportvatarTemplate, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import FlowToken from 0xFlowToken

access(all) fun main(address:Address) : [UInt64] {

    return SportvatarPack.getPacks(address: address)

}
`,
            args: (arg, t) => [
                arg(address, t.Address)
            ],
        });

}

