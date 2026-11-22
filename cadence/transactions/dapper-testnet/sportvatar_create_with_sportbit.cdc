// Generated from resources/js/flow/sportvatar-create-with-sportbit.tx.js (testnet addresses). Keep in sync with the website tx.
import Sportvatar, Sportbit from 0x4a9e28acee147fd0
import NonFungibleToken from 0x631e88ae7f1d7c20

// This transaction will create a new Sportvatar by burning a Flame with the necessary Rarity
// and equips the given Sportbits (one per accessory layer) at mint time.
transaction(
    series: UInt64,
    layers: [UInt32],
    templates: [UInt64?],
    flame: UInt64,
    sportbits: [UInt64]
    ) {

    let sportvatarCollection: &Sportvatar.Collection
    let flameNFT: @Sportbit.NFT
    let sportbitNFTs: @[Sportbit.NFT?]
    let accountAddress: Address

    prepare(account: auth(Storage, Capabilities) &Account) {
        if account.storage.borrow<&Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath) == nil {
            account.storage.save<@{NonFungibleToken.Collection}>(<- Sportvatar.createEmptyCollection(nftType: Type<@Sportvatar.Collection>()), to: Sportvatar.CollectionStoragePath)
        }
        if !account.capabilities.get<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath).check() {
            account.capabilities.unpublish(Sportvatar.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&Sportvatar.Collection>(Sportvatar.CollectionStoragePath),
                at: Sportvatar.CollectionPublicPath
            )
        }
        if account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath) == nil {
            account.storage.save<@{NonFungibleToken.Collection}>(<- Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>()), to: Sportbit.CollectionStoragePath)
        }
        if !account.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath).check() {
            account.capabilities.unpublish(Sportbit.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath),
                at: Sportbit.CollectionPublicPath
            )
        }

        self.sportvatarCollection = account.storage.borrow<&Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath)!
        let sportbitCollection = account.storage.borrow<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!

        self.flameNFT <- sportbitCollection.withdraw(withdrawID: flame) as! @Sportbit.NFT

        let sportbitNFTs: @[Sportbit.NFT?] <- []
        for sportbitId in sportbits {
            sportbitNFTs.append(<- (sportbitCollection.withdraw(withdrawID: sportbitId) as! @Sportbit.NFT))
        }
        self.sportbitNFTs <- sportbitNFTs
        self.accountAddress = account.address
    }

    execute {
        let sportvatar <- Sportvatar.createSportvatar(
            sportflame: <- self.flameNFT,
            series: series,
            layers: layers,
            templateIds: templates,
            sportbits: <- self.sportbitNFTs,
            address: self.accountAddress
        )

        self.sportvatarCollection.deposit(token: <-sportvatar)
    }
}
