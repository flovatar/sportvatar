import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function isInitializedScript(address) {
    if (address == null)
        throw new Error("isInitialized(address) -- address required")

    return await fcl
        .query({
            cadence: `
import Sportvatar, Sportbit, SportvatarTemplate, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import FlowToken from 0xFlowToken
import MetadataViews from 0xMetadataViews

access(all) fun main(address: Address): Bool {

  let account = getAccount(address)

  let sportvatarCap = account.capabilities.get<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath)
  let sportvatarComponentCap = account.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath)
  let sportvatarPackCap = account.capabilities.get<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath)


  return (sportvatarCap.check() && sportvatarComponentCap.check() && sportvatarPackCap.check())
}
`,
            args: (arg, t) => [
                arg(address, t.Address)
            ],
        });

}
