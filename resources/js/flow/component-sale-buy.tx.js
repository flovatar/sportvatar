import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function componentSaleBuyTx(storefrontAddress, listingResourceID, expectedPrice, commissionRecipient) {
    return await fcl
        .mutate({
            cadence: `
import FungibleToken from 0xFungible
import NonFungibleToken from 0xNonFungible
import DapperUtilityCoin from 0xDuc
import NFTStorefrontV2 from 0xStorefront2
import MetadataViews from 0xMetadataViews
import Sportvatar, Sportbit from 0xSportvatar

transaction(storefrontAddress: Address, listingResourceID: UInt64, expectedPrice: UFix64, commissionRecipient: Address) {
    let paymentVault: @FungibleToken.Vault
    let buyerSportbitCollection: &Sportbit.Collection
    let storefront: &NFTStorefrontV2.Storefront
    let listing: &NFTStorefrontV2.Listing
    let balanceBeforeTransfer: UFix64
    let mainDucVault: &DapperUtilityCoin.Vault
    let commissionRecipientCap: Capability<&{FungibleToken.Receiver}>

    prepare(dapper: auth(Storage, Capabilities) &Account, buyer: auth(Storage, Capabilities) &Account) {

        // Initialize the buyer's account if it is not already initialized
        let sportbitCap = buyer.capabilities.get<&Sportbit.CollectionPublic>(Sportbit.CollectionPublicPath)
        if(!sportbitCap.check()) {
            buyer.storage.save<@{NonFungibleToken.Collection}>(<- Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>()), to: Sportbit.CollectionStoragePath)
            buyer.capabilities.unpublish(Sportbit.CollectionPublicPath)
            buyer.capabilities.publish(buyer.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath), at: Sportbit.CollectionPublicPath)
        }

        // Fetch the storefront where the listing exists
        self.storefront = getAccount(storefrontAddress)
              .capabilities.borrow<&NFTStorefrontV2.Storefront>(
                  NFTStorefrontV2.StorefrontPublicPath
              )
              ?? panic("Could not borrow Storefront from provided address")

        // Fetch the listing from the storefront by ID
        self.listing = self.storefront.borrowListing(listingResourceID: listingResourceID)
            ?? panic("No Offer with that ID in Storefront")

        // Get access to Dapper's DUC vault
        let salePrice = self.listing.getDetails().salePrice
        self.mainDucVault = dapper.storage.borrow<&DapperUtilityCoin.Vault>(from: /storage/dapperUtilityCoinVault)
            ?? panic("Cannot borrow DapperUtilityCoin vault from dapper storage")

        // Withdraw the appropriate amount of DUC from the vault
        self.balanceBeforeTransfer = self.mainDucVault.balance
        self.paymentVault <- self.mainDucVault.withdraw(amount: salePrice)

        // Check that the price is what we expect
        if (expectedPrice != salePrice) {
            panic("Expected price does not match sale price")
        }

        self.buyerSportbitCollection = buyer
            .capabilities.borrow<&Sportbit.Collection>(Sportbit.CollectionPublicPath)
            ?? panic("Cannot borrow Sportbit collection receiver from buyer")

        // Access the capability to receive the commission.
        self.commissionRecipientCap = getAccount(commissionRecipient).capabilities.get<&{FungibleToken.Receiver}>(/public/dapperUtilityCoinReceiver)
        assert(self.commissionRecipientCap.check(), message: "Commission Recipient doesn't have DUC receiving capability")
    }

    execute {
        let item <- self.listing.purchase(
            payment: <-self.paymentVault,
            commissionRecipient: self.commissionRecipientCap
        )

        self.buyerSportbitCollection.deposit(token: <-item)
    }

    post {
        // Ensure there is no DUC leakage
        self.mainDucVault.balance == self.balanceBeforeTransfer: "transaction would leak DUC"
    }
}
`,
            args: (arg, t) => [
                arg(storefrontAddress, t.Address),
                arg(''+listingResourceID, t.UInt64),
                arg(floatArg(expectedPrice), t.UFix64),
                arg(commissionRecipient, t.Address),
            ],
            limit: 9999
        });

}
