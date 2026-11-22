import "Sportbit"
import "MetadataViews"

// READ-ONLY: the "restricted" trait as marketplaces see it (MetadataViews.Traits) for a Sportbit held by `holder`.
access(all) fun main(holder: Address, id: UInt64): {String: AnyStruct} {
    let c = getAccount(holder).capabilities.borrow<&Sportbit.Collection>(Sportbit.CollectionPublicPath)
        ?? panic("no Sportbit collection")
    let nft = c.borrowAccessory(id: id) ?? panic("id not held")
    let traits = nft.resolveView(Type<MetadataViews.Traits>())! as! MetadataViews.Traits
    var flag: AnyStruct? = nil
    for tr in traits.traits { if tr.name == "restricted" { flag = tr.value } }
    return {"id": id, "restrictedTrait": flag, "isRestricted": Sportbit.isRestricted(id: id, owner: holder)}
}
