import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


transaction(templateId: UInt64) {

    let sportbitCollection: &Sportbit.Collection
    let sportvatarAdmin: &Sportvatar.Admin

    prepare(account: AuthAccount) {
        self.sportbitCollection = account.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!

        self.sportvatarAdmin = account.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)!
    }

    execute {
        let sportbit <- self.sportvatarAdmin.createSportbit(templateId: templateId) as! @Sportbit.NFT

        self.sportbitCollection.deposit(token: <-sportbit)

    }
}