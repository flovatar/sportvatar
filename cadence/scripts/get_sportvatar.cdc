import "Sportvatar"

access(all) fun main(address: Address, sportvatarId: UInt64): Sportvatar.SportvatarData? {
    return Sportvatar.getSportvatar(address: address, sportvatarId: sportvatarId)
}
