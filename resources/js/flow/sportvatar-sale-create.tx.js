import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function sportvatarSaleCreateTx(saleItemID, saleItemPrice, customID, expiry) {
    return await fcl
        .mutate({
            cadence: `
import FungibleToken from 0xFungible
import NonFungibleToken from 0xNonFungible
import DapperUtilityCoin from 0xDuc
import NFTStorefrontV2 from 0xStorefront2
import MetadataViews from 0xMetadataViews
import Sportvatar, Sportbit from 0xSportvatar

fun getOrCreateStorefront(account: auth(Storage, Capabilities) &Account): &NFTStorefrontV2.Storefront {
    if let storefrontRef = account.storage.borrow<&NFTStorefrontV2.Storefront>(from: NFTStorefrontV2.StorefrontStoragePath) {
        return storefrontRef
    }

    let storefront <- NFTStorefrontV2.createStorefront() as! @NFTStorefrontV2.Storefront

    let storefrontRef = &storefront as &NFTStorefrontV2.Storefront

    account.storage.save(<-storefront, to: NFTStorefrontV2.StorefrontStoragePath)

    account.capabilities.unpublish(NFTStorefrontV2.StorefrontPublicPath)
    account.capabilities.publish(account.capabilities.storage.issue<&NFTStorefrontV2.Storefront>(NFTStorefrontV2.StorefrontStoragePath), at: NFTStorefrontV2.StorefrontPublicPath)

    return storefrontRef
}

transaction(saleItemID: UInt64, saleItemPrice: UFix64, customID: String?, expiry: UInt64) {
    let ducReceiver: Capability<&{FungibleToken.Receiver}>
    let ducReceiverMarket: Capability<&{FungibleToken.Receiver}>
    let sportvatarNFTProvider: Capability<&Sportvatar.Collection>
    let storefront: &NFTStorefrontV2.Storefront
    var saleCuts: [NFTStorefrontV2.SaleCut]

    prepare(acct: auth(Storage, Capabilities) &Account) {
        self.saleCuts = []

        let sportvatarCollectionProviderPrivatePath = /private/SportvatarCollection
        // Check if the Provider capability exists or not if \`no\` then create a new link for the same.
        if !acct.capabilities.get<&Sportvatar.Collection>(sportvatarCollectionProviderPrivatePath).check() {
            acct.capabilities.unpublish(sportvatarCollectionProviderPrivatePath)
            acct.capabilities.publish(acct.capabilities.storage.issue<&Sportvatar.Collection>(Sportvatar.CollectionStoragePath), at: sportvatarCollectionProviderPrivatePath)
        }

        self.ducReceiver = acct.capabilities.get<&{FungibleToken.Receiver}>(/public/dapperUtilityCoinReceiver)
        assert(self.ducReceiver.borrow() != nil, message: "Missing or mis-typed DUC receiver")

        let marketAccount = getAccount(0xDapperMerchant)
        self.ducReceiverMarket = marketAccount.capabilities.get<&{FungibleToken.Receiver}>(/public/dapperUtilityCoinReceiver)
        assert(self.ducReceiverMarket.borrow() != nil, message: "Missing or mis-typed DUC receiver")

        let sportvatarData = Sportvatar.getSportvatar(address: acct.address, sportvatarId: saleItemID)!
        let creatorAccount = getAccount(sportvatarData.creatorAddress)


        // Initialize the buyer's account if it is not already initialized
        let sportvatarCap = acct.capabilities.get<&Sportvatar.CollectionPublic>(Sportvatar.CollectionPublicPath)
        if(!sportvatarCap.check()) {
            acct.storage.save<@{NonFungibleToken.Collection}>(<- Sportvatar.createEmptyCollection(nftType: Type<@Sportvatar.Collection>()), to: Sportvatar.CollectionStoragePath)
            acct.capabilities.unpublish(Sportvatar.CollectionPublicPath)
            acct.capabilities.publish(acct.capabilities.storage.issue<&Sportvatar.Collection>(Sportvatar.CollectionStoragePath), at: Sportvatar.CollectionPublicPath)
        }

        self.sportvatarNFTProvider = acct.capabilities.get<&Sportvatar.Collection>(sportvatarCollectionProviderPrivatePath)
        assert(self.sportvatarNFTProvider.borrow() != nil, message: "Missing or mis-typed Sportvatar.Collection provider")

        self.storefront = getOrCreateStorefront(account: acct)


        self.saleCuts.append(NFTStorefrontV2.SaleCut(
            receiver: self.ducReceiver,
            amount: saleItemPrice * 0.95
        ))
        self.saleCuts.append(NFTStorefrontV2.SaleCut(
            receiver: self.ducReceiverMarket,
            amount: saleItemPrice * 0.05
        ))
    }

    execute {
        self.storefront.createListing(
            nftProviderCapability: self.sportvatarNFTProvider,
            nftType: Type<@Sportvatar.NFT>(),
            nftID: saleItemID,
            salePaymentVaultType: Type<@DapperUtilityCoin.Vault>(),
            saleCuts: self.saleCuts,
            marketplacesCapability: nil,
            customID: customID,
            commissionAmount: 0.0,
            expiry: expiry
        )
    }
}
`,
            args: (arg, t) => [
                arg(''+saleItemID, t.UInt64),
                arg(floatArg(saleItemPrice), t.UFix64),
                arg(customID, t.String),
                arg(''+expiry, t.UInt64)
            ],
            limit: 9999
        });

}
