// Generated from resources/js/flow/pack-buy.tx.js (mainnet addresses). Keep in sync with the website tx.
import Sportbit, SportvatarPack from 0xca5c31c0c03e11be
import NonFungibleToken from 0x1d7e57aa55817448
import FungibleToken from 0xf233dcee88fe0abe
import MetadataViews from 0x1d7e57aa55817448
import DapperUtilityCoin from 0xead892083b3e2c6c

// Buys a paid Sportvatar Pack with DUC (Dapper co-signs as the payer).
// The v2 authorisation (bound to the buyer, with expiry) is checked on-chain by SportvatarPack.purchasePack.
transaction(storefrontAddress: Address, listingResourceID: UInt64, expectedPrice: UFix64, expiry: UInt64, signature: String) {
    let paymentVault: @{FungibleToken.Vault}
    let buyerSportvatarPackCollection: Capability<&{SportvatarPack.CollectionPublic}>
    let balanceBeforeTransfer: UFix64
    let mainDucVault: auth(FungibleToken.Withdraw) &DapperUtilityCoin.Vault

    prepare(dapper: auth(BorrowValue) &Account, account: auth(Storage, Capabilities) &Account) {

        // Make sure the buyer has a Pack collection and a working public capability
        if account.storage.borrow<&SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath) == nil {
            account.storage.save<@SportvatarPack.Collection>(<- SportvatarPack.createEmptyCollection(), to: SportvatarPack.CollectionStoragePath)
        }
        if !account.capabilities.get<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath).check() {
            account.capabilities.unpublish(SportvatarPack.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&SportvatarPack.Collection>(SportvatarPack.CollectionStoragePath),
                at: SportvatarPack.CollectionPublicPath
            )
        }

        // Get access to Dapper's DUC vault
        self.mainDucVault = dapper.storage.borrow<auth(FungibleToken.Withdraw) &DapperUtilityCoin.Vault>(from: /storage/dapperUtilityCoinVault)
            ?? panic("Cannot borrow DapperUtilityCoin vault from dapper storage")

        // Withdraw the appropriate amount of DUC from the vault
        self.balanceBeforeTransfer = self.mainDucVault.balance
        self.paymentVault <- self.mainDucVault.withdraw(amount: expectedPrice)

        self.buyerSportvatarPackCollection = account.capabilities.get<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath)
    }

    execute {
        SportvatarPack.purchasePack(tokenId: listingResourceID, recipientCap: self.buyerSportvatarPackCollection, buyTokens: <- self.paymentVault, expiry: expiry, signature: signature, expectedPrice: expectedPrice)
    }

    post {
        // Ensure there is no DUC leakage
        self.mainDucVault.balance == self.balanceBeforeTransfer: "transaction would leak DUC"
    }
}
