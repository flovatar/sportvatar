import "SportvatarTemplate"

// Removes restrictions from NFT ids of one type (use to fix a mistake). Max 500 ids per transaction.
transaction(nftType: String, ids: [UInt64]) {
    let admin: auth(SportvatarTemplate.RestrictionsManage) &SportvatarTemplate.RestrictionsRegistry
    let t: Type
    prepare(signer: auth(Storage) &Account) {
        SportvatarTemplate.setupRestrictionsRegistry(account: signer)   // idempotent; only the contract account can do this
        self.admin = signer.storage.borrow<auth(SportvatarTemplate.RestrictionsManage) &SportvatarTemplate.RestrictionsRegistry>(from: SportvatarTemplate.getRestrictionsRegistryStoragePath())
            ?? panic("signer has no SportvatarTemplate.RestrictionsRegistry")
        self.t = CompositeType(nftType) ?? panic("unknown type ".concat(nftType))
    }
    execute {
        self.admin.unrestrictIDs(nftType: self.t, ids: ids)
    }
    post {
        !SportvatarTemplate.isIDRestricted(nftType: self.t, id: ids[0]): "ids still restricted"
    }
}
