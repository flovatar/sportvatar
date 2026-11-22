import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


//this transaction will open a Pack from the Collection
transaction(packId: UInt64) {
    
    let sportvatarPackCollection: &SportvatarPack.Collection

    prepare(account: AuthAccount) {
        self.sportvatarPackCollection = account.borrow<&SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)!
    }

    execute {
        self.sportvatarPackCollection.openPack(id: packId)
    }
}