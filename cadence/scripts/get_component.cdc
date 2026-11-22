import "Sportbit"

access(all) fun main(address: Address, componentId: UInt64): Sportbit.AccessoryData? {
    return Sportbit.getSportbit(address: address, componentId: componentId)
}
