// Generated from resources/js/flow/sportvatar-sale-create.tx.js (mainnet addresses). Keep in sync with the website tx.
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
access(all) fun getOrIssueProvider(account: auth(Storage, Capabilities) &Account): Capability<auth(NonFungibleToken.Withdraw) &Sportvatar.Collection> {
    let wanted = Type<auth(NonFungibleToken.Withdraw) &Sportvatar.Collection>()
    for controller in account.capabilities.storage.getControllers(forPath: Sportvatar.CollectionStoragePath) {
        if controller.tag == "sportvatar-storefront-provider" && controller.borrowType == wanted {
            return controller.capability as! Capability<auth(NonFungibleToken.Withdraw) &Sportvatar.Collection>
        }
    }
    let cap = account.capabilities.storage.issue<auth(NonFungibleToken.Withdraw) &Sportvatar.Collection>(Sportvatar.CollectionStoragePath)
    account.capabilities.storage.getController(byCapabilityID: cap.id)!.setTag("sportvatar-storefront-provider")
    return cap
}

// Lists a Sportvatar for sale on NFTStorefrontV2, paid in DUC (95% seller, 5% marketplace).
transaction(saleItemID: UInt64, saleItemPrice: UFix64, customID: String?, expiry: UInt64) {
    let ducReceiver: Capability<&{FungibleToken.Receiver}>
    let ducReceiverMarket: Capability<&{FungibleToken.Receiver}>
    let nftProvider: Capability<auth(NonFungibleToken.Withdraw) &Sportvatar.Collection>
    let storefront: auth(NFTStorefrontV2.CreateListing) &NFTStorefrontV2.Storefront
    var saleCuts: [NFTStorefrontV2.SaleCut]

    prepare(account: auth(Storage, Capabilities) &Account) {
        self.saleCuts = []

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

        // Make sure the item is in the seller's collection
        assert(Sportvatar.getSportvatar(address: account.address, sportvatarId: saleItemID) != nil, message: "Sportvatar not found in the seller's collection")

        self.nftProvider = getOrIssueProvider(account: account)
        assert(self.nftProvider.check(), message: "Missing or mis-typed Sportvatar.Collection provider")

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
