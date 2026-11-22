import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function componentSendGiftTx(componentId, address) {
    return await fcl
        .mutate({
            cadence: `
import Sportvatar, Sportbit, SportvatarTemplate, SportvatarPack from 0xSportvatar
import NonFungibleToken from 0xNonFungible
import FungibleToken from 0xFungible
import FlowToken from 0xFlowToken

transaction(
    componentId: UInt64,
    address: Address) {

    let sportvatarComponentCollection: &Sportbit.Collection
    let sportvatarComponentReceiverCollection: Capability<&Sportbit.CollectionPublic>

    prepare(account: auth(Storage, Capabilities) &Account) {
        self.sportvatarComponentCollection = account.storage.borrow<&Sportbit.Collection>(from: Sportbit.CollectionStoragePath)!


        let receiverAccount = getAccount(address)
        self.sportvatarComponentReceiverCollection = receiverAccount.capabilities.get<&Sportbit.CollectionPublic>(Sportbit.CollectionPublicPath)
    }

    execute {
        let component <- self.sportvatarComponentCollection.withdraw(withdrawID: componentId)
        if(component == nil){
            panic("Component not found!")
        }
        self.sportvatarComponentReceiverCollection.borrow()!.deposit(token: <-component)
    }
}
`,
            args: (arg, t) => [
                arg(''+componentId, t.UInt64),
                arg(address, t.Address)
            ],
            limit: 9999
        });

}
