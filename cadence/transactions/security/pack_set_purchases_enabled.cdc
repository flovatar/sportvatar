import "SportvatarPack"

// Switches paid pack purchases (v2 authorisations) on or off.
transaction(enabled: Bool) {
    let admin: auth(SportvatarPack.VerifierManage) &SportvatarPack.PackVerifier
    prepare(signer: auth(Storage) &Account) {
        SportvatarPack.setupPackVerifier(account: signer)   // idempotent; only the contract account can do this
        self.admin = signer.storage.borrow<auth(SportvatarPack.VerifierManage) &SportvatarPack.PackVerifier>(from: SportvatarPack.getPackVerifierStoragePath())
            ?? panic("signer has no SportvatarPack.PackVerifier")
    }
    execute {
        self.admin.setPurchasesEnabled(enabled)
    }
    post {
        SportvatarPack.getVerifierConfig().purchasesEnabled == enabled: "flag not updated"
    }
}
