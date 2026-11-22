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
window.loadLoginNonce = function(){
    return window.axios.get('/login/nonce').then(function(response){
        if(response.data && response.data.nonce){
            window.accountProofNonce = response.data.nonce;
            window.loginMessage = response.data.message;
            window.loginAddress = response.data.address || null;
        }
        return response.data;
    }).catch(function(error){
        console.log(error);
        return null;
    });
};
window.loadLoginNonce();

const accountProofResolver = async () => {
    if(!window.accountProofNonce){
        await window.loadLoginNonce();
    }
    return window.accountProofNonce ? {appIdentifier: window.location.origin, nonce: window.accountProofNonce} : null;
};

config()
    .put("app.detail.icon", "https://sportvatar.com/images/logo-rounded.png") // Configure FCL's Access Node
    .put("app.detail.title", "Sportvatar") // Configure FCL's Access Node
    //.put("sdk.transport", grpcSend)
    .put("accessNode.api", process.env.MIX_ACCESS_NODE) // Configure FCL's Access Node
    .put("discovery.wallet", process.env.MIX_WALLET_DISCOVERY) // Configure FCL's Wallet Discovery mechanism
    .put("discovery.wallet.method", 'POP/RPC')
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


