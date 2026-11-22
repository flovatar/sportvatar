import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function packRedeemTx(saleAddress, tokenId, signature) {
    return await fcl
        .mutate({
            cadence: `
import SportvatarPack from 0xSportvatar

//this transaction redeems a Pack
transaction(saleAddress: Address, tokenId: UInt64, signature: String) {

    let collectionCap: Capability<&SportvatarPack.CollectionPublic>

    prepare(account: auth(Storage, Capabilities) &Account) {

        let sportvatarPackCap = account.capabilities.get<&SportvatarPack.CollectionPublic>(SportvatarPack.CollectionPublicPath)
        if(!sportvatarPackCap.check()) {
            account.storage.save<@SportvatarPack.Collection>(<- SportvatarPack.createEmptyCollection(), to: SportvatarPack.CollectionStoragePath)
            account.capabilities.unpublish(SportvatarPack.CollectionPublicPath)
            account.capabilities.publish(account.capabilities.storage.issue<&SportvatarPack.CollectionPublic>(SportvatarPack.CollectionStoragePath), at: SportvatarPack.CollectionPublicPath)
        }

        self.collectionCap = sportvatarPackCap

    }

    execute {
        // get the read-only account storage of the seller
        let seller = getAccount(saleAddress)

        let packmarket = seller.capabilities.borrow<&SportvatarPack.CollectionPublic>(SportvatarPack.CollectionPublicPath)
                         ?? panic("Could not borrow seller's sale reference")

        packmarket.claimForFree(tokenId: tokenId, recipientCap: self.collectionCap, signature: signature)
    }

}
`,
            args: (arg, t) => [
                arg(saleAddress, t.Address),
                arg(''+tokenId, t.UInt64),
                arg(signature, t.String)
            ],
            limit: 9999
        });

}
