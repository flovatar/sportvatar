import "SportvatarPack"

// Invalidates every outstanding v2 pack authorisation (the back-end must sign with the new epoch).
transaction() {
    let admin: auth(SportvatarPack.VerifierManage) &SportvatarPack.PackVerifier
    prepare(signer: auth(Storage) &Account) {
        SportvatarPack.setupPackVerifier(account: signer)   // idempotent; only the contract account can do this
        self.admin = signer.storage.borrow<auth(SportvatarPack.VerifierManage) &SportvatarPack.PackVerifier>(from: SportvatarPack.getPackVerifierStoragePath())
            ?? panic("signer has no SportvatarPack.PackVerifier")
    }
    execute {
        self.admin.bumpEpoch()
    }
}
