import "SportvatarPack"

// Sets the pack back-end signer PUBLIC key (hex, 64 bytes). signatureAlgorithm 1 = ECDSA_P256, hashAlgorithm 3 = SHA3_256.
transaction(publicKey: String, signatureAlgorithm: UInt8, hashAlgorithm: UInt8) {
    let admin: auth(SportvatarPack.VerifierManage) &SportvatarPack.PackVerifier
    prepare(signer: auth(Storage) &Account) {
        SportvatarPack.setupPackVerifier(account: signer)   // idempotent; only the contract account can do this
        self.admin = signer.storage.borrow<auth(SportvatarPack.VerifierManage) &SportvatarPack.PackVerifier>(from: SportvatarPack.getPackVerifierStoragePath())
            ?? panic("signer has no SportvatarPack.PackVerifier")
    }
    execute {
        self.admin.setSigner(publicKey: publicKey, signatureAlgorithm: signatureAlgorithm, hashAlgorithm: hashAlgorithm)
    }
    post {
        SportvatarPack.getVerifierConfig().signerPublicKey == publicKey: "signer not set"
    }
}
