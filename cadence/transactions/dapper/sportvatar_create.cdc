// Generated from resources/js/flow/sportvatar-create.tx.js (mainnet addresses). Keep in sync with the website tx.
import Sportvatar, Sportbit from 0xca5c31c0c03e11be
import NonFungibleToken from 0x1d7e57aa55817448

// This transaction will create a new Sportvatar by burning a Flame with the necessary Rarity
transaction(
    series: UInt64,
    layers: [UInt32],
    templates: [UInt64?],
    flame: UInt64
    ) {

    let sportvatarCollection: &Sportvatar.Collection
    let flameNFT: @Sportbit.NFT
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
        self.accountAddress = account.address
    }

    execute {
        let sportvatar <- Sportvatar.createSportvatar(
            sportflame: <- self.flameNFT,
            series: series,
            layers: layers,
            templateIds: templates,
            sportbits: <- [],
            address: self.accountAddress
        )

        self.sportvatarCollection.deposit(token: <-sportvatar)
    }
}
