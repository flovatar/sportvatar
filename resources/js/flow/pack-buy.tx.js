import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function packBuyTx(storefrontAddress, listingResourceID, expectedPrice, signature, sportvatarAddress) {
    return await fcl
        .mutate({
            cadence: `
import Sportbit, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import MetadataViews from 0xMetadataViews
import DapperUtilityCoin from 0xDuc

transaction(storefrontAddress: Address, listingResourceID: UInt64, expectedPrice: UFix64, signature: String, sportvatarAddress: Address) {
    let paymentVault: @FungibleToken.Vault
    let buyerSportvatarPackCollection: Capability<&{SportvatarPack.CollectionPublic}>
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

        let packmarket = getAccount(sportvatarAddress).capabilities.borrow<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath)
                         ?? panic("Could not borrow seller's sale reference")

        packmarket.purchase(tokenId: listingResourceID, recipientCap: self.buyerSportvatarPackCollection, buyTokens: <- self.paymentVault, signature: signature, expectedPrice: expectedPrice)

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
                arg(signature, t.String),
                arg(sportvatarAddress, t.Address)
            ],
            limit: 9999
        });

}
