// FCLCrypto is deployed by the Flow team on mainnet at 0xb4b82a1c9d21d284.
// It checks the signatures against the account's on-chain keys
// (revoked keys excluded, combined weight >= 1000) using the
// proper FCL domain tag.
import FCLCrypto from 0xb4b82a1c9d21d284

access(all) fun main(
    address: Address,
    message: String,
    keyIndices: [UInt64],
    signatures: [String]
): Bool {
    let indices: [Int] = []
    for keyIndex in keyIndices {
        indices.append(Int(keyIndex))
    }
    return FCLCrypto.verifyUserSignatures(address: address, message: message, keyIndices: indices, signatures: signatures)
}
