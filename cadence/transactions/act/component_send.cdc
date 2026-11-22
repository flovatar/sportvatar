import "Sportbit"
import "NonFungibleToken"

// Sends one Sportbit to an address that has a Sportbit collection.
transaction(componentId: UInt64, address: Address) {
    let sportbitCollection: auth(NonFungibleToken.Withdraw) &Sportbit.Collection
    let receiver: &{NonFungibleToken.Receiver}

    prepare(account: auth(BorrowValue) &Account) {
        self.sportbitCollection = account.storage.borrow<auth(NonFungibleToken.Withdraw) &Sportbit.Collection>(from: Sportbit.CollectionStoragePath)
            ?? panic("Missing Sportbit collection")
        self.receiver = getAccount(address).capabilities.borrow<&{NonFungibleToken.Receiver}>(Sportbit.CollectionPublicPath)
            ?? panic("The receiver has no Sportbit collection")
    }

    execute {
        self.receiver.deposit(token: <- self.sportbitCollection.withdraw(withdrawID: componentId))
    }
}
