import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function checkPacksScript(id) {
    if (id == null) return null

    return await fcl
        .query({
            cadence: `
import Sportvatar, Sportbit, SportvatarTemplate, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import FlowToken from 0xFlowToken

access(all) fun main(id: UInt64) : Bool {

    return SportvatarPack.checkPackAvailable(id: id)

}
`,
            args: (arg, t) => [
                arg(''+id, t.UInt64)
            ],
        });

}

