window._ = require('lodash');

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.signalR = require("@microsoft/signalr");
window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * CSRF for edge-cached / static pages: the HTML carries no token and no session. Before the first
 * state-changing request (login, logout, subscribe, pack claim/reserve, ...) we fetch one from
 * /csrf-token (this starts the session) and send it as X-CSRF-TOKEN. On 419 (expired or
 * rotated after login) the token is refetched and the request retried once.
 * Read-only search POSTs are CSRF-exempt server side (App\Support\PublicCache) and skip
 * this, so browsing never creates a session.
 */
const csrfExemptPaths = [
    /^\/api\//,
    /^\/(search|locker-room\/search|leaderboard\/search|transactions\/search|builder\/search)\/?$/
];
let csrfTokenPromise = null;
window.fetchCsrfToken = function(force){
    if(force || !csrfTokenPromise){
        csrfTokenPromise = window.axios.get('/csrf-token', {params: {t: Date.now()}}).then(function(response){
            return response.data.token;
        }).catch(function(error){
            csrfTokenPromise = null;
            throw error;
        });
    }
    return csrfTokenPromise;
};
function needsCsrfToken(config){
    const method = (config.method || 'get').toLowerCase();
    if(['get', 'head', 'options'].indexOf(method) >= 0){
        return false;
    }
    let url;
    try {
        url = new URL(config.url, window.location.origin);
    } catch(e){
        return false;
    }
    if(url.origin !== window.location.origin){
        return false;
    }
    return !csrfExemptPaths.some(function(re){ return re.test(url.pathname); });
}
window.axios.interceptors.request.use(async function(config){
    if(needsCsrfToken(config)){
        const token = await window.fetchCsrfToken(false);
        if(token){
            config.headers['X-CSRF-TOKEN'] = token;
        }
    }
    return config;
});
window.axios.interceptors.response.use(function(response){ return response; }, async function(error){
    const config = error.config;
    if(error.response && error.response.status === 419 && config && !config.__csrfRetried && needsCsrfToken(config)){
        config.__csrfRetried = true;
        await window.fetchCsrfToken(true);
        return window.axios(config);
    }
    return Promise.reject(error);
});

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     cluster: process.env.MIX_PUSHER_APP_CLUSTER,
//     forceTLS: true
// });


import {config} from "@onflow/fcl"
import {send as grpcSend} from "@onflow/transport-grpc"

/**
 * FCL account-proof: the server issues a nonce (bound to the session) that the wallet signs
 * during authentication; POST /login then verifies the proof on chain (FCLCrypto).
 * The nonce is prefetched on page load because Dapper Wallet requires the resolver to
 * resolve immediately (otherwise browsers block the wallet popup).
 */
window.accountProofNonce = null;
window.loginMessage = null;
window.loginAddress = null;
window.loginIsAdmin = false;
// Server-side (Laravel session) login state: {authenticated, address}. Pages are edge-cached / static,
// so it is read from /login/nonce instead of being rendered into the HTML.
window.serverAuth = {authenticated: false, address: null};
window.loadLoginNonce = function(){
    window.loginNoncePromise = window.axios.get('/login/nonce').then(function(response){
        if(response.data && response.data.nonce){
            window.accountProofNonce = response.data.nonce;
            window.loginMessage = response.data.message;
            window.loginAddress = response.data.address || null;
            window.loginIsAdmin = !!response.data.admin;
        }
        if(response.data){
            window.serverAuth = {authenticated: !!response.data.address, address: response.data.address || null};
            window.dispatchEvent(new CustomEvent('server-auth', {detail: window.serverAuth}));
        }
        return response.data;
    }).catch(function(error){
        console.log(error);
        return null;
    });
    return window.loginNoncePromise;
};
window.loadLoginNonce();

const accountProofResolver = async () => {
    if(!window.accountProofNonce){
        await window.loadLoginNonce();
    }
    return window.accountProofNonce ? {appIdentifier: window.location.origin, nonce: window.accountProofNonce} : null;
};

/**
 * Wallet discovery (env-driven, like flovatar-web):
 *  - MIX_WALLET_DISCOVERY = Dapper's direct authn URL (accounts.meetdapper.com/...): Dapper only, opened as a popup
 *    (POP/RPC). This is production today and stays byte-for-byte the same behaviour.
 *  - MIX_WALLET_DISCOVERY = FCL Discovery (fcl-discovery.onflow.org/[testnet/]authn): FCL's wallet picker. Flow Wallet
 *    (extension, EXT/RPC; mobile via WalletConnect when MIX_WALLETCONNECT_PROJECT_ID is set) is listed by default,
 *    opt-in wallets come from MIX_WALLET_DISCOVERY_INCLUDE (Dapper: mainnet 0xead892083b3e2c6c, testnet
 *    0x82ec283f88a62e65), Blocto (shut down) is excluded. Forcing POP/RPC here would open Discovery itself in a popup
 *    and break the Flow Wallet extension handshake, so the method is left to FCL (IFRAME/RPC).
 */
const walletDiscovery = process.env.MIX_WALLET_DISCOVERY || '';
const isDapperDirectAuthn = walletDiscovery.indexOf('meetdapper.com') >= 0;
window.walletDiscoveryMode = isDapperDirectAuthn ? 'dapper' : 'discovery';

config()
    .put("app.detail.icon", "https://sportvatar.com/images/logo-rounded.png") // Configure FCL's Access Node
    .put("app.detail.title", "Sportvatar") // Configure FCL's Access Node
    //.put("sdk.transport", grpcSend)
    .put("accessNode.api", process.env.MIX_ACCESS_NODE) // Configure FCL's Access Node
    .put("discovery.wallet", process.env.MIX_WALLET_DISCOVERY) // Configure FCL's Wallet Discovery mechanism

if (isDapperDirectAuthn) {
    // Dapper's direct authn endpoint must be opened as a popup
    config().put("discovery.wallet.method", 'POP/RPC');
}

config()
    .put("fcl.limit", 9999) // Configure gas limit
    .put("fcl.accountProof.resolver", accountProofResolver) // Server-issued nonce for wallet login (account-proof)
    .put("0xProfile", process.env.MIX_CONTRACT_PROFILE) // Will let us use `0xProfile` in our Cadence
    .put("0xFungible", process.env.MIX_CONTRACT_FUNGIBLE) // Will let us use `0xFungible` in our Cadence
    .put("0xNonFungible", process.env.MIX_CONTRACT_NON_FUNGIBLE) // Will let us use `0xFungible` in our Cadence
    .put("0xFlowToken", process.env.MIX_CONTRACT_FLOW_TOKEN) // Will let us use `0xFlowToken` in our Cadence
    .put("0xSportvatar", process.env.MIX_CONTRACT_FLOW) // Will let us use `0xSportvatar` in our Cadence
    .put("0xComponent", process.env.MIX_CONTRACT_FLOW) // Will let us use `0xSportbit` in our Cadence
    .put("0xTemplate", process.env.MIX_CONTRACT_FLOW) // Will let us use `0xSportvatarTemplate` in our Cadence
    .put("0xPack", process.env.MIX_CONTRACT_FLOW) // Will let us use `0xSportvatarPack` in our Cadence
    .put("0xMarketplace", process.env.MIX_CONTRACT_FLOW) // Will let us use `0xMarketplace` in our Cadence
    .put("0xFindProfile", process.env.MIX_CONTRACT_FIND) // Will let us use `0xFindProfile` in our Cadence
    .put("0xDapperMerchant", process.env.MIX_DAPPER_MERCHANT) // Will let us use `0xFindProfile` in our Cadence
    .put("0xFind", process.env.MIX_CONTRACT_FIND) // Will let us use `0xFind` in our Cadence
    .put("0xMetadataViews", process.env.MIX_METADATA_VIEWS) // Will let us use `0xMetadataViews` in our Cadence
    .put("0xStorefront2", process.env.MIX_CONTRACT_STOREFRONT2) // Will let us use `0xStorefront` in our Cadence
    .put("0xDuc", process.env.MIX_CONTRACT_DUC) // Will let us use `0xDuc` in our Cadence
    .put("0xFut", process.env.MIX_CONTRACT_DUC) // Will let us use `0xFut` in our Cadence

// Network-aware extras (unset on production = unchanged behaviour). Testnet build:
// MIX_FLOW_NETWORK=testnet, MIX_WALLET_DISCOVERY=https://fcl-discovery.onflow.org/testnet/authn,
// MIX_WALLET_DISCOVERY_INCLUDE=0x33f75ff0b830dcec,0x82ec283f88a62e65 (Flow Wallet, Dapper testnet).
if (process.env.MIX_FLOW_NETWORK) {
    config().put("flow.network", process.env.MIX_FLOW_NETWORK);
}
if (!isDapperDirectAuthn) {
    const splitList = (v) => (v || '').split(',').map(a => a.trim()).filter(a => a);
    const include = splitList(process.env.MIX_WALLET_DISCOVERY_INCLUDE);
    // Blocto is shut down but still listed by FCL Discovery
    const exclude = process.env.MIX_WALLET_DISCOVERY_EXCLUDE !== undefined ? splitList(process.env.MIX_WALLET_DISCOVERY_EXCLUDE) : ["0x55ad22f01ef568a1"];
    if (include.length > 0) {
        config().put("discovery.authn.include", include);
    }
    if (exclude.length > 0) {
        config().put("discovery.authn.exclude", exclude);
    }
    if (process.env.MIX_WALLET_DISCOVERY_AUTHN_ENDPOINT) {
        config().put("discovery.authn.endpoint", process.env.MIX_WALLET_DISCOVERY_AUTHN_ENDPOINT);
    }
    // WalletConnect (Flow Wallet mobile app). Free project id from cloud.reown.com.
    if (process.env.MIX_WALLETCONNECT_PROJECT_ID) {
        config().put("walletconnect.projectId", process.env.MIX_WALLETCONNECT_PROJECT_ID);
    }
} else if (process.env.MIX_WALLET_DISCOVERY_INCLUDE) {
    config().put("discovery.authn.include", process.env.MIX_WALLET_DISCOVERY_INCLUDE.split(',').map(a => a.trim()).filter(a => a));
}
