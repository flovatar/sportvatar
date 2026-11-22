import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function getSportbitScript(address, componentId) {
    if (address == null) return null

    return await fcl
        .query({
            cadence: `
import Sportvatar, Sportbit, SportvatarTemplate, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import FlowToken from 0xFlowToken

access(all) fun main(address:Address, componentId: UInt64) : Sportbit.AccessoryData? {
    // get the accounts' public address objects
    return Sportbit.getSportbit(address: address, componentId: componentId)
}
`,
            args: (arg, t) => [
                arg(address, t.Address),
                arg(''+componentId, t.UInt64)
            ],
        });

}
