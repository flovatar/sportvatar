// FCLCrypto is deployed by the Flow team on testnet at 0x74daa6f9c7ef24b1.
// It checks the signatures against the account's on-chain keys
// (revoked keys excluded, combined weight >= 1000) using the
// proper FCL domain tag.
import FCLCrypto from 0x74daa6f9c7ef24b1

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
    return FCLCrypto.verifyAccountProofSignatures(address: address, message: message, keyIndices: indices, signatures: signatures)
}
