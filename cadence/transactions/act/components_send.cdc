import "Sportbit"
import "NonFungibleToken"

// Sends several Sportbits to an address that has a Sportbit collection.
transaction(componentIds: [UInt64], address: Address) {
    let sportbitCollection: auth(NonFungibleToken.Withdraw) &Sportbit.Collection
    let receiver: &{NonFungibleToken.Receiver}

    prepare(account: auth(BorrowValue) &Account) {
        self.sportbitCollection = account.storage.borrow<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>(from: Sportbit.CollectionStoragePath)
            ?? panic("Missing Sportbit collection")
        self.receiver = getAccount(address).capabilities.borrow<&{NonFungibleToken.Receiver}>(Sportbit.CollectionPublicPath)
            ?? panic("The receiver has no Sportbit collection")
    }

    execute {
        for id in componentIds {
            self.receiver.deposit(token: <- self.sportbitCollection.withdraw(withdrawID: id))
        }
    }
}
