import FungibleToken from 0x9a0766d93b6608b7
import NonFungibleToken from 0x631e88ae7f1d7c20
import DapperUtilityCoin from 0x82ec283f88a62e65
import NFTStorefrontV2 from 0x2d55b98eb200daef
import MetadataViews from 0x631e88ae7f1d7c20
import Sportvatar, Sportbit from 0xacb1ef5cc68a95b8

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
