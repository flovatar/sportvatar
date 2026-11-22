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
    randomString: [String],
    price: UFix64,
    flameCount: UInt32,
    series: UInt32,
    name: String,
    packCount: UInt32,
    compPerPack: UInt32
    ) {

    let sportvatarComponentCollection: &Sportbit.Collection
    let sportvatarPackCollection: &SportvatarPack.Collection
    let sportvatarAdmin: &Sportvatar.Admin



    prepare(account: AuthAccount) {

        self.sportvatarComponentCollection = account.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!

        self.sportvatarPackCollection = account.borrow<&SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)!

        self.sportvatarAdmin = account.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)!


    }

    execute {

        var i: UInt32 = 0
        while i < packCount {

            let componentsNFT: @[Sportbit.NFT] <- []

            let totalNFTPerPack: UInt32 = compPerPack + flameCount

            var j: UInt32 = 0
            while j < totalNFTPerPack {
                let tempNFT <- self.sportvatarComponentCollection.withdraw(withdrawID: components[(i*totalNFTPerPack) + j]) as! @Sportbit.NFT
                componentsNFT.append(<-tempNFT)
                j = j + UInt32(1)
            }

            let sportvatarPack <- self.sportvatarAdmin.createPack(
                        components: <-componentsNFT,
                        randomString: randomString[i],
                        price: price,
                        flameCount: flameCount,
                        series: series,
                        name: name
                    ) as! @SportvatarPack.Pack

            self.sportvatarPackCollection.deposit(token: <-sportvatarPack)

            i = i + UInt32(1)
        }




    }
}
