import FungibleToken from 0x9a0766d93b6608b7
import NonFungibleToken from 0x631e88ae7f1d7c20
import DapperUtilityCoin from 0x82ec283f88a62e65
import NFTStorefrontV2 from 0x2d55b98eb200daef
import MetadataViews from 0x631e88ae7f1d7c20
import Sportvatar, Sportbit from 0xacb1ef5cc68a95b8

transaction(storefrontAddress: Address, listingResourceID: UInt64, expectedPrice: UFix64, commissionRecipient: Address) {
    let paymentVault: @FungibleToken.Vault
    let buyerSportvatarCollection: &Sportvatar.Collection{NonFungibleToken.Receiver}
    let storefront: &NFTStorefrontV2.Storefront{NFTStorefrontV2.StorefrontPublic}
    let listing: &NFTStorefrontV2.Listing{NFTStorefrontV2.ListingPublic}
    let balanceBeforeTransfer: UFix64
    let mainDucVault: &DapperUtilityCoin.Vault
    let commissionRecipientCap: Capability<&{FungibleToken.Receiver}>

    prepare(dapper: auth(Storage, Capabilities) &Account, buyer: auth(Storage, Capabilities) &Account) {

        // Initialize the buyer's account if it is not already initialized
        let sportvatarCap = buyer.capabilities.get<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath)
        if(!sportvatarCap.check()) {
            buyer.storage.save<@{NonFungibleToken.Collection}>(<- Sportvatar.createEmptyCollection(nftType: Type<@Sportvatar.Collection>()), to: Sportvatar.CollectionStoragePath)
            buyer.capabilities.unpublish(Sportvatar.CollectionPublicPath)
            buyer.capabilities.publish(
                buyer.capabilities.storage.issue<&Sportvatar.Collection>(Sportvatar.CollectionStoragePath),
                at: Sportvatar.CollectionPublicPath
            )
        }

        // Fetch the storefront where the listing exists
        self.storefront = getAccount(storefrontAddress)
              .capabilities.get<&NFTStorefrontV2.Storefront>(
                  NFTStorefrontV2.StorefrontPublicPath
              )!
              .borrow()
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

        self.buyerSportvatarCollection = buyer
            .capabilities.get<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath)
            .borrow()
            ?? panic("Cannot borrow Sportvatar collection receiver from buyer")

        // Access the capability to receive the commission.
        self.commissionRecipientCap = getAccount(commissionRecipient).capabilities.get<&{FungibleToken.Receiver}>(/public/dapperUtilityCoinReceiver)
        assert(self.commissionRecipientCap.check(), message: "Commission Recipient doesn't have DUC receiving capability")
    }

    execute {
        let item <- self.listing.purchase(
            payment: <-self.paymentVault,
            commissionRecipient: self.commissionRecipientCap
        )

        self.buyerSportvatarCollection.deposit(token: <-item)
    }

    post {
        // Ensure there is no DUC leakage
        self.mainDucVault.balance == self.balanceBeforeTransfer: "transaction would leak DUC"
    }
}
