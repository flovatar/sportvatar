import "SportvatarTemplate"
import "Sportbit"
import "SportvatarPack"

// READ-ONLY post-update status of the Sportvatar account. Type-checks only once the update is live.
access(all) fun main(incident: Address, sampleIds: [UInt64]): {String: AnyStruct} {
    let t = Type<@Sportbit.NFT>()
    let sample: [Bool] = []
    for id in sampleIds { sample.append(Sportbit.isRestricted(id: id, owner: nil)) }
    let c = SportvatarPack.getVerifierConfig()
    return {
        "incidentAddressRestricted": SportvatarTemplate.isAddressRestricted(incident),
        "restrictedSportbitCount": SportvatarTemplate.getRestrictedIDCount(nftType: t),
        "sampleIdsRestricted": sample,
        "restrictedAddresses": SportvatarTemplate.getRestrictedAddresses(),
        "packSignerSet": c.signerPublicKey != nil, "packEpoch": c.epoch,
        "claimsEnabled": c.claimsEnabled, "purchasesEnabled": c.purchasesEnabled
    }
}
