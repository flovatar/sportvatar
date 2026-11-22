import Sportbit, SportvatarPack from 0xacb1ef5cc68a95b8
import NonFungibleToken from 0x631e88ae7f1d7c20
import FungibleToken from 0x9a0766d93b6608b7
import MetadataViews from 0x631e88ae7f1d7c20

//this transaction redeems a Pack and opens it
transaction(saleAddress: Address, tokenId: UInt64, signature: String) {

    let collectionCap: Capability<&{SportvatarPack.CollectionPublic}>
    let sportvatarPackCollection: &SportvatarPack.Collection

    prepare(account: auth(Storage, Capabilities) &Account) {

        let sportvatarPackCap = account.capabilities.get<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath)
        if(!sportvatarPackCap.check()) {
            account.storage.save<@SportvatarPack.Collection>(<- SportvatarPack.createEmptyCollection(nftType: Type<@SportvatarPack.Collection>()), to: SportvatarPack.CollectionStoragePath)
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

        let packmarket = seller.capabilities.borrow<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath)
                         ?? panic("Could not borrow seller's sale reference")

        packmarket.claimForFree(tokenId: tokenId, recipientCap: self.collectionCap, signature: signature)

        self.sportvatarPackCollection.openPack(id: tokenId)
    }

}
