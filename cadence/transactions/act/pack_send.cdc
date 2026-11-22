import "SportvatarPack"

// Admin/user: sends a Pack to an address that has a Pack collection.
transaction(packId: UInt64, address: Address) {
    let packCollection: auth(SportvatarPack.WithdrawEnt) &SportvatarPack.Collection
    let receiver: &{SportvatarPack.CollectionPublic}

    prepare(account: auth(BorrowValue) &Account) {
        self.packCollection = account.storage.borrow<auth(SportvatarPack.WithdrawEnt) &SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)
            ?? panic("Missing Pack collection")
        self.receiver = getAccount(address).capabilities.borrow<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath)
            ?? panic("The receiver has no Pack collection")
    }

    execute {
        self.receiver.deposit(token: <- self.packCollection.withdraw(withdrawID: packId))
    }
}
