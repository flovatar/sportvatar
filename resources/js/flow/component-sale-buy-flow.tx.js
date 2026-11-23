import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

// Flow Wallet variant of component-sale-buy.tx.js: buys a Sportbit listed on NFTStorefrontV2 in FLOW, paid from the
// buyer's own FlowToken vault (single signer, no Dapper co-signature). Refuses DUC listings.
export async function componentSaleBuyFlowTx(storefrontAddress, listingResourceID, expectedPrice) {
    return await fcl
        .mutate({
            cadence: `
import FungibleToken from 0xFungible
import NonFungibleToken from 0xNonFungible
import FlowToken from 0xFlowToken
import NFTStorefrontV2 from 0xStorefront2
import Sportvatar, Sportbit from 0xSportvatar

// Buys a Sportbit listed on NFTStorefrontV2 with FLOW.
transaction(storefrontAddress: Address, listingResourceID: UInt64, expectedPrice: UFix64) {
    let paymentVault: @{FungibleToken.Vault}
    let buyerCollection: &Sportbit.Collection
    let listing: &{NFTStorefrontV2.ListingPublic}
    let commissionRecipientCap: Capability<&{FungibleToken.Receiver}>

    prepare(buyer: auth(Storage, Capabilities) &Account) {

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
        assert(details.salePaymentVaultType == Type<@FlowToken.Vault>(), message: "Listing is not paid in FLOW")
        assert(!details.purchased, message: "Listing already purchased")

        // Check that the price is what we expect
        let salePrice = details.salePrice
        if expectedPrice != salePrice {
            panic("Expected price does not match sale price")
        }

        // Pay from the buyer's FlowToken vault
        let mainFlowVault = buyer.storage.borrow<auth(FungibleToken.Withdraw) &FlowToken.Vault>(from: /storage/flowTokenVault)
            ?? panic("Cannot borrow the buyer's FlowToken vault")
        self.paymentVault <- mainFlowVault.withdraw(amount: salePrice)

        self.buyerCollection = buyer.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)
            ?? panic("Cannot borrow Sportbit collection from buyer")

        // Commission (only used by listings that set one; ours use 0.0) goes to Sportvatar's FLOW receiver
        self.commissionRecipientCap = getAccount(0xSportvatar).capabilities.get<&{FungibleToken.Receiver}>(/public/flowTokenReceiver)
        assert(self.commissionRecipientCap.check(), message: "Commission recipient has no FLOW receiver")
    }

    execute {
        let item <- self.listing.purchase(
            payment: <-self.paymentVault,
            commissionRecipient: self.commissionRecipientCap
        )

        self.buyerCollection.deposit(token: <-item)
    }
}
`,
            args: (arg, t) => [
                arg(storefrontAddress, t.Address),
                arg(''+listingResourceID, t.UInt64),
                arg(floatArg(expectedPrice), t.UFix64),
            ],
            limit: 9999
        });

}
