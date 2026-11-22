import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


transaction() {
    prepare(account: AuthAccount) {
        if(account.getCapability<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath) == nil) {
            account.save<@SportvatarPack.Collection>(<- SportvatarPack.createEmptyCollection(), to: SportvatarPack.CollectionStoragePath)
            account.link<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath, target: SportvatarPack.CollectionStoragePath)
        }
    }

}