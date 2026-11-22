import Sportvatar, Sportbit from 0xacb1ef5cc68a95b8
import NonFungibleToken from 0x631e88ae7f1d7c20
import FungibleToken from 0x9a0766d93b6608b7
import MetadataViews from 0x631e88ae7f1d7c20

// This transaction will create a new Sportvatar by burning a Flame with the necessary Rarity
transaction(
    series: UInt64,
    layers: [UInt32],
    templates: [UInt64?],
    flame: UInt64,
    sportbits: [UInt64]
    ) {


    let sportvatarCollection: &Sportvatar.Collection
    let sportbitCollection: &Sportbit.Collection

    let flameNFT: @Sportbit.NFT
    let accountAddress: Address
    let sportbits: [UInt64]

    prepare(account: auth(Storage, Capabilities) &Account) {

        if account.storage.borrow<&Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath) == nil {
            account.storage.save<@{NonFungibleToken.Collection}>(<- Sportvatar.createEmptyCollection(nftType: Type<@Sportvatar.Collection>()), to: Sportvatar.CollectionStoragePath)
        }
        let sportvatarCap = account.capabilities.get<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath)
        if(!sportvatarCap.check()) {
            account.capabilities.unpublish(Sportvatar.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&Sportvatar.Collection>(Sportvatar.CollectionStoragePath),
                at: Sportvatar.CollectionPublicPath
            )
        }

        if account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath) == nil {
            account.storage.save<@{NonFungibleToken.Collection}>(<- Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>()), to: Sportbit.CollectionStoragePath)
        }
        let sportbitCap = account.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath)
        if(!sportbitCap.check()) {
            account.capabilities.unpublish(Sportbit.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath),
                at: Sportbit.CollectionPublicPath
            )
        }


        self.sportvatarCollection = account.storage.borrow<&Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath)!
        self.sportbitCollection = account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!

        self.flameNFT <-self.sportbitCollection.withdraw(withdrawID: flame) as! @Sportbit.NFT

        self.accountAddress = account.address
        self.sportbits = sportbits

    }

    execute {

        let sportbitsArr: @[Sportbit.NFT?] = []

        var i: UInt32 = UInt32(0)
        while(i <  UInt32(self.sportbits.length)){
            let sportbitId: UInt32 = self.sportbits[i]!
            sportbitsArr.append(<- self.sportbitCollection.withdraw(withdrawID: sportbitId) as! @Sportbit.NFT)
        }

        let sportvatar <- Sportvatar.createSportvatar(
            sportflame: <- self.flameNFT,
            series: series,
            layers: layers,
            templateIds: templates,
            sportbits: <- sportbitsArr,
            address: self.accountAddress
        )

        self.sportvatarCollection.deposit(token: <-sportvatar)
    }
}
