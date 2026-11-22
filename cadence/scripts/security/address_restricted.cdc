import "SportvatarTemplate"

// READ-ONLY: is the address restricted, and why.
access(all) fun main(address: Address): {String: AnyStruct} {
    return {"restricted": SportvatarTemplate.isAddressRestricted(address), "reason": SportvatarTemplate.getAddressReason(address)}
}
