import Sportvatar from 0xca5c31c0c03e11be
import Sportbit from 0xca5c31c0c03e11be

// READ-ONLY post-deploy check (run AFTER the update): must return all `true`.
//  [0] entitled borrow of a holder's public Sportvatar capability is nil
//  [1..4] getSupportedNFTTypes / isSupportedNFTType no longer panic and are correct
//  [5] the holder's Sportvatar is still readable (accessories getter)
access(all) fun main(holder: Address, sportvatarId: UInt64): [Bool] {
    let acct = getAccount(holder)
    let a = acct.capabilities.borrow<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath)!
    let b = getAccount(0xca5c31c0c03e11be).capabilities.borrow<&Sportbit.Collection>(Sportbit.CollectionPublicPath)!
    return [
        acct.capabilities.borrow<auth(Sportvatar.PrivateEnt) &Sportvatar.Collection>(Sportvatar.CollectionPublicPath) == nil,
        a.isSupportedNFTType(type: Type<@Sportvatar.NFT>()),
        a.getSupportedNFTTypes()[Type<@Sportvatar.NFT>()] == true,
        b.isSupportedNFTType(type: Type<@Sportbit.NFT>()),
        b.getSupportedNFTTypes()[Type<@Sportbit.NFT>()] == true,
        a.borrowSportvatar(id: sportvatarId) != nil
    ]
}
