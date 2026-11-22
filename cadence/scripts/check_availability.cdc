import FungibleToken from "../contracts/FungibleToken.cdc"
import NonFungibleToken from "../contracts/NonFungibleToken.cdc"
import FlowToken from "../contracts/FlowToken.cdc"
import Sportvatar from "../contracts/Sportvatar.cdc"
import Sportbit from "../contracts/Sportbit.cdc"
import SportvatarTemplate from "../contracts/SportvatarTemplate.cdc"
import SportvatarPack from "../contracts/SportvatarPack.cdc"


pub fun main(
    name: String,
    body: UInt64,
    hair: UInt64,
    facialHair: UInt64?,
    eyes: UInt64,
    nose: UInt64,
    mouth: UInt64,
    clothing: UInt64) : {String: Bool} {

    //return Sportvatar.checkCombinationAvailable(body: body, hair: hair, facialHair: facialHair, eyes: eyes, nose: nose, mouth: mouth, clothing: clothing) && Sportvatar.checkNameAvailable(name: name)

    let boolCombination = Sportvatar.checkCombinationAvailable(body: body, hair: hair, facialHair: facialHair, eyes: eyes, nose: nose, mouth: mouth, clothing: clothing)
    let boolName = Sportvatar.checkNameAvailable(name: name)
    let status:{String:Bool} = {}
    status["combination"] = boolCombination
    status["name"] = boolName
    status["available"] = boolCombination && boolName
    return status

}