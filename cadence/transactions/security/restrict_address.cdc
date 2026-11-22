import "SportvatarTemplate"

// Restricts an address in the SportvatarTemplate restrictions registry: it can no longer withdraw, sell, claim or receive this project's NFTs / rewards.
transaction(address: Address, reason: String) {
    let admin: auth(SportvatarTemplate.RestrictionsManage) &SportvatarTemplate.RestrictionsRegistry
    prepare(signer: auth(Storage) &Account) {
        SportvatarTemplate.setupRestrictionsRegistry(account: signer)   // idempotent; only the contract account can do this
        self.admin = signer.storage.borrow<auth(SportvatarTemplate.RestrictionsManage) &SportvatarTemplate.RestrictionsRegistry>(from: SportvatarTemplate.getRestrictionsRegistryStoragePath())
            ?? panic("signer has no SportvatarTemplate.RestrictionsRegistry")
    }
    execute {
        self.admin.restrictAddress(address, reason: reason)
    }
    post {
        SportvatarTemplate.isAddressRestricted(address): "address not restricted"
    }
}
