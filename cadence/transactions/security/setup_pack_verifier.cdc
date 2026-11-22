import "SportvatarPack"

// Creates the pack codes v2 verifier (SportvatarPack.PackVerifier) in the storage of the contract account, after
// SportvatarPack has been updated: no signer, claims and purchases disabled. Idempotent.
// Signer: the Sportvatar contract account.
transaction {
    prepare(signer: auth(Storage) &Account) {
        SportvatarPack.setupPackVerifier(account: signer)
    }
    post {
        SportvatarPack.isPackVerifierSetUp(): "pack verifier not set up"
    }
}
