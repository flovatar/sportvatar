import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function initAccountTx() {
    return await fcl
        .mutate({
            cadence: `
import FungibleToken from 0xFungible
import NonFungibleToken from 0xNonFungible
import DapperUtilityCoin from 0xDuc
import NFTStorefrontV2 from 0xStorefront2
import MetadataViews from 0xMetadataViews
import Sportvatar, Sportbit from 0xSportvatar

transaction {
  // We want the account's address for later so we can verify if the account was initialized properly
  let address: Address

  prepare(account: auth(Storage, Capabilities) &Account) {
    // save the address for the post check
    self.address = account.address


    let sportvatarCap = account.capabilities.get<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath)
    if(!sportvatarCap.check()) {
        account.storage.save<@{NonFungibleToken.Collection}>(<- Sportvatar.createEmptyCollection(nftType: Type<@Sportvatar.Collection>()), to: Sportvatar.CollectionStoragePath)
        account.capabilities.unpublish(Sportvatar.CollectionPublicPath)
        account.capabilities.publish(
            account.capabilities.storage.issue<&Sportvatar.Collection>(Sportvatar.CollectionStoragePath),
            at: Sportvatar.CollectionPublicPath
        )
    }

    let sportvatarComponentCap = account.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath)
    if(!sportvatarComponentCap.check()) {
        account.storage.save<@{NonFungibleToken.Collection}>(<- Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>()), to: Sportbit.CollectionStoragePath)
        account.capabilities.unpublish(Sportbit.CollectionPublicPath)
        account.capabilities.publish(
            account.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath),
            at: Sportbit.CollectionPublicPath
        )
    }

    // If the account doesn't already have a Storefront
    if account.storage.borrow<&NFTStorefrontV2.Storefront>(from: NFTStorefrontV2.StorefrontStoragePath) == nil {

        // Create a new empty Storefront
        let storefront <- NFTStorefrontV2.createStorefront() as! @NFTStorefrontV2.Storefront

        // save it to the account
        account.storage.save(<-storefront, to: NFTStorefrontV2.StorefrontStoragePath)

        // create a public capability for the Storefront
        account.capabilities.unpublish(NFTStorefrontV2.StorefrontPublicPath)
        account.capabilities.publish(
            account.capabilities.storage.issue<&NFTStorefrontV2.Storefront>(NFTStorefrontV2.StorefrontStoragePath),
            at: NFTStorefrontV2.StorefrontPublicPath
        )
    }


  }

}
`,
            args: (arg, t) => [
            ],
            limit: 9999
        });

}
