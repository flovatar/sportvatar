// Generated from resources/js/flow/component-sale-buy.tx.js (mainnet addresses). Keep in sync with the website tx.
import FungibleToken from 0xf233dcee88fe0abe
import NonFungibleToken from 0x1d7e57aa55817448
import DapperUtilityCoin from 0xead892083b3e2c6c
import NFTStorefrontV2 from 0x4eb8a10cb9f87357
import Sportvatar, Sportbit from 0xca5c31c0c03e11be

// Buys a Sportbit listed on NFTStorefrontV2 with DUC (Dapper co-signs as the payer).
transaction(storefrontAddress: Address, listingResourceID: UInt64, expectedPrice: UFix64, commissionRecipient: Address) {
    let paymentVault: @{FungibleToken.Vault}
    let buyerCollection: &Sportbit.Collection
    let listing: &{NFTStorefrontV2.ListingPublic}
    let balanceBeforeTransfer: UFix64
    let mainDucVault: auth(FungibleToken.Withdraw) &DapperUtilityCoin.Vault
    let commissionRecipientCap: Capability<&{FungibleToken.Receiver}>

    prepare(dapper: auth(BorrowValue) &Account, buyer: auth(Storage, Capabilities) &Account) {

        // Initialize the buyer's account if it is not already initialized
        if buyer.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath) == nil {
            buyer.storage.save<@{NonFungibleToken.Collection}>(<- Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>()), to: Sportbit.CollectionStoragePath)
        }
        if !buyer.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath).check() {
            buyer.capabilities.unpublish(Sportbit.CollectionPublicPath)
            buyer.capabilities.publish(
                buyer.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath),
                at: Sportbit.CollectionPublicPath
            )
        }

        // Fetch the storefront where the listing exists
        let storefront = getAccount(storefrontAddress)
            .capabilities.borrow<&{NFTStorefrontV2.StorefrontPublic}>(NFTStorefrontV2.StorefrontPublicPath)
            ?? panic("Could not borrow Storefront from provided address")

        // Fetch the listing from the storefront by ID
        self.listing = storefront.borrowListing(listingResourceID: listingResourceID)
            ?? panic("No Offer with that ID in Storefront")
        let details = self.listing.getDetails()
        assert(details.nftType == Type<@Sportbit.NFT>(), message: "Listing is not a Sportbit")
        assert(details.salePaymentVaultType == Type<@DapperUtilityCoin.Vault>(), message: "Listing is not paid in DUC")

        // Check that the price is what we expect
        let salePrice = details.salePrice
        if expectedPrice != salePrice {
            panic("Expected price does not match sale price")
        }

        // Get access to Dapper's DUC vault and withdraw the sale price
        self.mainDucVault = dapper.storage.borrow<auth(FungibleToken.Withdraw) &DapperUtilityCoin.Vault>(from: /storage/dapperUtilityCoinVault)
            ?? panic("Cannot borrow DapperUtilityCoin vault from dapper storage")
        self.balanceBeforeTransfer = self.mainDucVault.balance
        self.paymentVault <- self.mainDucVault.withdraw(amount: salePrice)

        self.buyerCollection = buyer.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)
            ?? panic("Cannot borrow Sportbit collection from buyer")

        // Access the capability to receive the commission.
        self.commissionRecipientCap = getAccount(commissionRecipient).capabilities.get<&{FungibleToken.Receiver}>(/public/dapperUtilityCoinReceiver)
        assert(self.commissionRecipientCap.check(), message: "Commission Recipient doesn't have DUC receiving capability")
    }

    execute {
        let item <- self.listing.purchase(
            payment: <-self.paymentVault,
            commissionRecipient: self.commissionRecipientCap
        )

        self.buyerCollection.deposit(token: <-item)
    }

    post {
        // Ensure there is no DUC leakage
        self.mainDucVault.balance == self.balanceBeforeTransfer: "transaction would leak DUC"
    }
}
