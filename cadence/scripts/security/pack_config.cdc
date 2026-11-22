import "SportvatarPack"

// READ-ONLY: pack verifier configuration and the number of packs still in the contract account.
access(all) fun main(): {String: AnyStruct} {
    let c = SportvatarPack.getVerifierConfig()
    return {
        "signerPublicKey": c.signerPublicKey, "signatureAlgorithm": c.signatureAlgorithm, "hashAlgorithm": c.hashAlgorithm,
        "epoch": c.epoch, "claimsEnabled": c.claimsEnabled, "purchasesEnabled": c.purchasesEnabled,
        "revokedPacks": c.revokedPacks, "packsInContractAccount": (SportvatarPack.getPacks(address: Type<@SportvatarPack.Pack>().address!) ?? []).length
    }
}
