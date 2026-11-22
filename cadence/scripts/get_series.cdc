import "SportvatarTemplate"

access(all) fun main(id: UInt64): SportvatarTemplate.SeriesData? {
    return SportvatarTemplate.getSeries(id: id)
}
