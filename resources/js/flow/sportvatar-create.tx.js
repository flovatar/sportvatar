import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function sportvatarCreateTx(series, layers, templates, flame) {

    return await fcl
        .mutate({
            cadence: `
import Sportvatar, Sportbit from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import MetadataViews from 0xMetadataViews

// This transaction will create a new Sportvatar by burning a Flame with the necessary Rarity
transaction(
    series: UInt64,
    layers: [UInt32],
    templates: [UInt64?],
    flame: UInt64
    ) {


    let sportvatarCollection: &Sportvatar.Collection
    let sportbitCollection: &Sportbit.Collection

    let flameNFT: @Sportbit.NFT
    let accountAddress: Address

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
`,
            args: (arg, t) => [
                arg(''+series, t.UInt64),
                arg(layers, t.Array(t.UInt32)),
                arg(templates, t.Array(t.Optional(t.UInt64))),
                arg(''+flame, t.UInt64)
            ],
            limit: 9999
        });

}
