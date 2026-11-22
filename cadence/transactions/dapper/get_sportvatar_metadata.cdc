import NFTStorefrontV2 from 0x4eb8a10cb9f87357
import MetadataViews from 0x1d7e57aa55817448
import Sportvatar, Sportbit from 0xca5c31c0c03e11be

access(all) struct PurchaseData {
    access(all) let id: UInt64
    access(all) let name: String?
    access(all) let amount: UFix64
    access(all) let description: String?
    access(all) let imageURL: String?

    init(id: UInt64, name: String?, amount: UFix64, description: String?, imageURL: String?) {
        self.id = id
        self.name = name
        self.amount = amount
        self.description = description
        self.imageURL = imageURL
    }
}

access(all) fun main(storefrontAddress: Address, listingResourceID: UInt64, expectedPrice: UFix64, commissionRecipient: Address): PurchaseData {


    let account = getAccount(storefrontAddress)
    let marketCollectionRef = account
        .capabilities.get<&NFTStorefrontV2.Storefront>(
            NFTStorefrontV2.StorefrontPublicPath
        )
        .borrow()
        ?? panic("Could not borrow market collection from address")

    let saleItem = marketCollectionRef.borrowListing(listingResourceID: listingResourceID)
        ?? panic("No item with that ID")

    let listingDetails = saleItem.getDetails()!

    let collection = account.capabilities.get<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath)
    .borrow()
    ?? panic("Could not borrow a reference to the collection")

    let nft = collection.borrowSportvatar(id: listingDetails.nftID)
            ?? panic("Could not borrow a reference to the collection")

    if let view = nft.resolveView(Type<MetadataViews.Display>()) {

        let display = view as! MetadataViews.Display

        let purchaseData = PurchaseData(
            id: listingDetails.nftID,
            name: (display.name == "") ? "Sportvatar" : display.name,
            amount: listingDetails.salePrice,
            description: (display.description == "") ? "Sportvatar" : display.description,
            imageURL: display.thumbnail.uri(),
        )

        return purchaseData
    }
     panic("No NFT")
}
