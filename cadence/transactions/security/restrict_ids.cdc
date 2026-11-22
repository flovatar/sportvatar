import "SportvatarTemplate"

// Restricts NFT ids of one type. nftType is the type identifier, e.g. "A.<contract address without 0x>.FlovatarComponent.NFT".
// Max 500 ids per transaction (the kit sends batches).
transaction(nftType: String, ids: [UInt64], reason: String) {
    let admin: auth(SportvatarTemplate.RestrictionsManage) &SportvatarTemplate.RestrictionsRegistry
    let t: Type
    prepare(signer: auth(Storage) &Account) {
        SportvatarTemplate.setupRestrictionsRegistry(account: signer)   // idempotent; only the contract account can do this
        self.admin = signer.storage.borrow<auth(SportvatarTemplate.RestrictionsManage) &SportvatarTemplate.RestrictionsRegistry>(from: SportvatarTemplate.getRestrictionsRegistryStoragePath())
            ?? panic("signer has no SportvatarTemplate.RestrictionsRegistry")
        self.t = CompositeType(nftType) ?? panic("unknown type ".concat(nftType))
        assert(self.t.address == signer.address, message: "type must belong to the signer's contracts")
    }
    execute {
        self.admin.restrictIDs(nftType: self.t, ids: ids, reason: reason)
    }
    post {
        SportvatarTemplate.isIDRestricted(nftType: self.t, id: ids[0]) && SportvatarTemplate.isIDRestricted(nftType: self.t, id: ids[ids.length - 1]): "ids not restricted"
    }
}
