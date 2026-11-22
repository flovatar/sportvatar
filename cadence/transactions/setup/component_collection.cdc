import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


transaction() {
    prepare(account: AuthAccount) {
        if(account.getCapability<&{Sportbit.CollectionPublic}>(Sportbit.CollectionPublicPath) == nil) {
            account.save<@NonFungibleToken.Collection>(<- Sportbit.createEmptyCollection(), to: Sportbit.CollectionStoragePath)
            account.link<&{Sportbit.CollectionPublic}>(Sportbit.CollectionPublicPath, target: Sportbit.CollectionStoragePath)
        }
    }

}
