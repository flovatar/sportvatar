import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"

export async function isInitializedDapperScript(address) {
    if (address == null)
        throw new Error("isInitialized(address) -- address required")

    return await fcl
        .query({
            cadence: `
import Sportvatar, Sportbit from 0xSportvatar

access(all) fun main(address: Address): Bool {
  let account = getAccount(address)
  let sportvatarCap = account.capabilities.get<&Sportvatar.Collection>(Sportvatar.CollectionPublicPath)
  let sportbitCap = account.capabilities.get<&Sportbit.Collection>(Sportbit.CollectionPublicPath)
  return sportvatarCap.check() && sportbitCap.check()
}
`,
            args: (arg, t) => [
                arg(address, t.Address)
            ],
        });

}
