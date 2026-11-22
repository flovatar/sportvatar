import "SportvatarPack"

// Revokes (or un-revokes) v2 authorisations for specific pack ids.
transaction(ids: [UInt64], revoke: Bool) {
    let admin: auth(SportvatarPack.VerifierManage) &SportvatarPack.PackVerifier
    prepare(signer: auth(Storage) &Account) {
        SportvatarPack.setupPackVerifier(account: signer)   // idempotent; only the contract account can do this
        self.admin = signer.storage.borrow<auth(SportvatarPack.VerifierManage) &SportvatarPack.PackVerifier>(from: SportvatarPack.getPackVerifierStoragePath())
            ?? panic("signer has no SportvatarPack.PackVerifier")
    }
    execute {
        if revoke { self.admin.revokePacks(ids: ids) } else { self.admin.unrevokePacks(ids: ids) }
    }
}
