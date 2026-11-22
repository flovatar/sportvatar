import "SportvatarTemplate"

// Removes an address restriction (use to fix a mistake).
transaction(address: Address) {
    let admin: auth(SportvatarTemplate.RestrictionsManage) &SportvatarTemplate.RestrictionsRegistry
    prepare(signer: auth(Storage) &Account) {
        SportvatarTemplate.setupRestrictionsRegistry(account: signer)   // idempotent; only the contract account can do this
        self.admin = signer.storage.borrow<auth(SportvatarTemplate.RestrictionsManage) &SportvatarTemplate.RestrictionsRegistry>(from: SportvatarTemplate.getRestrictionsRegistryStoragePath())
            ?? panic("signer has no SportvatarTemplate.RestrictionsRegistry")
    }
    execute {
        self.admin.unrestrictAddress(address)
    }
    post {
        !SportvatarTemplate.isAddressRestricted(address): "address still restricted"
    }
}
