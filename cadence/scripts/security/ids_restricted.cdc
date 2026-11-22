import "SportvatarTemplate"

// READ-ONLY: restriction flag per id.
access(all) fun main(nftType: String, ids: [UInt64]): [Bool] {
    let t = CompositeType(nftType) ?? panic("unknown type")
    let out: [Bool] = []
    for id in ids {
        out.append(SportvatarTemplate.isIDRestricted(nftType: t, id: id))
    }
    return out
}
