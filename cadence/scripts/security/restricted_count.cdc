import "SportvatarTemplate"

// READ-ONLY: number of restricted ids of a type.
access(all) fun main(nftType: String): Int {
    return SportvatarTemplate.getRestrictedIDCount(nftType: CompositeType(nftType) ?? panic("unknown type"))
}
