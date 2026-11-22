import "Sportvatar"

// true if `address` holds Sportvatar `id` (cheap: get_sportvatar.cdc can hit the script computation limit)
access(all) fun main(address: Address, id: UInt64): Bool {
    if let c = getAccount(address).capabilities.borrow<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath) {
        return c.getIDs().contains(id)
    }
    return false
}
