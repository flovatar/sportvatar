import "FungibleToken"
import "ViewResolver"
import "EVM"
import "CrossVMMetadataViews"
import "NonFungibleToken"
import "FlowToken"
import "SportvatarTemplate"
import "MetadataViews"

/*

 This contract defines the Sportvatar Dust Accessory NFT and the Collection to manage them.
 Components are linked to a specific Template that will ultimately contain the SVG and all the other metadata

 */

access(all)
contract Sportbit: NonFungibleToken{ 
	access(all)
	let CollectionStoragePath: StoragePath
	
	access(all)
	let CollectionPublicPath: PublicPath
	
	// Counter for all the Components ever minted
	access(all)
	var totalSupply: UInt64
	
	// Standard events that will be emitted
	access(all)
	event ContractInitialized()
	
	access(all)
	event Withdraw(id: UInt64, from: Address?)
	
	access(all)
	event Deposit(id: UInt64, to: Address?)
	
	access(all)
	event Created(id: UInt64, templateId: UInt64, mint: UInt64)
	
	access(all)
	event Destroyed(id: UInt64, templateId: UInt64)
	
	// The public interface provides all the basic informations about
	// the Component and also the Template ID associated with it.
	access(all)
	resource interface Public{ 
		access(all)
		let id: UInt64
		
		access(all)
		let templateId: UInt64
		
		access(all)
		let mint: UInt64
		
		access(all)
		fun getTemplate(): SportvatarTemplate.TemplateData
		
		access(all)
		fun getSvg(): String
		
		access(all)
		fun getSeries(): UInt64
		
		access(all)
		fun getRarity(): String
		
		access(all)
		fun getSport(): String
		
		access(all)
		fun getMetadata(): &{ String: String}
		
		access(all)
		fun getLayer(): UInt32
		
		access(all)
		fun getTotalMinted(): UInt64
		
		//these three are added because I think they will be in the standard. At least Dieter thinks it will be needed
		access(all)
		let name: String
		
		access(all)
		let description: String
		
		access(all)
		let schema: String?
	}
	
	// The NFT resource that implements the Public interface as well
	access(all)
	resource NFT: NonFungibleToken.NFT, Public, ViewResolver.Resolver{ 
		access(all)
		let id: UInt64
		
		access(all)
		let templateId: UInt64
		
		access(all)
		let mint: UInt64
		
		access(all)
		let name: String
		
		access(all)
		let description: String
		
		access(all)
		let schema: String?
		
		// Initiates the NFT from a Template ID.
		init(templateId: UInt64){ 
			Sportbit.totalSupply = Sportbit.totalSupply + UInt64(1)
			let template = SportvatarTemplate.getTemplate(id: templateId)!
			self.id = Sportbit.totalSupply
			self.templateId = templateId
			self.mint = SportvatarTemplate.getTotalMintedComponents(id: templateId)! + UInt64(1)
			self.name = template.name
			self.description = template.description
			self.schema = nil
			
			// Increments the counter and stores the timestamp
			SportvatarTemplate.setTotalMintedComponents(id: templateId, value: self.mint)
			SportvatarTemplate.setLastComponentMintedAt(id: templateId, value: getCurrentBlock().timestamp)
		}
		
		access(all)
		fun getID(): UInt64{ 
			return self.id
		}
		
		// Returns the Template associated to the current Component
		access(all)
		fun getTemplate(): SportvatarTemplate.TemplateData{ 
			return SportvatarTemplate.getTemplate(id: self.templateId)!
		}
		
		// Gets the SVG from the parent Template
		access(all)
		fun getSvg(): String{ 
			return self.getTemplate().svg!
		}
		
		// Gets the series number from the parent Template
		access(all)
		fun getSeries(): UInt64{ 
			return self.getTemplate().series
		}
		
		// Gets the rarity from the parent Template
		access(all)
		fun getRarity(): String{ 
			return self.getTemplate().rarity
		}
		
		access(all)
		fun getMetadata(): &{ String: String}{
			return self.getTemplate().metadata
		}
		
		access(all)
		fun getLayer(): UInt32{ 
			return self.getTemplate().layer
		}
		
		access(all)
		fun getSport(): String{ 
			return self.getTemplate().sport
		}
		
		access(all)
		fun getTotalMinted(): UInt64{ 
			return self.getTemplate().totalMintedComponents
		}
		
		// Emit a Destroyed event when it will be burned to create a Sportvatar
		// This will help to keep track of how many Components are still
		// available on the market.
		access(all)
		view fun getViews(): [Type]{ 
			let views: [Type] = [
				Type<MetadataViews.NFTCollectionData>(),
				Type<MetadataViews.NFTCollectionDisplay>(),
				Type<MetadataViews.Display>(),
				Type<MetadataViews.Royalties>(),
				Type<MetadataViews.Editions>(),
				Type<MetadataViews.ExternalURL>(),
				Type<MetadataViews.Serial>(),
				Type<MetadataViews.Traits>(),
				Type<MetadataViews.Rarity>(),
				Type<MetadataViews.EVMBridgedMetadata>()
			]
			return Sportbit.hasEVMPointer() ? views.concat([Type<CrossVMMetadataViews.EVMPointer>()]) : views
		}
		
		access(all)
		fun resolveView(_ type: Type): AnyStruct?{ 
			if type == Type<MetadataViews.ExternalURL>(){ 
				return MetadataViews.ExternalURL("https://sportvatar.com")
			}
			if type == Type<MetadataViews.Royalties>(){ 
				let royalties: [MetadataViews.Royalty] = []
				royalties.append(MetadataViews.Royalty(receiver: Sportbit.account.capabilities.get<&FlowToken.Vault>(/public/flowTokenReceiver), cut: 0.05, description: "Sportvatar Royalty"))
				return MetadataViews.Royalties(royalties)
			}
			if type == Type<MetadataViews.Serial>(){ 
				return MetadataViews.Serial(self.id)
			}
			if type == Type<MetadataViews.Editions>(){ 
				let componentTemplate: SportvatarTemplate.TemplateData = self.getTemplate()
				var maxMintable: UInt64 = componentTemplate.maxMintableComponents
				if maxMintable == UInt64(0){ 
					maxMintable = UInt64(999999)
				}
				let editionInfo = MetadataViews.Edition(name: "Sportvatar Accessory", number: self.mint, max: maxMintable)
				let editionList: [MetadataViews.Edition] = [editionInfo]
				return MetadataViews.Editions(editionList)
			}
			if type == Type<MetadataViews.NFTCollectionDisplay>(){ 
				let mediaSquare = MetadataViews.Media(file: MetadataViews.HTTPFile(url: "https://images.sportvatar.com/logo.svg"), mediaType: "image/svg+xml")
				let mediaBanner = MetadataViews.Media(file: MetadataViews.HTTPFile(url: "https://images.sportvatar.com/logo-horizontal.svg"), mediaType: "image/svg+xml")
				return MetadataViews.NFTCollectionDisplay(name: "Sportvatar Accessory", description: "The Sportvatar Accessories allow you customize and make your beloved Sportvatar even more unique and exclusive.", externalURL: MetadataViews.ExternalURL("https://sportvatar.com"), squareImage: mediaSquare, bannerImage: mediaBanner, socials:{ "discord": MetadataViews.ExternalURL("https://discord.gg/sportvatar"), "twitter": MetadataViews.ExternalURL("https://twitter.com/sportvatar"), "instagram": MetadataViews.ExternalURL("https://instagram.com/sportvatar_nft"), "tiktok": MetadataViews.ExternalURL("https://www.tiktok.com/@sportvatar")})
			}
			if type == Type<MetadataViews.Display>(){ 
				return MetadataViews.Display(name: self.name, description: self.description, thumbnail: MetadataViews.HTTPFile(url: "https://sportvatar.com/api/image/template/".concat(self.templateId.toString())))
			}
			if type == Type<MetadataViews.Traits>(){ 
				let traits: [MetadataViews.Trait] = []
				let template = self.getTemplate()
				let trait = MetadataViews.Trait(name: "Name", value: template.name, displayType: "String", rarity: MetadataViews.Rarity(score: nil, max: nil, description: template.rarity))
				traits.append(trait)
				if SportvatarTemplate.isRestricted(nftType: Type<@Sportbit.NFT>(), id: self.id, owner: self.owner?.address) {
					traits.append(MetadataViews.Trait(name: "restricted", value: true, displayType: "Boolean", rarity: nil))
				}
				return MetadataViews.Traits(traits)
			}
			if type == Type<MetadataViews.Rarity>(){ 
				let template = self.getTemplate()
				return MetadataViews.Rarity(score: nil, max: nil, description: template.rarity)
			}
			if type == Type<MetadataViews.EVMBridgedMetadata>(){ 
				let contractLevel = Sportbit.resolveContractView(resourceType: nil, viewType: Type<MetadataViews.EVMBridgedMetadata>()) as! MetadataViews.EVMBridgedMetadata?
					?? panic("Could not resolve contract-level EVMBridgedMetadata")
				// tokenURI = live ERC-721 JSON (name, description, image, attributes) served by the project API (EVM-A 2026-11)
				return MetadataViews.EVMBridgedMetadata(name: contractLevel.name, symbol: contractLevel.symbol, uri: MetadataViews.URI(baseURI: Sportbit.getEVMHost().concat("/sportbits/json/"), value: self.id.toString()))
			}
			if type == Type<CrossVMMetadataViews.EVMPointer>(){ 
				return Sportbit.getEVMPointer()
			}
			if type == Type<MetadataViews.NFTCollectionData>(){ 
				return MetadataViews.NFTCollectionData(storagePath: Sportbit.CollectionStoragePath, publicPath: Sportbit.CollectionPublicPath, publicCollection: Type<&Sportbit.Collection>(), publicLinkedType: Type<&Sportbit.Collection>(), createEmptyCollectionFunction: fun (): @{NonFungibleToken.Collection}{ 
						return <-Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>())
					})
			}
			return nil
		}






		access(all)
		fun createEmptyCollection(): @{NonFungibleToken.Collection}{ 
			return <-create Collection()
		}
	}
	
	// Standard NFT collectionPublic interface that can also borrowAccessory as the correct type
	access(all)
	resource interface CollectionPublic{ 
		access(all)
		fun deposit(token: @{NonFungibleToken.NFT})
		
		access(all)
		fun getIDs(): [UInt64]
		
		access(all)
		view fun borrowNFT(_ id: UInt64): &{NonFungibleToken.NFT}?
		
		access(all)
		fun borrowAccessory(id: UInt64): &Sportbit.NFT?{ 
			// If the result isn't nil, the id of the returned reference
			// should be the same as the argument to the function
			post{ 
				result == nil || result?.id == id:
					"Cannot borrow Component reference: The ID of the returned reference is incorrect"
			}
		}
		
		access(all)
		fun borrowSportvatar(id: UInt64): &Sportbit.NFT?{ 
			// If the result isn't nil, the id of the returned reference
			// should be the same as the argument to the function
			post{ 
				result == nil || result?.id == id:
					"Cannot borrow Component reference: The ID of the returned reference is incorrect"
			}
		}
	}
	
	// Main Collection to manage all the Components NFT
	access(all)
	resource Collection: CollectionPublic, NonFungibleToken.Provider, NonFungibleToken.Receiver, NonFungibleToken.Collection, NonFungibleToken.CollectionPublic, ViewResolver.ResolverCollection{ 
		// dictionary of NFT conforming tokens
		// NFT is a resource type with an `UInt64` ID field
		access(all)
		var ownedNFTs: @{UInt64:{ NonFungibleToken.NFT}}
		
		init(){ 
			self.ownedNFTs <-{} 
		}
		
		// withdraw removes an NFT from the collection and moves it to the caller
		access(NonFungibleToken.Withdraw)
		fun withdraw(withdrawID: UInt64): @{NonFungibleToken.NFT}{ 
			SportvatarTemplate.assertTransferAllowed(nftType: Type<@Sportbit.NFT>(), id: withdrawID, owner: self.owner?.address)
			let token <- self.ownedNFTs.remove(key: withdrawID) ?? panic("missing NFT")
			emit Withdraw(id: token.id, from: self.owner?.address)
			return <-token
		}
		
		// deposit takes a NFT and adds it to the collections dictionary
		// and adds the ID to the id array
		access(all)
		fun deposit(token: @{NonFungibleToken.NFT}){ 
			let token <- token as! @Sportbit.NFT
			let id: UInt64 = token.id
			SportvatarTemplate.assertDepositAllowed(nftType: Type<@Sportbit.NFT>(), id: id, to: self.owner?.address)
			
			// add the new token to the dictionary which removes the old one
			let oldToken <- self.ownedNFTs[id] <- token
			emit Deposit(id: id, to: self.owner?.address)
			destroy oldToken
		}
		
		// getIDs returns an array of the IDs that are in the collection
		access(all)
		view fun getIDs(): [UInt64]{ 
			return self.ownedNFTs.keys
		}
		
		// borrowNFT gets a reference to an NFT in the collection
		// so that the caller can read its metadata and call its methods
		access(all)
		view fun borrowNFT(_ id: UInt64): &{NonFungibleToken.NFT}?{ 
			return (&self.ownedNFTs[id] as &{NonFungibleToken.NFT}?)!
		}
		
		// borrowAccessory returns a borrowed reference to a Sportbit
		// so that the caller can read data and call methods from it.
		access(all)
		fun borrowAccessory(id: UInt64): &Sportbit.NFT?{ 
			if self.ownedNFTs[id] != nil{ 
				let ref = (&self.ownedNFTs[id] as &{NonFungibleToken.NFT}?)!
				return ref as! &Sportbit.NFT
			} else{ 
				return nil
			}
		}
		
		access(all)
		fun borrowSportvatar(id: UInt64): &Sportbit.NFT?{ 
			return self.borrowAccessory(id: id)
		}
		
		access(all)
		view fun borrowViewResolver(id: UInt64): &{ViewResolver.Resolver}?{ 
			pre{ 
				self.ownedNFTs[id] != nil:
					"NFT does not exist"
			}
			let nft = (&self.ownedNFTs[id] as &{NonFungibleToken.NFT}?)!
			let componentNFT = nft as! &Sportbit.NFT
			return componentNFT as &{ViewResolver.Resolver}
		}
		
		access(all)
		view fun getSupportedNFTTypes():{ Type: Bool}{ 
			return {Type<@Sportbit.NFT>(): true}
		}
		
		access(all)
		view fun isSupportedNFTType(type: Type): Bool{ 
			return type == Type<@Sportbit.NFT>()
		}
		
		access(all)
		fun createEmptyCollection(): @{NonFungibleToken.Collection}{ 
			return <-create Collection()
		}
	}





	// Restriction status of a Sportbit (see SportvatarTemplate restrictions)
	access(all)
	view fun isRestricted(id: UInt64, owner: Address?): Bool {
		return SportvatarTemplate.isRestricted(nftType: Type<@Sportbit.NFT>(), id: id, owner: owner)
	}

	access(all)
	view fun getContractViews(resourceType: Type?): [Type] {
		let views: [Type] = [
			Type<MetadataViews.NFTCollectionData>(),
			Type<MetadataViews.NFTCollectionDisplay>(),
			Type<MetadataViews.EVMBridgedMetadata>()
		]
		return Sportbit.hasEVMPointer() ? views.concat([Type<CrossVMMetadataViews.EVMPointer>()]) : views
	}
	// ---- EVM-A (2026-11): cross-VM / Flow EVM metadata helpers. Functions only, no new state. ----

	/// Web host serving the ERC-721 metadata JSON for this network (identical code on testnet and mainnet).
	access(all)
	view fun getEVMHost(): String {
		return self.account.address == 0xca5c31c0c03e11be ? "https://sportvatar.com" : "https://testnet.sportvatar.com"
	}

	/// Account-wide cross-VM config written by an admin tx: {NFT type identifier: project ERC-721 address}.
	access(all)
	view fun getCrossVMConfigPath(): StoragePath {
		return /storage/sportvatarCrossVMConfig
	}

	/// True once the account stored a project ERC-721 address for this NFT type (usable in view functions).
	access(all)
	view fun hasEVMPointer(): Bool {
		if let cfg = self.account.storage.borrow<&{String: EVM.EVMAddress}>(from: /storage/sportvatarCrossVMConfig) {
			return cfg[Type<@Sportbit.NFT>().identifier] != nil
		}
		return false
	}

	/// FLIP-318 EVMPointer to the project-owned ERC-721 (Cadence-native). nil until the account stored one for
	/// this NFT type, so the permissionless bridge path keeps working unchanged until then.
	access(all)
	fun getEVMPointer(): CrossVMMetadataViews.EVMPointer? {
		if let cfg = self.account.storage.copy<{String: EVM.EVMAddress}>(from: /storage/sportvatarCrossVMConfig) {
			if let evmAddress = cfg[Type<@Sportbit.NFT>().identifier] {
				return CrossVMMetadataViews.EVMPointer(
					cadenceType: Type<@Sportbit.NFT>(),
					cadenceContractAddress: self.account.address,
					evmContractAddress: evmAddress,
					nativeVM: CrossVMMetadataViews.VM.Cadence
				)
			}
		}
		return nil
	}



	access(all) fun resolveContractView(resourceType: Type?, viewType: Type): AnyStruct? {
		switch viewType {
			case Type<MetadataViews.NFTCollectionData>():
				let collectionData = MetadataViews.NFTCollectionData(
					storagePath: self.CollectionStoragePath,
					publicPath: self.CollectionPublicPath,
					publicCollection: Type<&Sportbit.Collection>(),
					publicLinkedType: Type<&Sportbit.Collection>(),
					createEmptyCollectionFunction: (fun(): @{NonFungibleToken.Collection} {
						return <-Sportbit.createEmptyCollection(nftType: Type<@Sportbit.NFT>())
					})
				)
				return collectionData
			case Type<MetadataViews.NFTCollectionDisplay>():
				let media = MetadataViews.Media(
					file: MetadataViews.HTTPFile(
						url: "https://images.sportvatar.com/logo.svg"
					),
					mediaType: "image/svg+xml"
				)
				let mediaBanner = MetadataViews.Media(
					file: MetadataViews.HTTPFile(
						url: "https://images.sportvatar.com/logo-horizontal.svg"
					),
					mediaType: "image/svg+xml"
				)
				return MetadataViews.NFTCollectionDisplay(
					name: "Sportvatar Accessories Collection",
					description: "The Sportvatar Accessories allow you customize and make your beloved Sportvatar even more unique and exclusive.",
					externalURL: MetadataViews.ExternalURL("https://sportvatar.com"),
					squareImage: media,
					bannerImage: mediaBanner,
					socials: {
						"twitter": MetadataViews.ExternalURL("https://x.com/sportvatar"),
						"discord": MetadataViews.ExternalURL("https://discord.gg/sportvatar"),
						"instagram": MetadataViews.ExternalURL("https://instagram.com/sportvatar"),
						"tiktok": MetadataViews.ExternalURL("https://www.tiktok.com/@sportvatar")
					}
				)
			case Type<CrossVMMetadataViews.EVMPointer>():
				return Sportbit.getEVMPointer()
			case Type<MetadataViews.EVMBridgedMetadata>():
				// Implementing this view gives the project control over how the bridged NFT is represented as an ERC721
				// when bridged to EVM on Flow via the public infrastructure bridge.

				// Compose the contract-level URI. In this case, the contract metadata is located on some HTTP host,
				// but it could be IPFS, S3, a data URL containing the JSON directly, etc.
				return MetadataViews.EVMBridgedMetadata(
					name: "Sportbit",
					symbol: "SPTB",
					uri: MetadataViews.URI(
						baseURI: nil, // contractURI JSON (EVM-A 2026-11)
						value: Sportbit.getEVMHost().concat("/evm/collections/sportbits.json")
					)
				)
		}
		return nil
	}



	
	// public function that anyone can call to create a new empty collection
	access(all)
	fun createEmptyCollection(nftType: Type): @{NonFungibleToken.Collection}{ 
		return <-create Collection()
	}
	
	// This struct is used to send a data representation of the Components
	// when retrieved using the contract helper methods outside the collection.
	access(all)
	struct AccessoryData{ 
		access(all)
		let id: UInt64
		
		access(all)
		let templateId: UInt64
		
		access(all)
		let mint: UInt64
		
		access(all)
		let name: String
		
		access(all)
		let description: String
		
		access(all)
		let rarity: String
		
		access(all)
		let metadata: &{ String: String}
		
		access(all)
		let layer: UInt32
		
		access(all)
		let totalMinted: UInt64
		
		access(all)
		let sport: String
		
		init(id: UInt64, templateId: UInt64, mint: UInt64){ 
			self.id = id
			self.templateId = templateId
			self.mint = mint
			let template = SportvatarTemplate.getTemplate(id: templateId)!
			self.name = template.name
			self.description = template.description
			self.rarity = template.rarity
			self.metadata = template.metadata
			self.layer = template.layer
			self.totalMinted = template.totalMintedComponents
			self.sport = template.sport
		}
	}
	
	// Get the SVG of a specific Sportbit from an account and the ID
	access(all)
	fun getSvgForSportbit(address: Address, id: UInt64): String?{ 
		let account = getAccount(address)
		if let componentCollection = account.capabilities.borrow<&Sportbit.Collection>(Sportbit.CollectionPublicPath){
			return (componentCollection.borrowAccessory(id: id)!).getSvg()
		}
		return nil
	}
	
	// Get a specific Component from an account and the ID as AccessoryData
	access(all)
	fun getSportbit(address: Address, componentId: UInt64): AccessoryData?{ 
		let account = getAccount(address)
		if let componentCollection = account.capabilities.borrow<&Sportbit.Collection>(Sportbit.CollectionPublicPath){
			if let component = componentCollection.borrowAccessory(id: componentId){ 
				return AccessoryData(id: componentId, templateId: (component!).templateId, mint: (component!).mint)
			}
		}
		return nil
	}
	
	// Get an array of all the components in a specific account as AccessoryData
	access(all)
	fun getSportbits(address: Address): [AccessoryData]{ 
		var componentData: [AccessoryData] = []
		let account = getAccount(address)
		if let componentCollection = account.capabilities.borrow<&Sportbit.Collection>(Sportbit.CollectionPublicPath){
			for id in componentCollection.getIDs(){ 
				var component = componentCollection.borrowAccessory(id: id)
				componentData.append(AccessoryData(id: id, templateId: (component!).templateId, mint: (component!).mint))
			}
		}
		return componentData
	}
	
	access(account)
	fun createSportbit(templateId: UInt64): @Sportbit.NFT{ 
		let template: SportvatarTemplate.TemplateData = SportvatarTemplate.getTemplate(id: templateId)!
		let totalMintedComponents: UInt64 = SportvatarTemplate.getTotalMintedComponents(id: templateId)!
		
		// Makes sure that the original minting limit set for each Template has not been reached
		if template.maxMintableComponents > UInt64(0) && totalMintedComponents >= template.maxMintableComponents{ 
			panic("Reached maximum mintable components for this template")
		}
		var newNFT <- create NFT(templateId: templateId)
		emit Created(id: newNFT.id, templateId: templateId, mint: newNFT.mint)
		return <-newNFT
	}
	
	// This method can only be called from another contract in the same account.
	// In Sportbit case it is called from the Sportvatar Admin that is used
	// to administer the components.
	// This function will batch create multiple Components and pass them back as a Collection
	access(account)
	fun batchCreateSportbits(templateId: UInt64, quantity: UInt64): @Collection{ 
		let newCollection <- create Collection()
		var i: UInt64 = 0
		while i < quantity{ 
			newCollection.deposit(token: <-self.createSportbit(templateId: templateId))
			i = i + UInt64(1)
		}
		return <-newCollection
	}
	
	init(){ 
		self.CollectionPublicPath = /public/SportbitCollection
		self.CollectionStoragePath = /storage/SportbitCollection
		
		// Initialize the total supply
		self.totalSupply = UInt64(0)
		self.account.storage.save<@{NonFungibleToken.Collection}>(<-Sportbit.createEmptyCollection(nftType: Type<@Sportbit.Collection>()), to: Sportbit.CollectionStoragePath)
		var capability_1 = self.account.capabilities.storage.issue<&Sportbit.Collection>(Sportbit.CollectionStoragePath)
		self.account.capabilities.publish(capability_1, at: Sportbit.CollectionPublicPath)
		emit ContractInitialized()
	}
}
