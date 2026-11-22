import "Sportvatar"
import "Sportbit"

// Admin: mints one Sportbit from a template into the admin's Sportbit collection.
transaction(templateId: UInt64) {
    let sportbitCollection: &Sportbit.Collection
    let sportvatarAdmin: &Sportvatar.Admin

    prepare(account: auth(BorrowValue) &Account) {
        self.sportbitCollection = account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)
            ?? panic("Missing Sportbit collection")
        self.sportvatarAdmin = account.storage.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)
            ?? panic("Missing Sportvatar admin")
    }

    execute {
        self.sportbitCollection.deposit(token: <- self.sportvatarAdmin.createSportbit(templateId: templateId))
    }
}
