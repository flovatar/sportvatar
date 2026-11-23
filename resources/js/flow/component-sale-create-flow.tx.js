import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

// Flow Wallet variant of component-sale-create.tx.js: NFTStorefrontV2 listing paid in FLOW (FlowToken.Vault).
// Same split as the Dapper (DUC) listing: 95% to the seller, 5% to Sportvatar (its FlowToken receiver, which is
// also the royalty receiver in the NFT's MetadataViews.Royalties). Never use this with a Dapper account and never
// create DUC listings from a Flow Wallet: the DUC receiver of a non-Dapper account forwards to Dapper.
export async function componentSaleCreateFlowTx(saleItemID, saleItemPrice, customID, expiry) {
    return await fcl
        .mutate({
            cadence: `
import FungibleToken from 0xFungible
import NonFungibleToken from 0xNonFungible
import FlowToken from 0xFlowToken
import NFTStorefrontV2 from 0xStorefront2
import Sportvatar, Sportbit from 0xSportvatar

access(all) fun getOrCreateStorefront(account: auth(Storage, Capabilities) &Account): auth(NFTStorefrontV2.CreateListing) &NFTStorefrontV2.Storefront {
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
    return account.storage.borrow<auth(NFTStorefrontV2.CreateListing) &NFTStorefrontV2.Storefront>(from: NFTStorefrontV2.StorefrontStoragePath)
        ?? panic("Could not borrow the Storefront")
}

// Returns the signer's withdraw capability used by Storefront listings. One controller (tagged)
// is reused for every listing instead of issuing a new Withdraw capability per listing.
access(all) fun getOrIssueProvider(account: auth(Storage, Capabilities) &Account): Capability<auth(NonFungibleToken.Withdraw) &Sportbit.Collection> {
    let wanted = Type<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>()
    for controller in account.capabilities.storage.getControllers(forPath: Sportbit.CollectionStoragePath) {
        if controller.tag == "sportbit-storefront-provider" && controller.borrowType == wanted {
            return controller.capability as! Capability<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>
        }
    }
    let cap = account.capabilities.storage.issue<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>(Sportbit.CollectionStoragePath)
    account.capabilities.storage.getController(byCapabilityID: cap.id)!.setTag("sportbit-storefront-provider")
    return cap
}

// A FlowToken receiver that really deposits into a FlowToken.Vault (not a forwarder)
access(all) fun flowReceiver(_ address: Address): Capability<&{FungibleToken.Receiver}> {
    let cap = getAccount(address).capabilities.get<&{FungibleToken.Receiver}>(/public/flowTokenReceiver)
    let receiver = cap.borrow() ?? panic("Missing FLOW receiver")
    assert(receiver.isInstance(Type<@FlowToken.Vault>()), message: "The FLOW receiver is not a FlowToken vault")
    return cap
}

// Lists a Sportbit for sale on NFTStorefrontV2, paid in FLOW (95% seller, 5% Sportvatar).
transaction(saleItemID: UInt64, saleItemPrice: UFix64, customID: String?, expiry: UInt64) {
    let nftProvider: Capability<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>
    let storefront: auth(NFTStorefrontV2.CreateListing) &NFTStorefrontV2.Storefront
    var saleCuts: [NFTStorefrontV2.SaleCut]

    prepare(account: auth(Storage, Capabilities) &Account) {
        self.saleCuts = []

        // Make sure the item is in the seller's collection
        assert(account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!.borrowNFT(saleItemID) != nil, message: "Sportbit not found in the seller's collection")

        self.nftProvider = getOrIssueProvider(account: account)
        assert(self.nftProvider.check(), message: "Missing or mis-typed Sportbit.Collection provider")

        self.storefront = getOrCreateStorefront(account: account)

        self.saleCuts.append(NFTStorefrontV2.SaleCut(
            receiver: flowReceiver(account.address),
            amount: saleItemPrice * 0.95
        ))
        self.saleCuts.append(NFTStorefrontV2.SaleCut(
            receiver: flowReceiver(0xSportvatar),
            amount: saleItemPrice * 0.05
        ))
    }

    execute {
        self.storefront.createListing(
            nftProviderCapability: self.nftProvider,
            nftType: Type<@Sportbit.NFT>(),
            nftID: saleItemID,
            salePaymentVaultType: Type<@FlowToken.Vault>(),
            saleCuts: self.saleCuts,
            marketplacesCapability: nil,
            customID: customID,
            commissionAmount: 0.0,
            expiry: expiry
        )
    }
}
`,
            args: (arg, t) => [
                arg(''+saleItemID, t.UInt64),
                arg(floatArg(saleItemPrice), t.UFix64),
                arg(customID, t.Optional(t.String)),
                arg(''+expiry, t.UInt64)
            ],
            limit: 9999
        });

}
