import DapperUtilityCoin from 0x82ec283f88a62e65

access(all) struct PurchaseData {
    access(all) let id: UInt64
    access(all) let name: String
    access(all) let amount: UFix64
    access(all) let description: String
    access(all) let imageURL: String
    access(all) let paymentVaultTypeID: Type

    init(id: UInt64, name: String, amount: UFix64, description: String, imageURL: String, paymentVaultTypeID: Type) {
        self.id = id
        self.name = name
        self.amount = amount
        self.description = description
        self.imageURL = imageURL
        self.paymentVaultTypeID = paymentVaultTypeID
    }
}

access(all) fun main(storefrontAddress: Address, listingResourceID: UInt64, expectedPrice: UFix64, signature: String, sportvatarAddress: Address): PurchaseData {

    var packName: String = "common"
    if(expectedPrice == UFix64(24.99)){
        packName = "rare"
    } else if(expectedPrice == UFix64(69.99)){
       packName = "epic"
    } else if(expectedPrice == UFix64(199.99)){
      packName = "legendary"
    }
    let purchaseData = PurchaseData(
        id: listingResourceID,
        name: "Sportvatar Pack",
        amount: expectedPrice,
        description: "An awesome Sportvatar Pack filled with waiting to be opened and containing awesome Sportvatar NFTs",
        imageURL: "https://sportvatar.com/images/packs/pack".concat(packName).concat(".svg"),
        paymentVaultTypeID: Type<@DapperUtilityCoin.Vault>()
    )

    return purchaseData
}
