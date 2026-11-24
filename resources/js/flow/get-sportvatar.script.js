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

// Same SportvatarData as Sportvatar.getSportvatar() but with svg: nil. getSvg() composes every layer template SVG on
// chain and exceeds the script computation limit (error 1110) for ~3% of mainnet Sportvatars (e.g. #799), which left
// their page on "Loading"; the site never reads it (the picture is composed in the browser from the template SVGs).
access(all) fun main(address: Address, sportvatarId: UInt64): Sportvatar.SportvatarData? {
    if let col = getAccount(address).capabilities.borrow<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath) {
        if let s = col.borrowSportvatar(id: sportvatarId) {
            return Sportvatar.SportvatarData(id: sportvatarId, mint: s.mint, series: s.series, name: s.getName(), rarity: s.rarity, svg: nil, combination: s.combination, creatorAddress: s.creatorAddress, layers: s.getLayers(), bio: s.getBio(), metadata: s.getMetadata(), stats: s.getStats())
        }
    }
    return nil
}
`,
            args: (arg, t) => [
                arg(address, t.Address),
                arg(''+sportvatarId, t.UInt64)
            ],
        });

}
