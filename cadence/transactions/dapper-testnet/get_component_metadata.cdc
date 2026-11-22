import NFTStorefrontV2 from 0x2d55b98eb200daef
import MetadataViews from 0x631e88ae7f1d7c20
import Sportvatar, Sportbit from 0xacb1ef5cc68a95b8

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

    let collection = account.capabilities.borrow<&Sportbit.Collection>(Sportbit.CollectionPublicPath)
    ?? panic("Could not borrow a reference to the collection")

    let nft = collection.borrowSportvatar(id: listingDetails.nftID)
            ?? panic("Could not borrow a reference to the collection")

    if let view = nft.resolveView(Type<MetadataViews.Display>()) {

        let display = view as! MetadataViews.Display

        let purchaseData = PurchaseData(
            id: listingDetails.nftID,
            name: (display.name == "") ? "Sportvatar Component" : display.name,
            amount: listingDetails.salePrice,
            description: (display.description == "") ? "Sportvatar Component" : display.description,
            imageURL: display.thumbnail.uri(),
        )

        return purchaseData
    }
     panic("No NFT")
}
