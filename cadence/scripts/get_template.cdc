import "SportvatarTemplate"

access(all) fun main(id: UInt64): SportvatarTemplate.TemplateData? {
    return SportvatarTemplate.getTemplate(id: id)
}
