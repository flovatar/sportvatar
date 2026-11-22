import "Sportvatar"
import "SportvatarTemplate"

// Admin: creates a new Sportvatar Template (trait) and stores it in the admin's template collection.
transaction(
    name: String,
    description: String,
    series: UInt64,
    layer: UInt32,
    sport: String,
    rarity: String,
    svg: String,
    maxMintableComponents: UInt64) {

    let templateCollection: &SportvatarTemplate.Collection
    let sportvatarAdmin: &Sportvatar.Admin

    prepare(account: auth(BorrowValue) &Account) {
        self.templateCollection = account.storage.borrow<&SportvatarTemplate.Collection>(from: SportvatarTemplate.CollectionStoragePath)
            ?? panic("Missing SportvatarTemplate collection")
        self.sportvatarAdmin = account.storage.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)
            ?? panic("Missing Sportvatar admin")
    }

    execute {
        let template <- self.sportvatarAdmin.createTemplate(
            name: name,
            description: description,
            series: series,
            layer: layer,
            metadata: {},
            rarity: rarity,
            sport: sport,
            svg: svg,
            maxMintableComponents: maxMintableComponents
        )
        self.templateCollection.deposit(template: <-template)
    }
}
