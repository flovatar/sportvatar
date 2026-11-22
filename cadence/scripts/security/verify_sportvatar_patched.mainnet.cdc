import Sportvatar from 0xca5c31c0c03e11be
import Sportbit from 0xca5c31c0c03e11be
import FlovatarComponent from 0x921ea449dffec68a

// READ-ONLY post-deploy check for the Sportvatar access-hardening update.
// The theft paths are inside `if false {}`: nothing executes, the script only type-checks.
// BEFORE the update (today): returns the collection type (paths reachable = vulnerable).
// AFTER the update: MUST FAIL with "cannot access `removeAccessory` ... requires `PrivateEnt` authorization"
// (and the same for removeFlovatarBackground, setSportbit, borrowSportvatarPrivate).
access(all) fun main(victim: Address, sportvatarId: UInt64): String {
    let col = getAccount(victim).capabilities.borrow<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath)
        ?? panic("no public Sportvatar collection")
    if false {
        let sv = col.borrowSportvatar(id: sportvatarId)!
        let a <- sv.removeAccessory(layer: 12)
        destroy a
        let b <- sv.removeFlovatarBackground()
        destroy b
        let junk: @Sportbit.NFT? <- nil
        let c <- sv.setSportbit(layer: 12, sportbit: <- junk!)
        destroy c
        let p = col.borrowSportvatarPrivate(id: sportvatarId)!
        let d <- p.removeAccessory(layer: 13)
        destroy d
    }
    return col.getType().identifier
}
