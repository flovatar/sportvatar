import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function getSportvatarIdsScript(address) {
    if (address == null) return null

    return await fcl
        .query({
            cadence: `
import Sportvatar, Sportbit, SportvatarTemplate, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import FlowToken from 0xFlowToken

access(all) fun main(address:Address) : [UInt64] {

    let account = getAccount(address)
    var arr:[UInt64] = []

    if let sportvatarCollection = account.capabilities.borrow<&{Sportvatar.CollectionPublic}>(Sportvatar.CollectionPublicPath)  {
        arr = sportvatarCollection.getIDs()
    }
    
    return arr

}
`,
            args: (arg, t) => [
                arg(address, t.Address)
            ],
        });

}

