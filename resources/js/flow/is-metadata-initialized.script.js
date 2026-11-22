import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function isMetadataInitializedScript(address) {
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

  let sportvatarCap = account.capabilities.get<&Sportvatar.Collection{Sportvatar.CollectionPublic, NonFungibleToken.CollectionPublic, NonFungibleToken.Receiver, MetadataViews.ResolverCollection}>(Sportvatar.CollectionPublicPath)
  let sportvatarComponentCap = account.capabilities.get<&Sportbit.Collection{Sportbit.CollectionPublic, NonFungibleToken.CollectionPublic, NonFungibleToken.Receiver, MetadataViews.ResolverCollection}>(Sportbit.CollectionPublicPath)

  return (sportvatarCap.check() && sportvatarComponentCap.check())
}
`,
            args: (arg, t) => [
                arg(address, t.Address)
            ],
        });

}
