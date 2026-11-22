import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function componentSendGiftTx(componentId, address) {
    return await fcl
        .mutate({
            cadence: `
import Sportbit from 0xSportvatar
import NonFungibleToken from 0xNonFungible

// Sends a Sportbit to another address.
transaction(
    componentId: UInt64,
    address: Address) {

    let sportbitCollection: auth(NonFungibleToken.Withdraw) &Sportbit.Collection
    let receiverCollection: &{NonFungibleToken.Receiver}

    prepare(account: auth(BorrowValue) &Account) {
        self.sportbitCollection = account.storage.borrow<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>(from: Sportbit.CollectionStoragePath)
            ?? panic("Could not borrow the Sportbit collection")
        self.receiverCollection = getAccount(address).capabilities.borrow<&{NonFungibleToken.Receiver}>(Sportbit.CollectionPublicPath)
            ?? panic("The receiver has no Sportbit collection")
    }

    execute {
        self.receiverCollection.deposit(token: <- self.sportbitCollection.withdraw(withdrawID: componentId))
    }
}
`,
            args: (arg, t) => [
                arg(''+componentId, t.UInt64),
                arg(address, t.Address)
            ],
            limit: 9999
        });

}
