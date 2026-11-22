import "SportvatarPack"

// Opens a Pack owned by the signer (the signer needs a Sportbit collection published at Sportbit.CollectionPublicPath).
transaction(packId: UInt64) {
    let packCollection: auth(SportvatarPack.WithdrawEnt) &SportvatarPack.Collection

    prepare(account: auth(BorrowValue) &Account) {
        self.packCollection = account.storage.borrow<auth(SportvatarPack.WithdrawEnt) &SportvatarPack.Collection>(from: SportvatarPack.CollectionStoragePath)
            ?? panic("Missing Pack collection")
    }

    execute {
        self.packCollection.openPack(id: packId)
    }
}
