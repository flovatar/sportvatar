import Sportbit, SportvatarPack from 0xca5c31c0c03e11be
import NonFungibleToken from 0x1d7e57aa55817448
import FungibleToken from 0xf233dcee88fe0abe
import MetadataViews from 0x1d7e57aa55817448

//this transaction redeems a Pack and opens it
transaction(saleAddress: Address, tokenId: UInt64, signature: String) {

    let collectionCap: Capability<&SportvatarPack.Collection>
    let sportvatarPackCollection: &SportvatarPack.Collection

    prepare(account: auth(Storage, Capabilities) &Account) {

        let sportvatarPackCap = account.capabilities.get<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath)
        if(!sportvatarPackCap.check()) {
            account.storage.save<@SportvatarPack.Collection>(<- SportvatarPack.createEmptyCollection(), to: SportvatarPack.CollectionStoragePath)
            account.capabilities.unpublish(SportvatarPack.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&SportvatarPack.Collection>(SportvatarPack.CollectionStoragePath),
                at: SportvatarPack.CollectionPublicPath
            )
        }
        self.sportvatarPackCollection = account.storage.borrow<&SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)!

        self.collectionCap = sportvatarPackCap


        if account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath) == nil {
            account.storage.save<@{NonFungibleToken.Collection}>(<- Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>()), to: Sportbit.CollectionStoragePath)
        }
        let sportbitCap = account.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath)
        if(!sportbitCap.check()) {
            account.capabilities.unpublish(Sportbit.CollectionPublicPath)
            account.capabilities.publish(
                account.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath),
                at: Sportbit.CollectionPublicPath
            )
        }

    }

    execute {
        // get the read-only account storage of the seller
        let seller = getAccount(saleAddress)

        let packmarket = seller.capabilities.get<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath).borrow()
                         ?? panic("Could not borrow seller's sale reference")

        packmarket.claimForFree(tokenId: tokenId, recipientCap: self.collectionCap, signature: signature)

        self.sportvatarPackCollection.openPack(id: tokenId)
    }

}
