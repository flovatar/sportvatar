import Sportbit, SportvatarPack from 0xca5c31c0c03e11be
import NonFungibleToken from 0x1d7e57aa55817448
import FungibleToken from 0xf233dcee88fe0abe
import MetadataViews from 0x1d7e57aa55817448
import DapperUtilityCoin from 0xead892083b3e2c6c

transaction(storefrontAddress: Address, listingResourceID: UInt64, expectedPrice: UFix64, signature: String, sportvatarAddress: Address) {
    let paymentVault: @FungibleToken.Vault
    let buyerSportvatarPackCollection: Capability<&SportvatarPack.Collection>
    let balanceBeforeTransfer: UFix64
    let mainDucVault: &DapperUtilityCoin.Vault

    prepare(dapper: auth(Storage, Capabilities) &Account, buyer: auth(Storage, Capabilities) &Account) {

        // Initialize the buyer's account if it is not already initialized
        let sportvatarPackCap = buyer.capabilities.get<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath)
        if(!sportvatarPackCap.check()) {
            buyer.storage.save<@SportvatarPack.Collection>(<- SportvatarPack.createEmptyCollection(), to: SportvatarPack.CollectionStoragePath)
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

        let packmarket = getAccount(sportvatarAddress).capabilities.get<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath).borrow()
                         ?? panic("Could not borrow seller's sale reference")

        packmarket.purchase(tokenId: listingResourceID, recipientCap: self.buyerSportvatarPackCollection, buyTokens: <- self.paymentVault, signature: signature, expectedPrice: expectedPrice)

    }

    post {
        // Ensure there is no DUC leakage
        self.mainDucVault.balance == self.balanceBeforeTransfer: "transaction would leak DUC"
    }
}