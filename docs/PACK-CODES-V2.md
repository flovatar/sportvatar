# Pack codes v2 (reissue, November 2026)

## Why

Version 1 codes were `"{packId}-{signature}"`. The signature covered only the pack's `randomString` and was checked
against **account key 0**, so whoever held a code could claim the pack, from any wallet. On 2026-11-16, one address
claimed 1,039 free packs this way, using codes taken from the unassigned pool of the Flovatar-holder code set.
The prod database also stores every code (W8). All v1 codes are treated as compromised.

## What changes on-chain (owner-signed, owner-kit `sportvatar/S1`, `S2`, `S4`, `S5`)

- `SportvatarPack.claimForFree` and `SportvatarPack.Collection.purchase` (v1) now always revert with
  `Pack codes v1 are no longer accepted`. **Every outstanding v1 code is invalid as soon as the update is sealed.**
  The packs stay where they are; nothing is re-minted.
- New entry points:
  - `SportvatarPack.claimFreePack(tokenId, recipientCap, expiry, signature)`
  - `SportvatarPack.purchasePack(tokenId, recipientCap, buyTokens, expiry, signature, expectedPrice)`
- A pack verifier inside `SportvatarPack` (no new contract: Flow mainnet does not accept new contract deployments,
  so it was added in the in-place update as a nested `PackVerifier` resource saved in the contract account's storage at
  `/storage/SportvatarPackVerifier`, created by `setup_pack_verifier.cdc`) holds:
  - the **pack-signer public key** (a dedicated key, not an account key);
  - an **epoch**;
  - claim and purchase switches;
  - a per-pack revoke list.
- Message signed by the back-end, with no domain tag:

  ```
  SPORTVATAR-PACK-V2|<claim|purchase>|<packId>|<recipient 0x + 16 hex>|<expiry unix s>|<epoch>
  ```

  Signature: ECDSA P-256 over SHA3-256, hex `r||s`.
  - The authorisation works only for `recipient`.
  - It expires at `expiry` (default 15 min, `PACK_AUTH_TTL`).
  - Bumping the epoch (`pack_bump_epoch.cdc`) invalidates every outstanding authorisation at once.
- Restricted addresses (restrictions registry inside `SportvatarTemplate`) can't claim or buy.

## Back-end (this PR)

| Piece | What it does |
|---|---|
| `app/Services/PackAuthorizer.php` | Builds the exact message and signs it with `PACK_SIGNER_KEY_PATH` (chmod 600 is enforced). Also does voucher hashing (HMAC with `PACK_CODE_PEPPER`) and generates codes (20 chars Crockford base32, about 100 bits). |
| `database/migrations/2026_11_22_000001_create_pack_vouchers_table.php` | One row per free-pack entitlement. Codes are stored **only** as an HMAC. Each row is either address-bound or a bearer code. |
| `php artisan sportvatar:issue-pack-vouchers` | Creates address-bound vouchers from a CSV (for Flovatar holders), or N bearer codes for Gazzetta and giveaways. Bearer codes are written once to a chmod-600 file. |
| `php artisan sportvatar:sync-free-packs` | Makes the DB list of claimable packs match the chain (read-only REST script). |
| `php artisan sportvatar:pack-signer-check` | Compares the local signer key and epoch with `SportvatarPack.getVerifierConfig()`. |
| `php artisan sportvatar:retire-v1-pack-secrets --force` | Nulls the v1 `secret` / `signature` columns after the on-chain update. |
| `POST /pack/authorize-claim` (`PackClaimController`) | Takes a voucher code, or an empty code for address-bound entitlements. Needs a verified wallet session. Reserves a free pack and returns `{packId, expiry, signature}` for that wallet. |
| `POST /pack/authorize-purchase` | Returns the v2 authorisation for the pack the logged-in buyer has reserved. |
| `resources/js/flow/pack-redeem*.tx.js`, `pack-buy.tx.js`, `actions.js`, `app/*.js` | The website transactions now call `claimFreePack` / `purchasePack`. The claim page posts the code and then sends the transaction. |
| `SignPacks` | Retired: v1 signing refuses to run. |
| `ProcessEvents` | A `SportvatarPack.Deposit` to the redeeming wallet marks the voucher as `claimed`. |

`SignPack`, the v1 job, stays in the code but is no longer dispatched.

### Environment

```
PACK_SIGNER_KEY_PATH=/home/sportvatar/secure/pack-signer.pkey   # chmod 600, outside the repo and web root
PACK_EPOCH=1                                                    # = SportvatarPack.getVerifierEpoch()
PACK_AUTH_TTL=900
PACK_CODE_PEPPER=<64 random chars>                              # never change after issuing codes
FLOW_NETWORK=mainnet
```

## Reissue procedure

1. Owner: run owner-kit steps `S1`–`S4`.
   - S2 is the moment all v1 codes stop working.
   - S4 creates the pack-signer key and registers its public key on-chain.
2. Copy the pack-signer private key to the server (`PACK_SIGNER_KEY_PATH`, chmod 600) over a secure channel.
3. Then run on the server:

   ```bash
   php artisan migrate
   php artisan sportvatar:pack-signer-check          # must print OK
   php artisan sportvatar:sync-free-packs
   php artisan sportvatar:retire-v1-pack-secrets --force
   ```

4. **Flovatar holders.** 733 unclaimed packs are assigned to 430 holder addresses (inventory 2026-11-22). They get address-bound vouchers:

   ```bash
   php artisan sportvatar:issue-pack-vouchers flovatar_holder --addresses=flovatar_holder_unclaimed.csv --batch=2026-11-reissue-holders
   ```

   Holders connect their wallet on the claim page and claim. No code is needed, and a leaked list is harmless because
   each voucher is bound to its wallet. flovatar.com `POST /sportpacks` must stop serving v1 codes. That change belongs
   in the flovatar-web repo.
5. **Gazzetta / RCS.** Generate new codes:

   ```bash
   php artisan sportvatar:issue-pack-vouchers gazzetta --count=<N> --out=/secure/gazzetta-2026-11.csv --batch=2026-11-reissue-gazzetta
   ```

   Send the file to RCS over a secure channel, then delete it.
   - Free packs still in the contract account: 4,689. That is 733 for holders and 3,956 others, the latter presumably
     never distributed or never claimed.
   - Choose N so that holder vouchers + N <= available free packs. More free packs can be minted with
     `cadence/transactions/create/pack.cdc` if needed.
6. Owner: run `S5_enable_claims.sh` once the claim page is live.

### If a key or codes leak

- Signer key leak:
  1. Owner sends `pack_set_claims_enabled false` (stops everything).
  2. Generate a new key and run S4 again (`pack_set_signer`).
  3. Bump the epoch.
  4. Update `PACK_SIGNER_KEY_PATH` and `PACK_EPOCH`.
- Bearer codes leak: set the affected `pack_vouchers` rows to `revoked`. Codes can't be used without the back-end.
- One authorisation leaks: it only works for its recipient wallet and expires within `PACK_AUTH_TTL`.

## Tests

- The full flow was rehearsed on the emulator and on testnet with the owner kit (`bigdada-flovatar/flovatar-security`,
  `owner-kit/rehearsal/R4_checks.sh`), using signatures from the same scheme. The kit checks that:
  - a v1 code is rejected;
  - a v2 code used by another wallet is rejected;
  - a restricted wallet is rejected;
  - the intended wallet's claim+open works;
  - a replay is rejected;
  - an expired authorisation is rejected;
  - an authorisation from an old epoch is rejected and the new epoch works;
  - a revoked pack is rejected;
  - claims switched off are rejected.
- `PackAuthorizer::sign` (PHP/OpenSSL) was verified against the on-chain verifier logic. A signature produced by PHP
  passes Cadence `PublicKey.verify(..., domainSeparationTag: "", SHA3_256)` in a testnet script, and fails for an
  altered message.
- The Laravel endpoints were not run against a live database here. They need the verified-login session from the web
  hotfix PR.
