// Generated from resources/js/flow/component-sale-create.tx.js (mainnet addresses). Keep in sync with the website tx.
import FungibleToken from 0xf233dcee88fe0abe
import NonFungibleToken from 0x1d7e57aa55817448
import DapperUtilityCoin from 0xead892083b3e2c6c
import NFTStorefrontV2 from 0x4eb8a10cb9f87357
import Sportvatar, Sportbit from 0xca5c31c0c03e11be

access(all) fun getOrCreateStorefront(account: auth(Storage, Capabilities) &Account): auth(NFTStorefrontV2.CreateListing) &NFTStorefrontV2.Storefront {
    if account.storage.borrow<&NFTStorefrontV2.Storefront>(from: NFTStorefrontV2.StorefrontStoragePath) == nil {
        account.storage.save(<- NFTStorefrontV2.createStorefront(), to: NFTStorefrontV2.StorefrontStoragePath)
        account.capabilities.unpublish(NFTStorefrontV2.StorefrontPublicPath)
        account.capabilities.publish(
            account.capabilities.storage.issue<&{NFTStorefrontV2.StorefrontPublic}>(NFTStorefrontV2.StorefrontStoragePath),
            at: NFTStorefrontV2.StorefrontPublicPath
        )
    }
    return account.storage.borrow<auth(NFTStorefrontV2.CreateListing) &NFTStorefrontV2.Storefront>(from: NFTStorefrontV2.StorefrontStoragePath)
        ?? panic("Could not borrow the Storefront")
}

// Returns the signer's withdraw capability used by Storefront listings. One controller (tagged)
// is reused for every listing instead of issuing a new Withdraw capability per listing.
access(all) fun getOrIssueProvider(account: auth(Storage, Capabilities) &Account): Capability<auth(NonFungibleToken.Withdraw) &Sportbit.Collection> {
    let wanted = Type<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>()
    for controller in account.capabilities.storage.getControllers(forPath: Sportbit.CollectionStoragePath) {
        if controller.tag == "sportbit-storefront-provider" && controller.borrowType == wanted {
            return controller.capability as! Capability<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>
        }
    }
    let cap = account.capabilities.storage.issue<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>(Sportbit.CollectionStoragePath)
    account.capabilities.storage.getController(byCapabilityID: cap.id)!.setTag("sportbit-storefront-provider")
    return cap
}

// Lists a Sportbit for sale on NFTStorefrontV2, paid in DUC (95% seller, 5% marketplace).
transaction(saleItemID: UInt64, saleItemPrice: UFix64, customID: String?, expiry: UInt64) {
    let ducReceiver: Capability<&{FungibleToken.Receiver}>
    let ducReceiverMarket: Capability<&{FungibleToken.Receiver}>
    let nftProvider: Capability<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>
    let storefront: auth(NFTStorefrontV2.CreateListing) &NFTStorefrontV2.Storefront
    var saleCuts: [NFTStorefrontV2.SaleCut]

    prepare(account: auth(Storage, Capabilities) &Account) {
        self.saleCuts = []

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

        // Make sure the item is in the seller's collection
        assert(account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!.borrowNFT(saleItemID) != nil, message: "Sportbit not found in the seller's collection")

        self.nftProvider = getOrIssueProvider(account: account)
        assert(self.nftProvider.check(), message: "Missing or mis-typed Sportbit.Collection provider")

        self.ducReceiver = account.capabilities.get<&{FungibleToken.Receiver}>(/public/dapperUtilityCoinReceiver)
        assert(self.ducReceiver.borrow() != nil, message: "Missing or mis-typed DUC receiver")

        self.ducReceiverMarket = getAccount(0xefd63e77395b6d05).capabilities.get<&{FungibleToken.Receiver}>(/public/dapperUtilityCoinReceiver)
        assert(self.ducReceiverMarket.borrow() != nil, message: "Missing or mis-typed DUC receiver")

        self.storefront = getOrCreateStorefront(account: account)

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
            nftProviderCapability: self.nftProvider,
            nftType: Type<@Sportbit.NFT>(),
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
