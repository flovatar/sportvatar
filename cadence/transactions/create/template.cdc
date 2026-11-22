import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"



//this transaction will create a new Sportvatar Template
transaction(
    name: String,
    description: String,
    series: UInt64,
    layer: UInt32,
    sport: String,
    rarity: String,
    svg: String,
    maxMintableComponents: UInt64) {

    let sportvatarTemplateCollection: &SportvatarTemplate.Collection
    let sportvatarAdmin: &Sportvatar.Admin

    prepare(account: AuthAccount) {
        self.sportvatarTemplateCollection = account.borrow<&SportvatarTemplate.Collection>(from: SportvatarTemplate.CollectionStoragePath)!

        self.sportvatarAdmin = account.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)!
    }

    execute {
        let metadata:{String: String} = {}
        let sportvatarTemplate <- self.sportvatarAdmin.createTemplate(
            name: name,
            description: description,
            series: series,
            layer: layer,
            metadata: metadata,
            rarity: rarity,
            sport: sport,
            svg: svg,
            maxMintableComponents: maxMintableComponents
            ) as! @SportvatarTemplate.Template

        self.sportvatarTemplateCollection.deposit(template: <-sportvatarTemplate)

    }
}
