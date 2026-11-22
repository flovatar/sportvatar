import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"

transaction {
  // We want the account's address for later so we can verify if the account was initialized properly
  let address: Address

  prepare(account: AuthAccount) {
    // save the address for the post check
    self.address = account.address


    let sportvatarCap = account.getCapability<&{Sportvatar.CollectionPublic}>(Sportvatar.CollectionPublicPath)
    if(!sportvatarCap.check()) {
        // store an empty NFT Collection in account storage
        account.save<@NonFungibleToken.Collection>(<- Sportvatar.createEmptyCollection(), to: Sportvatar.CollectionStoragePath)

        // publish a capability to the Collection in storage
        account.link<&{Sportvatar.CollectionPublic}>(Sportvatar.CollectionPublicPath, target: Sportvatar.CollectionStoragePath)
    }

    let sportbitCap = account.getCapability<&{Sportbit.CollectionPublic}>(Sportbit.CollectionPublicPath)
    if(!sportbitCap.check()) {
        account.save<@NonFungibleToken.Collection>(<- Sportbit.createEmptyCollection(), to: Sportbit.CollectionStoragePath)
        account.link<&{Sportbit.CollectionPublic}>(Sportbit.CollectionPublicPath, target: Sportbit.CollectionStoragePath)
    }

    let sportvatarPackCap = account.getCapability<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath)
    if(!sportvatarPackCap.check()) {
        account.save<@SportvatarPack.Collection>(<- SportvatarPack.createEmptyCollection(), to: SportvatarPack.CollectionStoragePath)
        account.link<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath, target: SportvatarPack.CollectionStoragePath)
    }

  }

}