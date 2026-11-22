import "Sportbit"

access(all) fun main(address: Address): [UInt64] {
    if let collection = getAccount(address).capabilities.borrow<&Sportbit.Collection>(Sportbit.CollectionPublicPath) {
        return collection.getIDs()
    }
    return []
}
