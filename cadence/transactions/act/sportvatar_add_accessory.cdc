import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


//this transaction will add a new Accessory to an existing Sportvatar
transaction(
    sportvatarId: UInt64,
    accessory: UInt64
    ) {

    let sportvatarCollection: &Sportvatar.Collection
    let sportvatarComponentCollection: &Sportbit.Collection

    let accessoryNFT: @Sportbit.NFT

    prepare(account: AuthAccount) {
        self.sportvatarCollection = account.borrow<&Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath)!

        self.sportvatarComponentCollection = account.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!

        self.accessoryNFT <- self.sportvatarComponentCollection.withdraw(withdrawID: accessory) as! @Sportbit.NFT
    }

    execute {

        let sportvatar: &{Sportvatar.Private} = self.sportvatarCollection.borrowSportvatarPrivate(id: sportvatarId)!

        let accessory <-sportvatar.setAccessory(component: <-self.accessoryNFT)
        if(accessory != nil){
            self.sportvatarComponentCollection.deposit(token: <-accessory!)
        } else {
            destroy accessory
        }
    }
}