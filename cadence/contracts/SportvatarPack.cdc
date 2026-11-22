import "FungibleToken"
import "NonFungibleToken"
import "FlowToken"
import "SportvatarTemplate"
import "Sportbit"
import Crypto
import "DapperUtilityCoin"

/*

 This contract defines the Sportvatar Packs and a Collection to manage them.

 Each Pack will contain one item for each required Component (body, hair, eyes, nose, mouth, clothing),
 and two other Components that are optional (facial hair, accessory, hat, eyeglasses, background).

 Packs will be pre-minted and can be purchased from the contract owner's account by providing a
 verified signature that is different for each Pack (more info in the purchase function).

 Once purchased, packs cannot be re-sold and users will only be able to open them to receive
 the contained Components into their collection.

 */

access(all)
contract SportvatarPack{

    access(all) entitlement WithdrawEnt

	access(all)
	let CollectionStoragePath: StoragePath
	
	access(all)
	let CollectionPublicPath: PublicPath
	
	// Counter for all the Packs ever minted
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
	event Created(id: UInt64, prefix: String)
	
	access(all)
	event Opened(id: UInt64)
	
	access(all)
	event Purchased(id: UInt64)
	
	access(all)
	event Claimed(id: UInt64)
	
	// The public interface contains only the ID and the price of the Pack
	access(all)
	resource interface Public{ 
		access(all)
		let id: UInt64
		
		access(all)
		let price: UFix64
		
		access(all)
		let flameCount: UInt32
		
		access(all)
		let series: UInt32
		
		access(all)
		let name: String
	}
	
	// The Pack resource that implements the Public interface and that contains
	// different Components in a Dictionary
	access(all)
	resource Pack: Public{ 
		access(all)
		let id: UInt64
		
		access(all)
		let price: UFix64
		
		access(all)
		let flameCount: UInt32
		
		access(all)
		let series: UInt32
		
		access(all)
		let name: String
		
		access(account)
		let components: @[Sportbit.NFT]
		
		access(account)
		var randomString: String
		
		// Initializes the Pack with all the Components.
		// It receives also the price and a random String that will signed by
		// the account owner to validate the purchase process.
		init(components: @[Sportbit.NFT], randomString: String, price: UFix64, flameCount: UInt32, series: UInt32, name: String){ 
			
			// Makes sure that if it's set to have a flame component, this one is present in the array
			var flameCountCheck: UInt32 = 0
			if flameCount > 0{ 
				var i: Int = 0
				while i < components.length{ 
					if components[i].name == "Sport Flame" && components[i].getLayer() == UInt32(0){ 
						flameCountCheck = flameCountCheck + 1
					}
					i = i + 1
				}
			}
			if flameCount != flameCountCheck{ 
				panic("There is a mismatch in the Sport Flame count")
			}
			
			// Increments the total supply counter
			SportvatarPack.totalSupply = SportvatarPack.totalSupply + UInt64(1)
			self.id = SportvatarPack.totalSupply
			
			// Moves all the components into the array
			self.components <- []
			while components.length > 0{ 
				self.components.append(<-components.remove(at: 0))
			}
			destroy components
			
			// Sets the randomString text and the price
			self.randomString = randomString
			self.price = price
			self.flameCount = flameCount
			self.series = series
			self.name = name
		}
		
		// This function is used to retrieve the random string to match it
		// against the signature passed during the purchase process
		access(contract)
		fun getRandomString(): String{ 
			return self.randomString
		}
		
		// This function reset the randomString so that after the purchase nobody
		// will be able to re-use the verified signature
		access(contract)
		fun setRandomString(randomString: String){ 
			self.randomString = randomString
		}
		
		// Only callable from this contract (openPack); a Pack's content can't be taken without opening it.
		access(contract)
		fun removeComponent(at: Int): @Sportbit.NFT{ 
			return <-self.components.remove(at: at)
		}
	}
	
	//Pack CollectionPublic interface that allows users to purchase a Pack
	access(all)
	resource interface CollectionPublic{ 
		access(all)
		fun getIDs(): [UInt64]
		
		access(all)
		fun deposit(token: @SportvatarPack.Pack)
		
		access(all)
		fun purchase(
			tokenId: UInt64,
			recipientCap: Capability<&{SportvatarPack.CollectionPublic}>,
			buyTokens: @{FungibleToken.Vault},
			signature: String,
			expectedPrice: UFix64
		)
		
		access(all)
		fun claimForFree(
			tokenId: UInt64,
			recipientCap: Capability<&{SportvatarPack.CollectionPublic}>,
			signature: String
		)
	}
	
	// Main Collection that implements the Public interface and that
	// will handle the purchase transactions
	access(all)
	resource Collection: CollectionPublic{ 
		// Dictionary of all the Packs owned
		access(account)
		let ownedPacks: @{UInt64: SportvatarPack.Pack}
		
		// Capability to send the FLOW tokens to the owner's account
		access(account)
		let ownerVault: Capability<&{FungibleToken.Receiver}>
		
		// Initializes the Collection with the vault receiver capability
		init(ownerVault: Capability<&{FungibleToken.Receiver}>){ 
			self.ownedPacks <-{} 
			self.ownerVault = ownerVault
		}
		
		// getIDs returns an array of the IDs that are in the collection
		access(all)
		fun getIDs(): [UInt64]{ 
			return self.ownedPacks.keys
		}
		
		// deposit takes a Pack and adds it to the collections dictionary
		// and adds the ID to the id array
		access(all)
		fun deposit(token: @SportvatarPack.Pack){ 
			let id: UInt64 = token.id
			SportvatarTemplate.assertDepositAllowed(nftType: Type<@SportvatarPack.Pack>(), id: id, to: self.owner?.address)
			
			// add the new token to the dictionary which removes the old one
			let oldToken <- self.ownedPacks[id] <- token
			emit Deposit(id: id, to: self.owner?.address)
			destroy oldToken
		}
		
		// withdraw removes a Pack from the collection and moves it to the caller
		access(WithdrawEnt)
		fun withdraw(withdrawID: UInt64): @SportvatarPack.Pack{ 
			SportvatarTemplate.assertTransferAllowed(nftType: Type<@SportvatarPack.Pack>(), id: withdrawID, owner: self.owner?.address)
			let token <- self.ownedPacks.remove(key: withdrawID) ?? panic("Missing Pack")
			emit Withdraw(id: token.id, from: self.owner?.address)
			return <-token
		}
		
		// This function allows any Pack owner to open the pack and receive its content
		// into the owner's Component Collection.
		// The pack is destroyed after the Components are delivered.
		access(WithdrawEnt)
		fun openPack(id: UInt64){ 
			
			// Gets the Component Collection Public capability to be able to
			// send there the Components contained in the Pack
			let recipientCap = (self.owner!).capabilities.get<&{Sportbit.CollectionPublic}>(Sportbit.CollectionPublicPath)
			let recipient = recipientCap.borrow()!
			
			// Removed the pack from the collection
			let pack <- self.withdraw(withdrawID: id)
			
			// Removes all the components from the Pack and deposits them to the
			// Component Collection of the owner
			while pack.components.length > 0{ 
				recipient.deposit(token: <-pack.removeComponent(at: 0))
			}
			
			// Emits the event to notify that the pack was opened
			emit Opened(id: pack.id)
			destroy pack
		}
		
		// Gets the price for a specific Pack
		access(account)
		view fun getPrice(id: UInt64): UFix64{ 
			let pack: &SportvatarPack.Pack = (&self.ownedPacks[id] as &SportvatarPack.Pack?)!
			return pack.price
		}
		
		// Gets the random String for a specific Pack
		access(account)
		fun getRandomString(id: UInt64): String{ 
			let pack: &SportvatarPack.Pack = (&self.ownedPacks[id] as &SportvatarPack.Pack?)!
			return pack.getRandomString()
		}
		
		// Sets the random String for a specific Pack
		access(account)
		fun setRandomString(id: UInt64, randomString: String){ 
			let pack: &SportvatarPack.Pack = (&self.ownedPacks[id] as &SportvatarPack.Pack?)!
			pack.setRandomString(randomString: randomString)
		}
		
		// This function provides the ability for anyone to purchase a Pack
		// It receives as parameters the Pack ID, the Pack Collection Public capability to receive the pack,
		// a vault containing the necessary FLOW token, and finally a signature to validate the process.
		// The signature is generated off-chain by the smart contract's owner account using the Crypto library
		// to generate a hash from the original random String contained in each Pack.
		// This will guarantee that the contract owner will be able to decide which user can buy a pack, by
		// providing them the correct signature.
		//
		//
		access(all)
		fun purchase(tokenId: UInt64, recipientCap: Capability<&{SportvatarPack.CollectionPublic}>, buyTokens: @{FungibleToken.Vault}, signature: String, expectedPrice: UFix64){ 
			// v1 signatures (over the pack randomString, not bound to the buyer) are disabled since the 2026-11 code reissue.
			panic("Pack codes v1 are no longer accepted: use SportvatarPack.purchasePack / SportvatarPack.claimFreePack with a v2 authorisation")
		}
		
		// v2: authorisation bound to the recipient address, with expiry and epoch (see the pack verifier section of this contract).
		// Called through the contract-level SportvatarPack.purchasePack.
		access(contract)
		fun purchaseBound(tokenId: UInt64, recipientCap: Capability<&{SportvatarPack.CollectionPublic}>, buyTokens: @{FungibleToken.Vault}, expiry: UInt64, signature: String, expectedPrice: UFix64){ 
			pre{ 
				self.ownedPacks.containsKey(tokenId) == true:
					"Pack not found!"
				self.getPrice(id: tokenId) > UFix64(0.0):
					"This pack is free: use claimFreePack"
				self.getPrice(id: tokenId) <= buyTokens.balance:
					"Not enough tokens to buy the Pack!"
				self.getPrice(id: tokenId) == expectedPrice:
					"Price not set as expected!"
				buyTokens.isInstance(Type<@DapperUtilityCoin.Vault>()):
					"Vault not of the right Token Type"
			}
			SportvatarTemplate.assertAddressAllowed(recipientCap.address)
			SportvatarPack.verifyAuthorisation(kind: "purchase", packId: tokenId, recipient: recipientCap.address, expiry: expiry, signature: signature)
			
			let recipient = recipientCap.borrow() ?? panic("Could not borrow the recipient's pack collection")
			let pack <- self.withdraw(withdrawID: tokenId)
			
			// mainnet F Address: 0x8a86f18e0e05bd9f
			let dapperMarketVault = getAccount(0x8a86f18e0e05bd9f).capabilities.get<&{FungibleToken.Receiver}>(/public/dapperUtilityCoinReceiver)
			let vaultRef = dapperMarketVault.borrow() ?? panic("Could not borrow reference to owner pack vault")
			vaultRef.deposit(from: <-buyTokens)
			
			let packId: UInt64 = pack.id
			pack.setRandomString(randomString: revertibleRandom<UInt64>().toString())
			recipient.deposit(token: <-pack)
			emit Purchased(id: packId)
		}
		
		// This function provides the ability for anyone to claim a Pack for free
		// It receives as parameters the Pack ID, the Pack Collection Public capability to receive the pack and
		// a signature to validate the process.
		// The signature is generated off-chain by the smart contract's owner account using the Crypto library
		// to generate a hash from the original random String contained in each Pack.
		// This will guarantee that the contract owner will be able to decide which user can buy a pack, by
		// providing them the correct signature.
		//
		//
		access(all)
		fun claimForFree(tokenId: UInt64, recipientCap: Capability<&{SportvatarPack.CollectionPublic}>, signature: String){ 
			// v1 codes (signature over the pack randomString, not bound to the recipient) are disabled since the 2026-11 code reissue.
			panic("Pack codes v1 are no longer accepted: use SportvatarPack.purchasePack / SportvatarPack.claimFreePack with a v2 authorisation")
		}
		
		// v2: authorisation bound to the recipient address, with expiry and epoch (see the pack verifier section of this contract).
		// Called through the contract-level SportvatarPack.claimFreePack.
		access(contract)
		fun claimBound(tokenId: UInt64, recipientCap: Capability<&{SportvatarPack.CollectionPublic}>, expiry: UInt64, signature: String){ 
			pre{ 
				self.ownedPacks.containsKey(tokenId) == true:
					"Pack not found!"
				self.getPrice(id: tokenId) == UFix64(0.0):
					"Price not set as expected!"
			}
			SportvatarTemplate.assertAddressAllowed(recipientCap.address)
			SportvatarPack.verifyAuthorisation(kind: "claim", packId: tokenId, recipient: recipientCap.address, expiry: expiry, signature: signature)
			
			let recipient = recipientCap.borrow() ?? panic("Could not borrow the recipient's pack collection")
			let pack <- self.withdraw(withdrawID: tokenId)
			let packId: UInt64 = pack.id
			pack.setRandomString(randomString: revertibleRandom<UInt64>().toString())
			recipient.deposit(token: <-pack)
			emit Claimed(id: packId)
		}
	}
	
	// Free pack claim with a v2 authorisation (signed by the pack back-end for this recipient).
	access(all)
	fun claimFreePack(tokenId: UInt64, recipientCap: Capability<&{SportvatarPack.CollectionPublic}>, expiry: UInt64, signature: String){ 
		let packs = self.account.storage.borrow<auth(WithdrawEnt) &SportvatarPack.Collection>(from: self.CollectionStoragePath) ?? panic("Pack collection not found")
		packs.claimBound(tokenId: tokenId, recipientCap: recipientCap, expiry: expiry, signature: signature)
	}
	
	// Paid pack purchase with a v2 authorisation (signed by the pack back-end for this buyer).
	access(all)
	fun purchasePack(tokenId: UInt64, recipientCap: Capability<&{SportvatarPack.CollectionPublic}>, buyTokens: @{FungibleToken.Vault}, expiry: UInt64, signature: String, expectedPrice: UFix64){ 
		let packs = self.account.storage.borrow<auth(WithdrawEnt) &SportvatarPack.Collection>(from: self.CollectionStoragePath) ?? panic("Pack collection not found")
		packs.purchaseBound(tokenId: tokenId, recipientCap: recipientCap, buyTokens: <-buyTokens, expiry: expiry, signature: signature, expectedPrice: expectedPrice)
	}
	
	// Restriction status of a pack (see SportvatarTemplate restrictions)
	access(all)
	view fun isRestricted(id: UInt64, owner: Address?): Bool {
		return SportvatarTemplate.isRestricted(nftType: Type<@SportvatarPack.Pack>(), id: id, owner: owner)
	}
	
	// public function that anyone can call to create a new empty collection
	access(all)
	fun createEmptyCollection(): @SportvatarPack.Collection{ 
		let ownerVault: Capability<&{FungibleToken.Receiver}> =
			self.account.capabilities.get<&FlowToken.Vault>(/public/flowTokenReceiver)
		return <-create Collection(ownerVault: ownerVault)
	}
	
	// Get all the packs from a specific account
	access(all)
	fun getPacks(address: Address): [UInt64]?{ 
		let account = getAccount(address)
		if let packCollection =
			account.capabilities.borrow<&SportvatarPack.Collection>(self.CollectionPublicPath){
			return packCollection.getIDs()
		}
		return nil
	}
	
	access(all)
	fun checkPackAvailable(id: UInt64): Bool{ 
		if let packCollection =
			self.account.capabilities.borrow<&{SportvatarPack.CollectionPublic}>(SportvatarPack.CollectionPublicPath){
			let packIds: [UInt64] = packCollection.getIDs()
			return packIds.contains(id)
		}
		return false
	}
	
	// This method can only be called from another contract in the same account (The Sportvatar Admin resource)
	// It creates a new pack from a list of Components, the random String and the price.
	// Some Components are required and others are optional
	access(account)
	fun createPack(
		components: @[
			Sportbit.NFT
		],
		randomString: String,
		price: UFix64,
		flameCount: UInt32,
		series: UInt32,
		name: String
	): @SportvatarPack.Pack{ 
		var newPack <-
			create Pack(
				components: <-components,
				randomString: randomString,
				price: price,
				flameCount: flameCount,
				series: series,
				name: name
			)
		
		// Emits an event to notify that a Pack was created.
		// Sends the first 4 digits of the randomString to be able to sync the ID with the off-chain DB
		// that will store also the signatures once they are generated
		emit Created(id: newPack.id, prefix: randomString.slice(from: 0, upTo: 5))
		return <-newPack
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	// Pack codes v2 verifier (2026-11 incident)
	//
	// Verifies pack claim / purchase authorisations issued by the Sportvatar back-end.
	//
	// Message that the back-end signs (UTF-8, no domain tag; the prefix provides the domain separation):
	//
	//     SPORTVATAR-PACK-V2|<kind>|<packId>|<recipient>|<expiry>|<epoch>
	//
	//   kind       "claim" (free pack) or "purchase" (paid pack)
	//   packId     decimal pack id
	//   recipient  the address that will receive the pack, Address.toString() form ("0x" + 16 hex digits)
	//   expiry     unix timestamp (seconds, decimal); the authorisation is rejected after this block time
	//   epoch      current verifier epoch (decimal); bumping it invalidates every outstanding authorisation
	//
	// Compared with version 1 (signature over the pack's randomString, checked against account key 0):
	//   - an authorisation is bound to one recipient, so a leaked code can't be used by anyone else;
	//   - it expires, and all of them can be revoked at once (bumpEpoch) or per pack (revokePacks);
	//   - the signer is a dedicated key held only by the pack back-end, not an account key: it can't sign
	//     transactions. Only its public key is stored here.
	//
	// Why it lives here: Flow mainnet does not accept NEW contract deployments without allow-listing, and an
	// in-place update can't add fields to an existing contract. So the verifier was added in an update as nested
	// types and functions, and its state is a SportvatarPack.PackVerifier resource saved in this contract account's
	// storage at /storage/SportvatarPackVerifier (created by setupPackVerifier, called from an admin transaction).
	// No verifier resource = no signer, claims and purchases disabled: every authorisation is rejected.
	// ----------------------------------------------------------------------------------------------------------------

	access(all) entitlement VerifierManage

	access(all) event SignerSet(publicKey: String, signatureAlgorithm: UInt8, hashAlgorithm: UInt8)
	access(all) event EpochSet(epoch: UInt64)
	access(all) event ClaimsEnabled(enabled: Bool)
	access(all) event PurchasesEnabled(enabled: Bool)
	access(all) event PackRevoked(id: UInt64)
	access(all) event PackUnrevoked(id: UInt64)

	access(all) struct VerifierConfig {
		access(all) let signerPublicKey: String?
		access(all) let signatureAlgorithm: UInt8
		access(all) let hashAlgorithm: UInt8
		access(all) let epoch: UInt64
		access(all) let claimsEnabled: Bool
		access(all) let purchasesEnabled: Bool
		access(all) let revokedPacks: Int

		view init(signerPublicKey: String?, signatureAlgorithm: UInt8, hashAlgorithm: UInt8, epoch: UInt64, claimsEnabled: Bool, purchasesEnabled: Bool, revokedPacks: Int) {
			self.signerPublicKey = signerPublicKey
			self.signatureAlgorithm = signatureAlgorithm
			self.hashAlgorithm = hashAlgorithm
			self.epoch = epoch
			self.claimsEnabled = claimsEnabled
			self.purchasesEnabled = purchasesEnabled
			self.revokedPacks = revokedPacks
		}
	}

	// Held only in this contract account's storage. Mutators need the VerifierManage entitlement, which only the
	// account itself can obtain (storage borrow); no capability to it is ever issued.
	access(all) resource PackVerifier {
		access(self) var signerPublicKey: String?
		access(self) var signatureAlgorithm: UInt8
		access(self) var hashAlgorithm: UInt8
		access(self) var epoch: UInt64
		access(self) var claimsEnabled: Bool
		access(self) var purchasesEnabled: Bool
		access(self) let revokedPacks: {UInt64: Bool}

		access(all) view fun getConfig(): VerifierConfig {
			return VerifierConfig(
				signerPublicKey: self.signerPublicKey,
				signatureAlgorithm: self.signatureAlgorithm,
				hashAlgorithm: self.hashAlgorithm,
				epoch: self.epoch,
				claimsEnabled: self.claimsEnabled,
				purchasesEnabled: self.purchasesEnabled,
				revokedPacks: self.revokedPacks.length
			)
		}

		access(all) view fun getEpoch(): UInt64 {
			return self.epoch
		}

		access(all) view fun isPackRevoked(id: UInt64): Bool {
			return self.revokedPacks[id] ?? false
		}

		// Returns nil if the authorisation is valid, otherwise the reason it is not.
		access(all) fun check(kind: String, packId: UInt64, recipient: Address, expiry: UInt64, signature: String): String? {
			if kind != "claim" && kind != "purchase" {
				return "unknown kind"
			}
			if kind == "claim" && !self.claimsEnabled {
				return "free pack claims are disabled"
			}
			if kind == "purchase" && !self.purchasesEnabled {
				return "pack purchases are disabled"
			}
			if self.signerPublicKey == nil {
				return "no pack signer configured"
			}
			if self.isPackRevoked(id: packId) {
				return "pack authorisation revoked"
			}
			if UFix64(expiry) < getCurrentBlock().timestamp {
				return "pack authorisation expired"
			}
			let key = PublicKey(
				publicKey: self.signerPublicKey!.decodeHex(),
				signatureAlgorithm: SignatureAlgorithm(rawValue: self.signatureAlgorithm)!
			)
			let message = SportvatarPack.buildVerifierMessage(kind: kind, packId: packId, recipient: recipient, expiry: expiry, epoch: self.epoch)
			let valid = key.verify(
				signature: signature.decodeHex(),
				signedData: message.utf8,
				domainSeparationTag: "",
				hashAlgorithm: HashAlgorithm(rawValue: self.hashAlgorithm)!
			)
			if !valid {
				return "invalid pack signature"
			}
			return nil
		}

		// publicKey: hex, 64 bytes (uncompressed X||Y without 04 prefix), e.g. from `flow keys generate`
		// signatureAlgorithm: 1 = ECDSA_P256, 2 = ECDSA_secp256k1; hashAlgorithm: 1 = SHA2_256, 3 = SHA3_256
		access(VerifierManage) fun setSigner(publicKey: String, signatureAlgorithm: UInt8, hashAlgorithm: UInt8) {
			pre {
				publicKey.length == 128: "publicKey must be 64 bytes hex"
				SignatureAlgorithm(rawValue: signatureAlgorithm) != nil: "unknown signature algorithm"
				HashAlgorithm(rawValue: hashAlgorithm) != nil: "unknown hash algorithm"
			}
			// Fails if the key is not a valid point for the algorithm
			let _ = PublicKey(publicKey: publicKey.decodeHex(), signatureAlgorithm: SignatureAlgorithm(rawValue: signatureAlgorithm)!)
			self.signerPublicKey = publicKey
			self.signatureAlgorithm = signatureAlgorithm
			self.hashAlgorithm = hashAlgorithm
			emit SignerSet(publicKey: publicKey, signatureAlgorithm: signatureAlgorithm, hashAlgorithm: hashAlgorithm)
		}

		// Invalidates every authorisation issued for an older epoch.
		access(VerifierManage) fun bumpEpoch(): UInt64 {
			self.epoch = self.epoch + 1
			emit EpochSet(epoch: self.epoch)
			return self.epoch
		}

		access(VerifierManage) fun setClaimsEnabled(_ enabled: Bool) {
			self.claimsEnabled = enabled
			emit ClaimsEnabled(enabled: enabled)
		}

		access(VerifierManage) fun setPurchasesEnabled(_ enabled: Bool) {
			self.purchasesEnabled = enabled
			emit PurchasesEnabled(enabled: enabled)
		}

		access(VerifierManage) fun revokePacks(ids: [UInt64]) {
			pre { ids.length <= 500: "Max 500 ids per call" }
			for id in ids {
				self.revokedPacks[id] = true
				emit PackRevoked(id: id)
			}
		}

		access(VerifierManage) fun unrevokePacks(ids: [UInt64]) {
			pre { ids.length <= 500: "Max 500 ids per call" }
			for id in ids {
				self.revokedPacks.remove(key: id)
				emit PackUnrevoked(id: id)
			}
		}

		init() {
			self.signerPublicKey = nil
			self.signatureAlgorithm = 1
			self.hashAlgorithm = 3
			self.epoch = 1
			// Nothing can be claimed or bought until a signer is set and these are switched on by the admin.
			self.claimsEnabled = false
			self.purchasesEnabled = false
			self.revokedPacks = {}
		}
	}

	access(all) view fun getPackVerifierStoragePath(): StoragePath {
		return /storage/SportvatarPackVerifier
	}

	access(contract) view fun borrowPackVerifier(): &PackVerifier? {
		return self.account.storage.borrow<&PackVerifier>(from: /storage/SportvatarPackVerifier)
	}

	// True once setupPackVerifier has been run by the contract account.
	access(all) view fun isPackVerifierSetUp(): Bool {
		return self.borrowPackVerifier() != nil
	}

	// Creates the verifier in this contract account's storage (no signer, claims and purchases disabled).
	// Only the contract account can call it with effect (it must pass a reference to its own account). Idempotent.
	access(all) fun setupPackVerifier(account: auth(Storage) &Account) {
		pre {
			account.address == self.account.address: "Only the contract account can set up the pack verifier"
		}
		if account.storage.type(at: /storage/SportvatarPackVerifier) == nil {
			account.storage.save(<-create PackVerifier(), to: /storage/SportvatarPackVerifier)
		}
	}

	access(all) view fun getVerifierMessagePrefix(): String {
		return "SPORTVATAR-PACK-V2"
	}

	access(all) view fun getVerifierConfig(): VerifierConfig {
		if let verifier = self.borrowPackVerifier() {
			return verifier.getConfig()
		}
		return VerifierConfig(signerPublicKey: nil, signatureAlgorithm: 1, hashAlgorithm: 3, epoch: 1, claimsEnabled: false, purchasesEnabled: false, revokedPacks: 0)
	}

	access(all) view fun getVerifierEpoch(): UInt64 {
		if let verifier = self.borrowPackVerifier() {
			return verifier.getEpoch()
		}
		return 1
	}

	access(all) view fun isPackRevoked(id: UInt64): Bool {
		if let verifier = self.borrowPackVerifier() {
			return verifier.isPackRevoked(id: id)
		}
		return false
	}

	access(all) view fun buildVerifierMessage(kind: String, packId: UInt64, recipient: Address, expiry: UInt64, epoch: UInt64): String {
		return self.getVerifierMessagePrefix()
			.concat("|").concat(kind)
			.concat("|").concat(packId.toString())
			.concat("|").concat(recipient.toString())
			.concat("|").concat(expiry.toString())
			.concat("|").concat(epoch.toString())
	}

	// Returns nil if the authorisation is valid, otherwise the reason it is not.
	access(all) fun checkAuthorisation(kind: String, packId: UInt64, recipient: Address, expiry: UInt64, signature: String): String? {
		if let verifier = self.borrowPackVerifier() {
			return verifier.check(kind: kind, packId: packId, recipient: recipient, expiry: expiry, signature: signature)
		}
		if kind != "claim" && kind != "purchase" {
			return "unknown kind"
		}
		return kind == "claim" ? "free pack claims are disabled" : "pack purchases are disabled"
	}

	// Panics with the reason if the authorisation is not valid.
	access(all) fun verifyAuthorisation(kind: String, packId: UInt64, recipient: Address, expiry: UInt64, signature: String) {
		if let reason = self.checkAuthorisation(kind: kind, packId: packId, recipient: recipient, expiry: expiry, signature: signature) {
			panic("Pack authorisation rejected: ".concat(reason))
		}
	}

	// ------------------------------------------------------------------------------------------- end pack verifier

	init(){ 
		let wallet =
			getAccount(0x8a86f18e0e05bd9f).capabilities.get<&{FungibleToken.Receiver}>(
				/public/dapperUtilityCoinReceiver
			)
		self.CollectionPublicPath = /public/SportvatarPackCollection
		self.CollectionStoragePath = /storage/SportvatarPackCollection
		
		// Initialize the total supply
		self.totalSupply = 0
		self.account.storage.save<@SportvatarPack.Collection>(
			<-SportvatarPack.createEmptyCollection(),
			to: SportvatarPack.CollectionStoragePath
		)
		var capability_1 =
			self.account.capabilities.storage.issue<&SportvatarPack.Collection>(
				SportvatarPack.CollectionStoragePath
			)
		self.account.capabilities.publish(capability_1, at: SportvatarPack.CollectionPublicPath)
		emit ContractInitialized()
	}
}
