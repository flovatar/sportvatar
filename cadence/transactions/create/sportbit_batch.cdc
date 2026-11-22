import "Sportvatar"
import "Sportbit"
import "NonFungibleToken"

// Admin: mints `quantity` Sportbits from a template into the admin's Sportbit collection.
transaction(templateId: UInt64, quantity: UInt64) {
    let sportbitCollection: &Sportbit.Collection
    let sportvatarAdmin: &Sportvatar.Admin

    prepare(account: auth(BorrowValue) &Account) {
        self.sportbitCollection = account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)
            ?? panic("Missing Sportbit collection")
        self.sportvatarAdmin = account.storage.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)
            ?? panic("Missing Sportvatar admin")
    }

    execute {
        let collection <- self.sportvatarAdmin.batchCreateSportbits(templateId: templateId, quantity: quantity)
        for id in collection.getIDs() {
            self.sportbitCollection.deposit(token: <- collection.withdraw(withdrawID: id))
        }
        destroy collection
    }
}
