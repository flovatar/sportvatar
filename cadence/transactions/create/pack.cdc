import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


//this transaction will create a new Pack from a group of components
transaction(
    components: [UInt64],
    randomString: String,
    price: UFix64,
    sparkCount: UInt32,
    series: UInt32,
    name: String
    ) {

    let sportvatarComponentCollection: &Sportbit.Collection
    let sportvatarPackCollection: &SportvatarPack.Collection
    let sportvatarAdmin: &Sportvatar.Admin

    let componentsNFT: @[Sportbit.NFT]


    prepare(account: AuthAccount) {

        self.componentsNFT <- []

        self.sportvatarComponentCollection = account.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!

        self.sportvatarPackCollection = account.borrow<&SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)!

        self.sportvatarAdmin = account.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)!

        for componentId in components {
            let tempNFT <- self.sportvatarComponentCollection.withdraw(withdrawID: componentId) as! @Sportbit.NFT
            self.componentsNFT.append(<-tempNFT)
        }

    }

    execute {
        let sportvatarPack <- self.sportvatarAdmin.createPack(
            components: <-self.componentsNFT,
            randomString: randomString,
            price: price,
            sparkCount: sparkCount,
            series: series,
            name: name
        ) as! @SportvatarPack.Pack

        self.sportvatarPackCollection.deposit(token: <-sportvatarPack)

    }
}
