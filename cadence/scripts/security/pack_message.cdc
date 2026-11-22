import "SportvatarPack"

// READ-ONLY: the exact message the pack back-end must sign for a v2 authorisation (current epoch), and its check result.
access(all) fun main(kind: String, packId: UInt64, recipient: Address, expiry: UInt64, signature: String): {String: AnyStruct} {
    let epoch = SportvatarPack.getVerifierEpoch()
    return {
        "message": SportvatarPack.buildVerifierMessage(kind: kind, packId: packId, recipient: recipient, expiry: expiry, epoch: epoch),
        "messageHex": String.encodeHex(SportvatarPack.buildVerifierMessage(kind: kind, packId: packId, recipient: recipient, expiry: expiry, epoch: epoch).utf8),
        "epoch": epoch,
        "rejectReason": signature == "" ? "no signature given" : SportvatarPack.checkAuthorisation(kind: kind, packId: packId, recipient: recipient, expiry: expiry, signature: signature)
    }
}
