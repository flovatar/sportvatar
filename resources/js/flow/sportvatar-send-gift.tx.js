import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function sportvatarSendGiftTx(sportvatarId, address) {

    return await fcl
        .mutate({
            cadence: `
import Sportvatar from 0xSportvatar
import NonFungibleToken from 0xNonFungible

// Sends a Sportvatar to another address.
transaction(
    sportvatarId: UInt64,
    address: Address) {

    let sportvatarCollection: auth(NonFungibleToken.Withdraw) &Sportvatar.Collection
    let receiverCollection: &{NonFungibleToken.Receiver}

    prepare(account: auth(BorrowValue) &Account) {
        self.sportvatarCollection = account.storage.borrow<auth(NonFungibleToken.Withdraw) &Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath)
            ?? panic("Could not borrow the Sportvatar collection")
        self.receiverCollection = getAccount(address).capabilities.borrow<&{NonFungibleToken.Receiver}>(Sportvatar.CollectionPublicPath)
            ?? panic("The receiver has no Sportvatar collection")
    }

    execute {
        self.receiverCollection.deposit(token: <- self.sportvatarCollection.withdraw(withdrawID: sportvatarId))
    }
}
`,
            args: (arg, t) => [
                arg(''+sportvatarId, t.UInt64),
                arg(address, t.Address)
            ],
            limit: 9999
        });

}
