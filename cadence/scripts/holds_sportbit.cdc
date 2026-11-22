import "Sportbit"

// true if `address` holds Sportbit `id` in its collection (not equipped, not inside a pack)
access(all) fun main(address: Address, id: UInt64): Bool {
    if let c = getAccount(address).capabilities.borrow<&Sportbit.Collection>(Sportbit.CollectionPublicPath) {
        return c.getIDs().contains(id)
    }
    return false
}
