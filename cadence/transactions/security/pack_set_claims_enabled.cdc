import "SportvatarPack"

// Switches free pack claims (v2 codes) on or off.
transaction(enabled: Bool) {
    let admin: auth(SportvatarPack.VerifierManage) &SportvatarPack.PackVerifier
    prepare(signer: auth(Storage) &Account) {
        SportvatarPack.setupPackVerifier(account: signer)   // idempotent; only the contract account can do this
        self.admin = signer.storage.borrow<auth(SportvatarPack.VerifierManage) &SportvatarPack.PackVerifier>(from: SportvatarPack.getPackVerifierStoragePath())
            ?? panic("signer has no SportvatarPack.PackVerifier")
    }
    execute {
        self.admin.setClaimsEnabled(enabled)
    }
    post {
        SportvatarPack.getVerifierConfig().claimsEnabled == enabled: "flag not updated"
    }
}
