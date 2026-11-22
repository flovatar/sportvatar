import FungibleToken from "../../contracts/FungibleToken.cdc"
import NonFungibleToken from "../../contracts/NonFungibleToken.cdc"
import FlowToken from "../../contracts/FlowToken.cdc"
import Sportvatar from "../../contracts/Sportvatar.cdc"
import Sportbit from "../../contracts/Sportbit.cdc"
import SportvatarTemplate from "../../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../../contracts/SportvatarPack.cdc"


//this transaction will create a new Webshot and create and auction for it
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

    prepare(account: AuthAccount) {
        self.templateCollection = account.borrow<&SportvatarTemplate.Collection>(from: SportvatarTemplate.CollectionStoragePath)!

        self.sportvatarAdmin = account.borrow<&Sportvatar.Admin>(from: Sportvatar.AdminStoragePath)!
    }

    execute {
        let layers: {UInt32: SportvatarTemplate.Layer} = {}
        var i: UInt32 = UInt32(0)
        while(i <  UInt32(layersId.length)){
            layers.insert(key: layersId[i]!, SportvatarTemplate.Layer(id: layersId[i]!, name: layersName[i]!, isAccessory: layersIsAccessory[i]!))
            i = i + UInt32(1)
        }


        let colors: {UInt32: String} = {}
        i = UInt32(0)
        while(i <  UInt32(colorsId.length)){
            colors.insert(key: colorsId[i]!, colorsName[i]!)
            i = i + UInt32(1)
        }

        let sportvatarSeries <- self.sportvatarAdmin.createSeries(
            name: name,
            description: description,
            svgPrefix: svgPrefix,
            svgSuffix: svgSuffix,
            layers: layers,
            colors: colors,
            metadata: {},
            maxMintable: maxMintable
            ) as! @SportvatarTemplate.Series


        self.templateCollection.depositSeries(series: <-sportvatarSeries)

    }
}
