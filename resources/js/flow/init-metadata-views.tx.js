import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function initMetadataViewsTx() {
    return await fcl
        .mutate({
            cadence: `
import Sportvatar, Sportbit, SportvatarTemplate, SportvatarPack from 0xSportvatar
import FungibleToken from 0xFungible
import NonFungibleToken from 0xNonFungible
import FlowToken from 0xFlowToken
import MetadataViews from 0xMetadataViews


transaction {
  // We want the account's address for later so we can verify if the account was initialized properly
  let address: Address

  prepare(account: auth(Storage, Capabilities) &Account) {
    // save the address for the post check
    self.address = account.address

    let sportvatarCapMeta = account.capabilities.get<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath)
    if(!sportvatarCapMeta.check()) {
        account.capabilities.unpublish(Sportvatar.CollectionPublicPath)
        account.capabilities.publish(account.capabilities.storage.issue<&Sportvatar.Collection>(Sportvatar.CollectionStoragePath), at: Sportvatar.CollectionPublicPath)
    }

    let sportvatarComponentCapMeta = account.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath)
    if(!sportvatarComponentCapMeta.check()) {
        account.capabilities.unpublish(Sportbit.CollectionPublicPath)
        account.capabilities.publish(account.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath), at: Sportbit.CollectionPublicPath)
    }


  }

}
`,
            args: (arg, t) => [
            ],
            limit: 9999
        });

}
