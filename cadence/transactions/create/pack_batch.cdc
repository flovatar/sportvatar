import "Sportvatar"
import "Sportbit"
import "SportvatarPack"
import "NonFungibleToken"

// Admin: creates `packCount` Packs; components are taken in order, (compPerPack + flameCount) per pack.
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

    let sportbitCollection: auth(NonFungibleToken.Withdraw) &Sportbit.Collection
    let packCollection: &SportvatarPack.Collection
    let sportvatarAdmin: &Sportvatar.Admin

    prepare(account: auth(BorrowValue) &Account) {
        self.sportbitCollection = account.storage.borrow<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>(from: Sportbit.CollectionStoragePath)
            ?? panic("Missing Sportbit collection")
        self.packCollection = account.storage.borrow<&SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)
            ?? panic("Missing Pack collection")
        self.sportvatarAdmin = account.storage.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)
            ?? panic("Missing Sportvatar admin")
    }

    execute {
        let perPack = compPerPack + flameCount
        var i: UInt32 = 0
        while i < packCount {
            let nfts: @[Sportbit.NFT] <- []
            var j: UInt32 = 0
            while j < perPack {
                nfts.append(<- (self.sportbitCollection.withdraw(withdrawID: components[Int(i * perPack + j)]) as! @Sportbit.NFT))
                j = j + 1
            }
            let pack <- self.sportvatarAdmin.createPack(
                components: <-nfts,
                randomString: randomString[Int(i)],
                price: price,
                flameCount: flameCount,
                series: series,
                name: name
            )
            self.packCollection.deposit(token: <-pack)
            i = i + 1
        }
    }
}
