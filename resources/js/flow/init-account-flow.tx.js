import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

// Flow Wallet (non-Dapper) account setup: Sportvatar, Sportbit and Pack collections + an NFTStorefrontV2 storefront.
// No DapperUtilityCoin receiver is created on purpose: Flow Wallet users trade in FLOW only, and a DUC/FUT receiver
// on a Flow Wallet account forwards to Dapper (funds of a DUC sale would not reach the seller).
// Idempotent: only missing pieces are created; broken public capabilities are re-published.
export async function initAccountFlowTx() {
    return await fcl
        .mutate({
            cadence: `
import NonFungibleToken from 0xNonFungible
import NFTStorefrontV2 from 0xStorefront2
import Sportvatar, Sportbit, SportvatarPack from 0xSportvatar

transaction {

    prepare(account: auth(Storage, Capabilities) &Account) {

        if account.storage.borrow<&Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath) == nil {
            account.storage.save<@{NonFungibleToken.Collection}>(<- Sportvatar.createEmptyCollection(nftType: Type<@Sportvatar.Collection>()), to: Sportvatar.CollectionStoragePath)
        }
        if !account.capabilities.get<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath).check() {
            account.capabilities.unpublish(Sportvatar.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&Sportvatar.Collection>(Sportvatar.CollectionStoragePath),
                at: Sportvatar.CollectionPublicPath
            )
        }

        if account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath) == nil {
            account.storage.save<@{NonFungibleToken.Collection}>(<- Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>()), to: Sportbit.CollectionStoragePath)
        }
        if !account.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath).check() {
            account.capabilities.unpublish(Sportbit.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath),
                at: Sportbit.CollectionPublicPath
            )
        }

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

        if account.storage.borrow<&NFTStorefrontV2.Storefront>(from: NFTStorefrontV2.StorefrontStoragePath) == nil {
            account.storage.save(<- NFTStorefrontV2.createStorefront(), to: NFTStorefrontV2.StorefrontStoragePath)
        }
        if !account.capabilities.get<&{NFTStorefrontV2.StorefrontPublic}>(NFTStorefrontV2.StorefrontPublicPath).check() {
            account.capabilities.unpublish(NFTStorefrontV2.StorefrontPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&{NFTStorefrontV2.StorefrontPublic}>(NFTStorefrontV2.StorefrontStoragePath),
                at: NFTStorefrontV2.StorefrontPublicPath
            )
        }
    }
}
`,
            args: (arg, t) => [
            ],
            limit: 9999
        });

}
