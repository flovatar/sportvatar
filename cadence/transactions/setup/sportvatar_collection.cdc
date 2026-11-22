import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


transaction() {
    prepare(account: AuthAccount) {
        if(account.getCapability<&{Sportvatar.CollectionPublic}>(Sportvatar.CollectionPublicPath) == nil) {
            account.save<@NonFungibleToken.Collection>(<- Sportvatar.createEmptyCollection(), to: Sportvatar.CollectionStoragePath)
            account.link<&{Sportvatar.CollectionPublic}>(Sportvatar.CollectionPublicPath, target: Sportvatar.CollectionStoragePath)
        }
    }

}
