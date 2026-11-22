import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"



transaction() {

    let sportvatarAdmin: &Sportvatar.Admin

    prepare(account: AuthAccount) {
        self.sportvatarAdmin = account.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)!
    }

    execute {
        self.sportvatarAdmin.resetTemplatesCount()

    }

}
