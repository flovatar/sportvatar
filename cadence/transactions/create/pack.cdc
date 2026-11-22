import "Sportvatar"
import "Sportbit"
import "SportvatarPack"
import "NonFungibleToken"

// Admin: creates a Pack from Sportbits in the admin's collection and stores it in the admin's Pack collection.
transaction(
    components: [UInt64],
    randomString: String,
    price: UFix64,
    flameCount: UInt32,
    series: UInt32,
    name: String
    ) {

    let packCollection: &SportvatarPack.Collection
    let sportvatarAdmin: &Sportvatar.Admin
    let componentsNFT: @[Sportbit.NFT]

    prepare(account: auth(BorrowValue) &Account) {
        let sportbitCollection = account.storage.borrow<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>(from: Sportbit.CollectionStoragePath)
            ?? panic("Missing Sportbit collection")
        self.packCollection = account.storage.borrow<&SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)
            ?? panic("Missing Pack collection")
        self.sportvatarAdmin = account.storage.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)
            ?? panic("Missing Sportvatar admin")
        let nfts: @[Sportbit.NFT] <- []
        for componentId in components {
            nfts.append(<- (sportbitCollection.withdraw(withdrawID: componentId) as! @Sportbit.NFT))
        }
        self.componentsNFT <- nfts
    }

    execute {
        let pack <- self.sportvatarAdmin.createPack(
            components: <-self.componentsNFT,
            randomString: randomString,
            price: price,
            flameCount: flameCount,
            series: series,
            name: name
        )
        self.packCollection.deposit(token: <-pack)
    }
}
