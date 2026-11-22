import SportvatarPack from 0xca5c31c0c03e11be

//this transaction redeems a Pack
transaction(saleAddress: Address, tokenId: UInt64, signature: String) {

    let collectionCap: Capability<&SportvatarPack.Collection>

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

        self.collectionCap = sportvatarPackCap

    }

    execute {
        // get the read-only account storage of the seller
        let seller = getAccount(saleAddress)

        let packmarket = seller.capabilities.get<&SportvatarPack.Collection>(SportvatarPack.CollectionPublicPath).borrow()
                         ?? panic("Could not borrow seller's sale reference")

        packmarket.claimForFree(tokenId: tokenId, recipientCap: self.collectionCap, signature: signature)
    }

}
