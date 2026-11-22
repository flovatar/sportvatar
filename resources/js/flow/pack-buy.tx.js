import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function packBuyTx(storefrontAddress, listingResourceID, expectedPrice, expiry, signature) {
    return await fcl
        .mutate({
            cadence: `
import Sportbit, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import MetadataViews from 0xMetadataViews
import DapperUtilityCoin from 0xDuc

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
`,
            args: (arg, t) => [
                arg(storefrontAddress, t.Address),
                arg(''+listingResourceID, t.UInt64),
                arg(floatArg(expectedPrice), t.UFix64),
                arg(''+expiry, t.UInt64),
                arg(signature, t.String)
            ],
            limit: 9999
        });

}
