import "Sportvatar"
import "SportvatarTemplate"

// Admin: creates a new Sportvatar Series and stores it in the admin's template collection.
transaction(
    name: String,
    description: String,
    svgPrefix: String,
    svgSuffix: String,
    layersId: [UInt32],
    layersName: [String],
    layersIsAccessory: [Bool],
    colorsId: [UInt32],
    colorsName: [String],
    maxMintable: UInt64) {

    let templateCollection: &SportvatarTemplate.Collection
    let sportvatarAdmin: &Sportvatar.Admin

    prepare(account: auth(BorrowValue) &Account) {
        self.templateCollection = account.storage.borrow<&SportvatarTemplate.Collection>(from: SportvatarTemplate.CollectionStoragePath)
            ?? panic("Missing SportvatarTemplate collection")
        self.sportvatarAdmin = account.storage.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)
            ?? panic("Missing Sportvatar admin")
    }

    execute {
        let layers: {UInt32: SportvatarTemplate.Layer} = {}
        var i = 0
        while i < layersId.length {
            layers.insert(key: layersId[i], SportvatarTemplate.Layer(id: layersId[i], name: layersName[i], isAccessory: layersIsAccessory[i]))
            i = i + 1
        }
        let colors: {UInt32: String} = {}
        i = 0
        while i < colorsId.length {
            colors.insert(key: colorsId[i], colorsName[i])
            i = i + 1
        }
        let series <- self.sportvatarAdmin.createSeries(
            name: name,
            description: description,
            svgPrefix: svgPrefix,
            svgSuffix: svgSuffix,
            layers: layers,
            colors: colors,
            metadata: {},
            maxMintable: maxMintable
        )
        self.templateCollection.depositSeries(series: <-series)
    }
}
