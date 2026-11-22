/*

 This contract defines the Sportvatar Templates and the Collection to manage them.
 Sportvatar Templates are the building blocks (lego bricks) of the final Sportvatar,

 Templates are NOT using the NFT standard and will be always linked only to the contract's owner account.

 Templates are organized in Series, Layers and have maximum mint number along with some other variables.

 */

access(all)
contract SportvatarTemplate{ 
	access(all)
	let CollectionStoragePath: StoragePath
	
	access(all)
	let CollectionPublicPath: PublicPath
	
	// Counter for all the Templates ever minted
	access(all)
	var totalSupply: UInt64
	
	access(all)
	var totalSeriesSupply: UInt64
	
	//These counters will keep track of how many Components were minted for each Template
	access(contract)
	let totalMintedComponents:{ UInt64: UInt64}
	
	access(contract)
	let totalMintedCollectibles:{ UInt64: UInt64}
	
	access(contract)
	let lastComponentMintedAt:{ UInt64: UFix64}
	
	// Event to notify about the Template creation
	access(all)
	event ContractInitialized()
	
	access(all)
	event Created(
		id: UInt64,
		name: String,
		series: UInt64,
		layer: UInt32,
		maxMintableComponents: UInt64
	)
	
	access(all)
	event CreatedSeries(id: UInt64, name: String, maxMintable: UInt64)
	
	access(all)
	struct Layer{ 
		access(all)
		let id: UInt32
		
		access(all)
		let name: String
		
		access(all)
		let isAccessory: Bool
		
		init(id: UInt32, name: String, isAccessory: Bool){ 
			self.id = id
			self.name = name
			self.isAccessory = isAccessory
		}
	}
	
	access(all)
	resource interface PublicSeries{ 
		access(all)
		let id: UInt64
		
		access(all)
		let name: String
		
		access(all)
		let description: String
		
		access(all)
		let svgPrefix: String
		
		access(all)
		let svgSuffix: String
		
		access(contract)
		let layers:{ UInt32: Layer}
		
		access(contract)
		let colors:{ UInt32: String}
		
		access(contract)
		let metadata:{ String: String}
		
		access(all)
		let maxMintable: UInt64
		
		access(all)
		fun getLayers():{ UInt32: Layer}
		
		access(all)
		fun getColors():{ UInt32: String}
		
		access(all)
		fun getMetadata():{ String: String}
	}
	
	// The Series resource implementing the public interface as well
	access(all)
	resource Series: PublicSeries{ 
		access(all)
		let id: UInt64
		
		access(all)
		let name: String
		
		access(all)
		let description: String
		
		access(all)
		let svgPrefix: String
		
		access(all)
		let svgSuffix: String
		
		access(contract)
		let layers:{ UInt32: Layer}
		
		access(contract)
		let colors:{ UInt32: String}
		
		access(contract)
		let metadata:{ String: String}
		
		access(all)
		let maxMintable: UInt64
		
		access(all)
		fun getLayers():{ UInt32: Layer}{ 
			return self.layers
		}
		
		access(all)
		fun getColors():{ UInt32: String}{ 
			return self.colors
		}
		
		access(all)
		fun getMetadata():{ String: String}{ 
			return self.metadata
		}
		
		init(name: String, description: String, svgPrefix: String, svgSuffix: String, layers:{ UInt32: Layer}, colors:{ UInt32: String}, metadata:{ String: String}, maxMintable: UInt64){ 
			// increments the counter and stores it as the ID
			SportvatarTemplate.totalSeriesSupply = SportvatarTemplate.totalSeriesSupply + UInt64(1)
			self.id = SportvatarTemplate.totalSeriesSupply
			self.name = name
			self.description = description
			self.svgPrefix = svgPrefix
			self.svgSuffix = svgSuffix
			self.layers = layers
			self.colors = colors
			self.metadata = metadata
			self.maxMintable = maxMintable
		}
	}
	
	// The public interface providing the SVG and all the other 
	// metadata like name, series, layer, etc.
	access(all)
	resource interface Public{ 
		access(all)
		let id: UInt64
		
		access(all)
		let name: String
		
		access(all)
		let description: String
		
		access(all)
		let series: UInt64
		
		access(all)
		let layer: UInt32
		
		access(contract)
		let metadata:{ String: String}
		
		access(all)
		let rarity: String
		
		access(all)
		let sport: String
		
		access(all)
		let svg: String
		
		access(all)
		let maxMintableComponents: UInt64
		
		access(all)
		fun getMetadata():{ String: String}{ 
			return self.metadata
		}
	}
	
	// The Template resource implementing the public interface as well
	access(all)
	resource Template: Public{ 
		access(all)
		let id: UInt64
		
		access(all)
		let name: String
		
		access(all)
		let description: String
		
		access(all)
		let series: UInt64
		
		access(all)
		let layer: UInt32
		
		access(contract)
		let metadata:{ String: String}
		
		access(all)
		let rarity: String
		
		access(all)
		let sport: String
		
		access(all)
		let svg: String
		
		access(all)
		let maxMintableComponents: UInt64
		
		// Initialize a Template with all the necessary data
		init(name: String, description: String, series: UInt64, layer: UInt32, metadata:{ String: String}, rarity: String, sport: String, svg: String, maxMintableComponents: UInt64){ 
			// increments the counter and stores it as the ID
			SportvatarTemplate.totalSupply = SportvatarTemplate.totalSupply + UInt64(1)
			self.id = SportvatarTemplate.totalSupply
			self.name = name
			self.description = description
			self.series = series
			self.layer = layer
			self.metadata = metadata
			self.rarity = rarity
			self.sport = sport
			self.svg = svg
			self.maxMintableComponents = maxMintableComponents
		}
	}
	
	// Standard CollectionPublic interface that can also borrow Component Templates
	access(all)
	resource interface CollectionPublic{ 
		access(all)
		fun getIDs(): [UInt64]
		
		access(all)
		fun getSeriesIDs(): [UInt64]
		
		access(all)
		fun borrowTemplate(id: UInt64): &{SportvatarTemplate.Public}?
		
		access(all)
		fun borrowSeries(id: UInt64): &{SportvatarTemplate.PublicSeries}?
	}
	
	// The main Collection that manages the Templates and that implements also the Public interface
	access(all)
	resource Collection: CollectionPublic{ 
		// Dictionary of Component Templates
		access(all)
		var ownedTemplates: @{UInt64: SportvatarTemplate.Template}
		
		access(all)
		var ownedSeries: @{UInt64: SportvatarTemplate.Series}
		
		init(){ 
			self.ownedTemplates <-{} 
			self.ownedSeries <-{} 
		}
		
		// deposit takes a Component Template and adds it to the collections dictionary
		// and adds the ID to the id array
		access(all)
		fun deposit(template: @SportvatarTemplate.Template){ 
			let id: UInt64 = template.id
			
			// add the new Component Template to the dictionary which removes the old one
			let oldTemplate <- self.ownedTemplates[id] <- template
			destroy oldTemplate
		}
		
		// deposit takes a Series and adds it to the collections dictionary
		// and adds the ID to the id array
		access(all)
		fun depositSeries(series: @SportvatarTemplate.Series){ 
			let id: UInt64 = series.id
			
			// add the new Component Template to the dictionary which removes the old one
			let oldTemplate <- self.ownedSeries[id] <- series
			destroy oldTemplate
		}
		
		// getIDs returns an array of the IDs that are in the collection
		access(all)
		fun getIDs(): [UInt64]{ 
			return self.ownedTemplates.keys
		}
		
		// getIDs returns an array of the IDs that are in the collection
		access(all)
		fun getSeriesIDs(): [UInt64]{ 
			return self.ownedSeries.keys
		}
		
		// borrowTemplate returns a borrowed reference to a Component Template
		// so that the caller can read data and call methods from it.
		access(all)
		fun borrowTemplate(id: UInt64): &{SportvatarTemplate.Public}?{ 
			if self.ownedTemplates[id] != nil{ 
				let ref = (&self.ownedTemplates[id] as &SportvatarTemplate.Template?)!
				return ref as! &SportvatarTemplate.Template
			} else{ 
				return nil
			}
		}
		
		// borrowTemplate returns a borrowed reference to a Component Template
		// so that the caller can read data and call methods from it.
		access(all)
		fun borrowSeries(id: UInt64): &{SportvatarTemplate.PublicSeries}?{ 
			if self.ownedSeries[id] != nil{ 
				let ref = (&self.ownedSeries[id] as &SportvatarTemplate.Series?)!
				return ref as! &SportvatarTemplate.Series
			} else{ 
				return nil
			}
		}
	}
	
	// This function can only be called by the account owner to create an empty Collection
	access(account)
	fun createEmptyCollection(): @SportvatarTemplate.Collection{ 
		return <-create Collection()
	}
	
	// This struct is used to send a data representation of the Templates
	// when retrieved using the contract helper methods outside the collection.
	access(all)
	struct SeriesData{ 
		access(all)
		let id: UInt64
		
		access(all)
		let name: String
		
		access(all)
		let description: String
		
		access(all)
		let svgPrefix: String
		
		access(all)
		let svgSuffix: String
		
		access(all)
		let layers: &{ UInt32: Layer}
		
		access(all)
		let colors: &{ UInt32: String}
		
		access(all)
		let metadata: &{ String: String}
		
		access(all)
		let maxMintable: UInt64
		
		access(all)
		let totalMintedCollectibles: UInt64
		
		init(
			id: UInt64,
			name: String,
			description: String,
			svgPrefix: String,
			svgSuffix: String,
			layers: &{
				UInt32: Layer
			},
			colors: &{
				UInt32: String
			},
			metadata: &{
				String: String
			},
			maxMintable: UInt64
		){ 
			self.id = id
			self.name = name
			self.description = description
			self.svgPrefix = svgPrefix
			self.svgSuffix = svgSuffix
			self.layers = layers
			self.colors = colors
			self.metadata = metadata
			self.maxMintable = maxMintable
			self.totalMintedCollectibles = SportvatarTemplate.getTotalMintedCollectibles(
					series: id
				)!
		}
	}
	
	// This struct is used to send a data representation of the Templates 
	// when retrieved using the contract helper methods outside the collection.
	access(all)
	struct TemplateData{ 
		access(all)
		let id: UInt64
		
		access(all)
		let name: String
		
		access(all)
		let description: String
		
		access(all)
		let series: UInt64
		
		access(all)
		let layer: UInt32
		
		access(all)
		let metadata: &{ String: String}
		
		access(all)
		let rarity: String
		
		access(all)
		let sport: String
		
		access(all)
		let svg: String?
		
		access(all)
		let maxMintableComponents: UInt64
		
		access(all)
		let totalMintedComponents: UInt64
		
		access(all)
		let lastComponentMintedAt: UFix64
		
		init(
			id: UInt64,
			name: String,
			description: String,
			series: UInt64,
			layer: UInt32,
			metadata: &{
				String: String
			},
			rarity: String,
			sport: String,
			svg: String?,
			maxMintableComponents: UInt64
		){ 
			self.id = id
			self.name = name
			self.description = description
			self.series = series
			self.layer = layer
			self.metadata = metadata
			self.rarity = rarity
			self.sport = sport
			self.svg = svg
			self.maxMintableComponents = maxMintableComponents
			self.totalMintedComponents = SportvatarTemplate.getTotalMintedComponents(id: id)!
			self.lastComponentMintedAt = SportvatarTemplate.getLastComponentMintedAt(id: id)!
		}
	}
	
	// Get all the Component Templates from the account. 
	// We hide the SVG field because it might be too big to execute in a script
	access(all)
	fun getTemplates(): [TemplateData]{ 
		var templateData: [TemplateData] = []
		if let templateCollection =
			self.account.capabilities.borrow<&SportvatarTemplate.Collection>(SportvatarTemplate.CollectionPublicPath){
			for id in templateCollection.getIDs(){ 
				var template = templateCollection.borrowTemplate(id: id)
				templateData.append(TemplateData(id: id, name: (template!).name, description: (template!).description, series: (template!).series, layer: (template!).layer, metadata: (template!).metadata, rarity: (template!).rarity, sport: (template!).sport, svg: nil, maxMintableComponents: (template!).maxMintableComponents))
			}
		}
		return templateData
	}
	
	// Get all the Series from the account.
	// We hide the SVG field because it might be too big to execute in a script
	access(all)
	fun getSeriesAll(): [SeriesData]{ 
		var seriesData: [SeriesData] = []
		if let templateCollection =
			self.account.capabilities.borrow<&SportvatarTemplate.Collection>(SportvatarTemplate.CollectionPublicPath){
			for id in templateCollection.getSeriesIDs(){ 
				var series = templateCollection.borrowSeries(id: id)
				seriesData.append(SeriesData(id: id, name: (series!).name, description: (series!).description, svgPrefix: (series!).svgPrefix, svgSuffix: (series!).svgSuffix, layers: (series!).layers, colors: (series!).colors, metadata: (series!).metadata, maxMintable: (series!).maxMintable))
			}
		}
		return seriesData
	}
	
	// Gets a specific Template from its ID
	access(all)
	fun getTemplate(id: UInt64): TemplateData?{ 
		if let templateCollection =
			self.account.capabilities.borrow<&SportvatarTemplate.Collection>(SportvatarTemplate.CollectionPublicPath){
			if let template = templateCollection.borrowTemplate(id: id){ 
				return TemplateData(id: id, name: (template!).name, description: (template!).description, series: (template!).series, layer: (template!).layer, metadata: (template!).metadata, rarity: (template!).rarity, sport: (template!).sport, svg: (template!).svg, maxMintableComponents: (template!).maxMintableComponents)
			}
		}
		return nil
	}
	
	// Gets the SVG of a specific Template from its ID
	access(all)
	fun getTemplateSvg(id: UInt64): String?{ 
		if let templateCollection =
			self.account.capabilities.borrow<&SportvatarTemplate.Collection>(SportvatarTemplate.CollectionPublicPath){
			if let template = templateCollection.borrowTemplate(id: id){ 
				return (template!).svg
			}
		}
		return nil
	}
	
	// Gets a specific Series from its ID
	access(all)
	fun getSeries(id: UInt64): SeriesData?{ 
		if let templateCollection =
			self.account.capabilities.borrow<&SportvatarTemplate.Collection>(SportvatarTemplate.CollectionPublicPath){
			if let series = templateCollection.borrowSeries(id: id){ 
				return SeriesData(id: id, name: (series!).name, description: (series!).description, svgPrefix: (series!).svgPrefix, svgSuffix: (series!).svgSuffix, layers: (series!).layers, colors: (series!).colors, metadata: (series!).metadata, maxMintable: (series!).maxMintable)
			}
		}
		return nil
	}
	
	access(all)
	fun isCollectibleLayerAccessory(layer: UInt32, series: UInt64): Bool{ 
		let series = SportvatarTemplate.getSeries(id: series)!
		if let layer = series.layers[layer]{ 
			if layer.isAccessory{ 
				return true
			}
		}
		return false
	}
	
	// Returns the amount of minted Components for a specific Template
	access(all)
	fun getTotalMintedComponents(id: UInt64): UInt64?{ 
		return SportvatarTemplate.totalMintedComponents[id]
	}
	
	// Returns the amount of minted Collectibles for a specific Series
	access(all)
	fun getTotalMintedCollectibles(series: UInt64): UInt64?{ 
		return SportvatarTemplate.totalMintedCollectibles[series]
	}
	
	// Returns the timestamp of the last time a Component for a specific Template was minted
	access(all)
	fun getLastComponentMintedAt(id: UInt64): UFix64?{ 
		return SportvatarTemplate.lastComponentMintedAt[id]
	}
	
	// This function is used within the contract to set the new counter for each Template
	access(account)
	fun setTotalMintedComponents(id: UInt64, value: UInt64){ 
		SportvatarTemplate.totalMintedComponents[id] = value
	}
	
	// This function is used within the contract to set the new counter for each Template
	access(account)
	fun increaseTotalMintedComponents(id: UInt64){ 
		let totMintedComponents: UInt64? = SportvatarTemplate.totalMintedComponents[id]
		if totMintedComponents != nil{ 
			SportvatarTemplate.totalMintedComponents[id] = totMintedComponents! + UInt64(1)
		}
	}
	
	// This function is used within the contract to set the new counter for each Series
	access(account)
	fun setTotalMintedCollectibles(series: UInt64, value: UInt64){ 
		SportvatarTemplate.totalMintedCollectibles[series] = value
	}
	
	// This function is used within the contract to set the new counter for each Template
	access(account)
	fun increaseTotalMintedCollectibles(series: UInt64){ 
		let totMintedCollectibles: UInt64? = SportvatarTemplate.totalMintedCollectibles[series]
		if totMintedCollectibles != nil{ 
			SportvatarTemplate.totalMintedCollectibles[series] = totMintedCollectibles! + UInt64(1)
		}
	}
	
	// This function is used within the contract to set the timestamp 
	// when a Component for a specific Template was minted
	access(account)
	fun setLastComponentMintedAt(id: UInt64, value: UFix64){ 
		SportvatarTemplate.lastComponentMintedAt[id] = value
	}
	
	access(account)
	fun createTemplate(
		name: String,
		description: String,
		series: UInt64,
		layer: UInt32,
		metadata:{ 
			String: String
		},
		rarity: String,
		sport: String,
		svg: String,
		maxMintableComponents: UInt64
	): @SportvatarTemplate.Template{ 
		var newTemplate <-
			create Template(
				name: name,
				description: description,
				series: series,
				layer: layer,
				metadata: metadata,
				rarity: rarity,
				sport: sport,
				svg: svg,
				maxMintableComponents: maxMintableComponents
			)
		
		// Emits the Created event to notify about the new Template
		emit Created(
			id: newTemplate.id,
			name: newTemplate.name,
			series: newTemplate.series,
			layer: newTemplate.layer,
			maxMintableComponents: newTemplate.maxMintableComponents
		)
		
		// Set the counter for the minted Components of this Template to 0
		SportvatarTemplate.setTotalMintedComponents(id: newTemplate.id, value: UInt64(0))
		SportvatarTemplate.setLastComponentMintedAt(id: newTemplate.id, value: UFix64(0))
		return <-newTemplate
	}
	
	access(account)
	fun createSeries(
		name: String,
		description: String,
		svgPrefix: String,
		svgSuffix: String,
		layers:{ 
			UInt32: Layer
		},
		colors:{ 
			UInt32: String
		},
		metadata:{ 
			String: String
		},
		maxMintable: UInt64
	): @SportvatarTemplate.Series{ 
		var newSeries <-
			create Series(
				name: name,
				description: description,
				svgPrefix: svgPrefix,
				svgSuffix: svgSuffix,
				layers: layers,
				colors: colors,
				metadata: metadata,
				maxMintable: maxMintable
			)
		
		// Emits the Created event to notify about the new Template
		emit CreatedSeries(
			id: newSeries.id,
			name: newSeries.name,
			maxMintable: newSeries.maxMintable
		)
		
		// Set the counter for the minted Collectibles of this Series to 0
		SportvatarTemplate.setTotalMintedCollectibles(series: newSeries.id, value: UInt64(0))
		return <-newSeries
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	// Restrictions (2026-11 incident)
	//
	// Admin-managed registry of restricted addresses and restricted NFT ids. The NFT, marketplace, inbox and crafting
	// contracts of this account consult it:
	//   - a restricted id can't be withdrawn, sold, listed, used for crafting / building / upgrading,
	//     and can only be deposited into this contract account itself;
	//   - a restricted address can't withdraw, sell, claim or receive this account's NFTs / rewards.
	//
	// Why it lives here: Flow mainnet does not accept NEW contract deployments without allow-listing, and an
	// in-place update can't add fields to an existing contract. So this was added in an update as new nested types
	// and functions, and the state is a SportvatarTemplate.RestrictionsRegistry resource saved in this contract account's
	// storage at /storage/SportvatarRestrictionsRegistry (created by setupRestrictionsRegistry, called from an admin transaction).
	// No registry = nothing is restricted.
	//
	// Every change emits an event. Restrictions can be removed again by the admin (to fix mistakes).
	// Status is public: isRestricted / getRestrictionStatus, and the NFT contracts add a "restricted" trait to their
	// metadata.
	// ----------------------------------------------------------------------------------------------------------------

	access(all) entitlement RestrictionsManage

	access(all) event AddressRestricted(address: Address, reason: String)
	access(all) event AddressUnrestricted(address: Address)
	access(all) event IDsRestricted(nftType: String, ids: [UInt64], reason: String)
	access(all) event IDsUnrestricted(nftType: String, ids: [UInt64])

	access(all) struct RestrictionStatus {
		access(all) let nftType: String
		access(all) let id: UInt64
		access(all) let idRestricted: Bool
		access(all) let ownerRestricted: Bool
		access(all) let reason: String?

		view init(nftType: String, id: UInt64, idRestricted: Bool, ownerRestricted: Bool, reason: String?) {
			self.nftType = nftType
			self.id = id
			self.idRestricted = idRestricted
			self.ownerRestricted = ownerRestricted
			self.reason = reason
		}
	}

	// Held only in this contract account's storage. Mutators need the RestrictionsManage entitlement, which only the
	// account itself can obtain (storage borrow); no capability to it is ever issued.
	access(all) resource RestrictionsRegistry {

		// address -> reason
		access(self) let restrictedAddresses: {Address: String}

		// NFT type identifier -> (id -> reason)
		access(self) let restrictedIDs: {String: {UInt64: String}}

		access(all) view fun getAddressReason(_ address: Address): String? {
			return self.restrictedAddresses[address]
		}

		access(all) view fun getIDReason(nftType: String, id: UInt64): String? {
			if let ids = &self.restrictedIDs[nftType] as &{UInt64: String}? {
				return ids[id]
			}
			return nil
		}

		access(all) view fun getRestrictedAddresses(): [Address] {
			return self.restrictedAddresses.keys
		}

		access(all) view fun getRestrictedTypes(): [String] {
			return self.restrictedIDs.keys
		}

		access(all) view fun getRestrictedIDCount(nftType: String): Int {
			if let ids = &self.restrictedIDs[nftType] as &{UInt64: String}? {
				return ids.length
			}
			return 0
		}

		access(all) view fun getRestrictedIDs(nftType: String, from: Int, upTo: Int): [UInt64] {
			if let ids = &self.restrictedIDs[nftType] as &{UInt64: String}? {
				let keys = ids.keys
				let end = upTo < keys.length ? upTo : keys.length
				if from >= end {
					return []
				}
				return keys.slice(from: from, upTo: end)
			}
			return []
		}

		access(RestrictionsManage) fun restrictAddress(_ address: Address, reason: String) {
			self.restrictedAddresses[address] = reason
			emit AddressRestricted(address: address, reason: reason)
		}

		access(RestrictionsManage) fun unrestrictAddress(_ address: Address) {
			self.restrictedAddresses.remove(key: address)
			emit AddressUnrestricted(address: address)
		}

		// Batches: keep ids.length <= 500 per call (event size / computation).
		access(RestrictionsManage) fun restrictIDs(nftType: Type, ids: [UInt64], reason: String) {
			pre {
				ids.length <= 500: "Max 500 ids per call"
			}
			let key = nftType.identifier
			if self.restrictedIDs[key] == nil {
				self.restrictedIDs[key] = {}
			}
			let map = (&self.restrictedIDs[key] as auth(Mutate) &{UInt64: String}?)!
			for id in ids {
				map[id] = reason
			}
			emit IDsRestricted(nftType: key, ids: ids, reason: reason)
		}

		access(RestrictionsManage) fun unrestrictIDs(nftType: Type, ids: [UInt64]) {
			pre {
				ids.length <= 500: "Max 500 ids per call"
			}
			let key = nftType.identifier
			if let map = &self.restrictedIDs[key] as auth(Mutate) &{UInt64: String}? {
				for id in ids {
					map.remove(key: id)
				}
			}
			emit IDsUnrestricted(nftType: key, ids: ids)
		}

		init() {
			self.restrictedAddresses = {}
			self.restrictedIDs = {}
		}
	}

	access(all) view fun getRestrictionsRegistryStoragePath(): StoragePath {
		return /storage/SportvatarRestrictionsRegistry
	}

	access(contract) view fun borrowRestrictionsRegistry(): &RestrictionsRegistry? {
		return self.account.storage.borrow<&RestrictionsRegistry>(from: /storage/SportvatarRestrictionsRegistry)
	}

	// True once setupRestrictionsRegistry has been run by the contract account.
	access(all) view fun isRestrictionsRegistrySetUp(): Bool {
		return self.borrowRestrictionsRegistry() != nil
	}

	// Creates the registry in this contract account's storage. Only the contract account can call it with effect
	// (it must pass a reference to its own account). Idempotent.
	access(all) fun setupRestrictionsRegistry(account: auth(Storage) &Account) {
		pre {
			account.address == self.account.address: "Only the contract account can set up the restrictions registry"
		}
		if account.storage.type(at: /storage/SportvatarRestrictionsRegistry) == nil {
			account.storage.save(<-create RestrictionsRegistry(), to: /storage/SportvatarRestrictionsRegistry)
		}
	}

	// ---- public, read-only views ----

	access(all) view fun isAddressRestricted(_ address: Address?): Bool {
		if address == nil {
			return false
		}
		if let registry = self.borrowRestrictionsRegistry() {
			return registry.getAddressReason(address!) != nil
		}
		return false
	}

	access(all) view fun isIDRestricted(nftType: Type, id: UInt64): Bool {
		if let registry = self.borrowRestrictionsRegistry() {
			return registry.getIDReason(nftType: nftType.identifier, id: id) != nil
		}
		return false
	}

	// True if the id is restricted or the given holder address is restricted.
	access(all) view fun isRestricted(nftType: Type, id: UInt64, owner: Address?): Bool {
		return self.isIDRestricted(nftType: nftType, id: id) || self.isAddressRestricted(owner)
	}

	access(all) view fun getRestrictionStatus(nftType: Type, id: UInt64, owner: Address?): RestrictionStatus {
		var reason: String? = nil
		if let registry = self.borrowRestrictionsRegistry() {
			reason = registry.getIDReason(nftType: nftType.identifier, id: id)
			if reason == nil && owner != nil {
				reason = registry.getAddressReason(owner!)
			}
		}
		return RestrictionStatus(
			nftType: nftType.identifier,
			id: id,
			idRestricted: self.isIDRestricted(nftType: nftType, id: id),
			ownerRestricted: self.isAddressRestricted(owner),
			reason: reason
		)
	}

	access(all) view fun getAddressReason(_ address: Address): String? {
		if let registry = self.borrowRestrictionsRegistry() {
			return registry.getAddressReason(address)
		}
		return nil
	}

	access(all) view fun getRestrictedAddresses(): [Address] {
		if let registry = self.borrowRestrictionsRegistry() {
			return registry.getRestrictedAddresses()
		}
		return []
	}

	access(all) view fun getRestrictedTypes(): [String] {
		if let registry = self.borrowRestrictionsRegistry() {
			return registry.getRestrictedTypes()
		}
		return []
	}

	access(all) view fun getRestrictedIDCount(nftType: Type): Int {
		if let registry = self.borrowRestrictionsRegistry() {
			return registry.getRestrictedIDCount(nftType: nftType.identifier)
		}
		return 0
	}

	// Paged list (ids are returned in dictionary order).
	access(all) view fun getRestrictedIDs(nftType: Type, from: Int, upTo: Int): [UInt64] {
		if let registry = self.borrowRestrictionsRegistry() {
			return registry.getRestrictedIDs(nftType: nftType.identifier, from: from, upTo: upTo)
		}
		return []
	}

	// ---- checks used by the NFT / marketplace / crafting contracts (they panic) ----

	// Withdrawing / transferring / listing / selling an NFT held by `owner`.
	access(all) view fun assertTransferAllowed(nftType: Type, id: UInt64, owner: Address?) {
		pre {
			!self.isIDRestricted(nftType: nftType, id: id): "Restricted: this NFT is restricted and can't be transferred, sold or used"
			!self.isAddressRestricted(owner): "Restricted: the holder address is restricted"
		}
	}

	// Depositing an NFT into a collection owned by `to`.
	// Restricted ids may only be deposited into this contract account (or into a collection that is not stored yet).
	access(all) view fun assertDepositAllowed(nftType: Type, id: UInt64, to: Address?) {
		pre {
			!self.isAddressRestricted(to): "Restricted: the recipient address is restricted"
			!self.isIDRestricted(nftType: nftType, id: id) || to == nil || to == self.account.address: "Restricted: this NFT is restricted and can't be deposited here"
		}
	}

	// Using an NFT as input of crafting / building / upgrading / equipping.
	access(all) view fun assertUsable(nftType: Type, id: UInt64) {
		pre {
			!self.isIDRestricted(nftType: nftType, id: id): "Restricted: this NFT is restricted and can't be used"
		}
	}

	// An address that wants to claim / receive rewards or act as buyer / seller.
	access(all) view fun assertAddressAllowed(_ address: Address?) {
		pre {
			!self.isAddressRestricted(address): "Restricted: this address is restricted"
		}
	}

	// ---------------------------------------------------------------------------------------------- end Restrictions

	init(){ 
		self.CollectionPublicPath = /public/SportvatarTemplateCollection
		self.CollectionStoragePath = /storage/SportvatarTemplateCollection
		
		// Initialize the total supply
		self.totalSupply = 0
		self.totalSeriesSupply = 0
		self.totalMintedComponents ={} 
		self.totalMintedCollectibles ={} 
		self.lastComponentMintedAt ={} 
		self.account.storage.save<@SportvatarTemplate.Collection>(
			<-SportvatarTemplate.createEmptyCollection(),
			to: SportvatarTemplate.CollectionStoragePath
		)
		var capability_1 =
			self.account.capabilities.storage.issue<&SportvatarTemplate.Collection>(
				SportvatarTemplate.CollectionStoragePath
			)
		self.account.capabilities.publish(capability_1, at: SportvatarTemplate.CollectionPublicPath)
		emit ContractInitialized()
	}
}
