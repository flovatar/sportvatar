import Sportvatar, Sportbit, SportvatarTemplate, SportvatarPack from 0x921ea449dffec68a
import NonFungibleToken from 0x1d7e57aa55817448
import FungibleToken from 0xf233dcee88fe0abe
import FlowToken from 0x1654653399040a61

// This transaction will create a new Sportvatar by burning a Flame and all the necessary rarity Boosters
transaction(
    flame: UInt64,
    body: UInt64,
    hair: UInt64,
    facialHair: UInt64?,
    eyes: UInt64,
    nose: UInt64,
    mouth: UInt64,
    clothing: UInt64,
    accessory: UInt64?,
    hat: UInt64?,
    eyeglasses: UInt64?,
    background: UInt64?,
    rareBoost: [UInt64],
    epicBoost: [UInt64],
    legendaryBoost: [UInt64]
    ) {


    let sportvatarCollection: &Sportvatar.Collection
    let sportvatarComponentCollection: &Sportbit.Collection

    let flameNFT: @Sportbit.NFT
    let accessoryNFT: @Sportbit.NFT?
    let hatNFT: @Sportbit.NFT?
    let eyeglassesNFT: @Sportbit.NFT?
    let backgroundNFT: @Sportbit.NFT?
    let rareBoostNFT: @[Sportbit.NFT]
    let epicBoostNFT: @[Sportbit.NFT]
    let legendaryBoostNFT: @[Sportbit.NFT]
    let accountAddress: Address

    prepare(account: auth(Storage, Capabilities) &Account) {
        self.sportvatarCollection = account.storage.borrow<&Sportvatar.Collection>(from: Sportvatar.CollectionStoragePath)!

        self.sportvatarComponentCollection = account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!

        self.rareBoostNFT <-[]
        for componentId in rareBoost {
            let tempNFT <-self.sportvatarComponentCollection.withdraw(withdrawID: componentId) as! @Sportbit.NFT
            self.rareBoostNFT.append(<-tempNFT)
        }
        self.epicBoostNFT <-[]
        for componentId in epicBoost {
            let tempNFT <-self.sportvatarComponentCollection.withdraw(withdrawID: componentId) as! @Sportbit.NFT
            self.epicBoostNFT.append(<-tempNFT)
        }
        self.legendaryBoostNFT <-[]
        for componentId in legendaryBoost {
            let tempNFT <-self.sportvatarComponentCollection.withdraw(withdrawID: componentId) as! @Sportbit.NFT
            self.legendaryBoostNFT.append(<-tempNFT)
        }


        if(accessory != nil){
            self.accessoryNFT <- self.sportvatarComponentCollection.withdraw(withdrawID: accessory!) as! @Sportbit.NFT
        } else {
            self.accessoryNFT <- nil
        }

        if(hat != nil){
            self.hatNFT <- self.sportvatarComponentCollection.withdraw(withdrawID: hat!) as! @Sportbit.NFT
        } else {
            self.hatNFT <- nil
        }

        if(eyeglasses != nil){
            self.eyeglassesNFT <- self.sportvatarComponentCollection.withdraw(withdrawID: eyeglasses!) as! @Sportbit.NFT
        } else {
            self.eyeglassesNFT <- nil
        }

        if(background != nil){
            self.backgroundNFT <- self.sportvatarComponentCollection.withdraw(withdrawID: background!) as! @Sportbit.NFT
        } else {
            self.backgroundNFT <- nil
        }

        self.flameNFT <-self.sportvatarComponentCollection.withdraw(withdrawID: flame) as! @Sportbit.NFT

        self.accountAddress = account.address

    }

    execute {

        let sportvatar <- Sportvatar.createSportvatar(
            flame: <-self.flameNFT,
            body: body,
            hair: hair,
            facialHair: facialHair,
            eyes: eyes,
            nose: nose,
            mouth: mouth,
            clothing: clothing,
            accessory: <-self.accessoryNFT,
            hat: <-self.hatNFT,
            eyeglasses: <-self.eyeglassesNFT,
            background: <-self.backgroundNFT,
            rareBoost: <-self.rareBoostNFT,
            epicBoost: <-self.epicBoostNFT,
            legendaryBoost: <-self.legendaryBoostNFT,
            address: self.accountAddress
        )

        self.sportvatarCollection.deposit(token: <-sportvatar)
    }

}
