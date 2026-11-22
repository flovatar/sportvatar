import Sportbit, SportvatarPack from 0xacb1ef5cc68a95b8
import NonFungibleToken from 0x631e88ae7f1d7c20
import FungibleToken from 0x9a0766d93b6608b7
import MetadataViews from 0x631e88ae7f1d7c20
import DapperUtilityCoin from 0x82ec283f88a62e65

transaction(storefrontAddress: Address, listingResourceID: UInt64, expectedPrice: UFix64, signature: String, sportvatarAddress: Address) {
    let paymentVault: @FungibleToken.Vault
    let buyerSportvatarPackCollection: Capability<&{SportvatarPack.CollectionPublic}>
    let balanceBeforeTransfer: UFix64
    let mainDucVault: &DapperUtilityCoin.Vault

    prepare(dapper: auth(Storage, Capabilities) &Account, buyer: auth(Storage, Capabilities) &Account) {

        // Initialize the buyer's account if it is not already initialized
        let sportvatarPackCap = buyer.capabilities.get<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath)
        if(!sportvatarPackCap.check()) {
            buyer.storage.save<@SportvatarPack.Collection>(<- SportvatarPack.createEmptyCollection(nftType: Type<@SportvatarPack.Collection>()), to: SportvatarPack.CollectionStoragePath)
            buyer.capabilities.unpublish(SportvatarPack.CollectionPublicPath)
            buyer.capabilities.publish(
                buyer.capabilities.storage.issue<&SportvatarPack.Collection>(SportvatarPack.CollectionStoragePath),
                at: SportvatarPack.CollectionPublicPath
            )
        }

        // Get access to Dapper's DUC vault
        self.mainDucVault = dapper.storage.borrow<&DapperUtilityCoin.Vault>(from: /storage/dapperUtilityCoinVault)
            ?? panic("Cannot borrow DapperUtilityCoin vault from dapper storage")

        // Withdraw the appropriate amount of DUC from the vault
        self.balanceBeforeTransfer = self.mainDucVault.balance
        self.paymentVault <- self.mainDucVault.withdraw(amount: expectedPrice)

        self.buyerSportvatarPackCollection = buyer.capabilities.get<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath)

    }

    execute {

        let packmarket = getAccount(sportvatarAddress).capabilities.borrow<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath)
                         ?? panic("Could not borrow seller's sale reference")

        packmarket.purchase(tokenId: listingResourceID, recipientCap: self.buyerSportvatarPackCollection, buyTokens: <- self.paymentVault, signature: signature, expectedPrice: expectedPrice)

    }

    post {
        // Ensure there is no DUC leakage
        self.mainDucVault.balance == self.balanceBeforeTransfer: "transaction would leak DUC"
    }
}