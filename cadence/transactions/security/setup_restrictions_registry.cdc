import "SportvatarTemplate"

// Creates the restrictions registry (SportvatarTemplate.RestrictionsRegistry) in the storage of the contract
// account, after SportvatarTemplate has been updated. Idempotent. Signer: the Sportvatar contract account.
// The restrictions live inside SportvatarTemplate because Flow mainnet does not accept new contract
// deployments; an update can't add contract fields, so the state is this resource in account storage.
transaction {
    prepare(signer: auth(Storage) &Account) {
        SportvatarTemplate.setupRestrictionsRegistry(account: signer)
    }
    post {
        SportvatarTemplate.isRestrictionsRegistrySetUp(): "restrictions registry not set up"
    }
}
