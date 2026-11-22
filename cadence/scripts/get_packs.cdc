import "SportvatarPack"

// Pack ids held by `address` (used by sportvatar:clean-packs to release expired reservations).
access(all) fun main(address: Address): [UInt64] {
    return SportvatarPack.getPacks(address: address) ?? []
}
