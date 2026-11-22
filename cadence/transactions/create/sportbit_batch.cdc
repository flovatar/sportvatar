import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


transaction(templateId: UInt64, quantity: UInt64) {

    let sportvatarComponentCollection: &Sportbit.Collection
    let sportvatarAdmin: &Sportvatar.Admin

    prepare(account: AuthAccount) {
        self.sportvatarComponentCollection = account.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!

        self.sportvatarAdmin = account.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)!
    }

    execute {
        let collection <- self.sportvatarAdmin.batchCreateSportbits(templateId: templateId, quantity: quantity) as! @Sportbit.Collection

        for id in collection.getIDs() {
            self.sportvatarComponentCollection.deposit(token: <- collection.withdraw(withdrawID: id))
        }

        destroy collection

    }
}