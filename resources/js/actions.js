
import {builderCheckoutTx} from './flow/builder-checkout.tx';
import {checkSportvatarAvailabilityScript} from './flow/check-sportvatar-availability.script';
import {componentSaleBuyTx} from './flow/component-sale-buy.tx';
import {componentSaleCancelTx} from './flow/component-sale-cancel.tx';
import {componentSaleCreateTx} from './flow/component-sale-create.tx';
import {sportvatarAddSportbitTx} from './flow/sportvatar-add-sportbit.tx';
import {sportvatarRemoveSportbitTx} from './flow/sportvatar-remove-sportbit.tx';
import {sportvatarCreateTx} from './flow/sportvatar-create.tx';
import {sportvatarCreateWithSportbitTx} from './flow/sportvatar-create-with-sportbit.tx';
import {sportvatarSaleBuyTx} from './flow/sportvatar-sale-buy.tx';
import {sportvatarSaleCancelTx} from './flow/sportvatar-sale-cancel.tx';
import {sportvatarSaleCreateTx} from './flow/sportvatar-sale-create.tx';
import {getCollectionsScript} from './flow/get-collections.script';
import {getCollectionsPackScript} from './flow/get-collections-pack.script';
import {getCollectionsSportvatarScript} from './flow/get-collections-sportvatar.script';
import {getCollectionsSportvatarIdsScript} from './flow/get-collections-sportvatar-ids.script';
import {getCollectionsSportbitScript} from './flow/get-collections-sportbit.script';
import {getSportbitScript} from './flow/get-sportbit.script';
import {getSportbitsScript} from './flow/get-sportbits.script';
import {getSportvatarScript} from './flow/get-sportvatar.script';
import {getSportvatarsScript} from './flow/get-sportvatars.script';
import {getSportvatarIdsScript} from './flow/get-sportvatar-ids.script';
import {getProfileScript} from './flow/get-profile.script';
import {getTemplateScript} from './flow/get-template.script';
import {getTemplatesScript} from './flow/get-templates.script';
import {packBuyTx} from './flow/pack-buy.tx';
import {packOpenTx} from './flow/pack-open.tx';
import {packRedeemTx} from './flow/pack-redeem.tx';
import {packRedeemAndOpenTx} from './flow/pack-redeem-and-open.tx';
import {getPacksScript} from './flow/get-packs.script';
import {checkPacksScript} from './flow/check-packs.script';
// Flow Wallet (non-Dapper) variants. The Dapper transactions above are left byte-identical (Dapper-approved code).
import {isInitializedScript} from './flow/is-initialized.script';
import {initAccountFlowTx} from './flow/init-account-flow.tx';
import {sportvatarSaleCreateFlowTx} from './flow/sportvatar-sale-create-flow.tx';
import {sportvatarSaleBuyFlowTx} from './flow/sportvatar-sale-buy-flow.tx';
import {componentSaleCreateFlowTx} from './flow/component-sale-create-flow.tx';
import {componentSaleBuyFlowTx} from './flow/component-sale-buy-flow.tx';
import {sportvatarSendGiftTx} from './flow/sportvatar-send-gift.tx';
import {componentSendGiftTx} from './flow/component-send-gift.tx';


import './classes/Helpers';

import { DateTime } from 'luxon'
import { toSvg } from "jdenticon"
import Cookies from 'js-cookie'


require('./bootstrap');

import Alpine from 'alpinejs';


import * as fcl from "@onflow/fcl"
import * as t from "@onflow/types"
import {config} from "@onflow/fcl";


window.fcl = fcl;
window.t = t;
window.user = null;
window.profile = null;
window.dispatch = null;
window.currentWallet = null;
window.txId = null;
window.txFirstRun = false;

/**
 * Which wallet is connected: 'dapper' or 'flow' (Flow Wallet or any other non-Dapper wallet).
 * Looks at every FCL service (FCL >= 1.10 no longer guarantees that services[0] is the authn service),
 * the authn endpoint and the provider address (Dapper mainnet 0xead892083b3e2c6c, testnet 0x82ec283f88a62e65).
 */
window.detectWallet = function(user){
    // Dapper-only build (MIX_WALLET_DISCOVERY = Dapper's direct authn URL, production today): every user is Dapper
    if(window.walletDiscoveryMode === 'dapper'){
        return 'dapper';
    }
    const services = (user && user.services) ? user.services : [];
    const isDapper = services.some(function(s){
        const uid = (s.uid || '').toLowerCase();
        const endpoint = (s.endpoint || '').toLowerCase();
        const provider = (s.provider && s.provider.address) ? s.provider.address.toLowerCase() : '';
        return uid.indexOf('dapper') >= 0 || endpoint.indexOf('meetdapper.com') >= 0
            || provider === '0xead892083b3e2c6c' || provider === '0x82ec283f88a62e65';
    });
    return isDapper ? 'dapper' : 'flow';
}

/**
 * Listing currency a wallet trades in: Dapper Wallet = 'DUC' (shown as USD), Flow Wallet = 'FLOW'.
 * A Flow Wallet must never create DUC listings (its DUC receiver forwards to Dapper) and can't pay DUC;
 * Dapper Wallet only signs the Dapper-approved DUC transactions.
 */
window.walletCurrency = function(wallet){
    wallet = wallet || window.currentWallet;
    if(wallet === 'dapper'){
        return 'DUC';
    }
    if(wallet === 'flow'){
        return 'FLOW';
    }
    return null;
}
window.currencyLabel = function(currency){
    return currency === 'FLOW' ? 'FLOW' : 'USD';
}
window.walletName = function(wallet){
    return wallet === 'dapper' ? 'Dapper Wallet' : 'Flow Wallet';
}
// Listings without a currency are legacy DUC listings
window.listingCurrency = function(offer){
    return (offer && offer.currency) ? offer.currency : 'DUC';
}
window.canPayListing = function(offer, wallet){
    const currency = window.walletCurrency(wallet);
    return currency !== null && currency === window.listingCurrency(offer);
}

window.blacklistedAddresses = [
    '0x2b3733faa486deb3',
    '0x34f8fd310486de0f'
];


//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------



window.initApp = function(dispatch){
    window.dispatch = dispatch;
    fcl.currentUser().subscribe(updateUser);
}

window.transError = function(transaction){
    if(transaction.errorMsg){
        return transaction.errorMsg;
    }
    if(transaction.errorMessage){
        return transaction.errorMessage;
    }
    return '';
}

async function updateUser(user){
    window.user = user;

    if(user.addr) {
        // Decide the wallet here, before choosing which init check / transactions to use
        window.currentWallet = window.detectWallet(user);

        let isBlacklisted = _.find(window.blacklistedAddresses, (item) => {
            return item == user.addr;
        });

        if(isBlacklisted){
            window.dispatch('user-blacklisted', {});
            window.logout();
        } else {
            window.dispatch('user-loaded', {user: user, wallet: window.currentWallet});
            window.checkPendingPacks();
            if(window.currentWallet != 'dapper'){
                // Flow Wallet: offer the account setup (collections + storefront) when it's missing.
                // Dapper keeps its previous behaviour (no init popup; its approved transactions create what they need).
                try {
                    const initialized = await isInitializedScript(user.addr);
                    if(!initialized){
                        window.dispatch('user-not-initialized');
                    }
                } catch(error){
                    console.log('is-initialized check failed');
                    console.log(error);
                }
            }
            await refreshProfile();
        }
    } else {
        window.dispatch('user-check-finish', {user: user});
    }
}


window.loadInitUser = async function(){
    if(window.currentWallet == 'dapper'){
        // Dapper accounts are set up by Dapper's approved transactions
        return;
    }
    try {
        window.dispatch('loading-start');
        const txId = await initAccountFlowTx();
        window.dispatch('waiting-seal');
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            window.readTransaction(transaction);
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    window.dispatch('loading-completed');
                    window.dispatch('account-initialized', {account: window.user});
                    refreshProfile();
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}

window.readTransaction = function(transaction){
    console.log(transaction);
    try {
        if(transaction && transaction.events && transaction.events[0] && transaction.events[0].transactionId){
            //console.log(transaction.events[0].transactionId);
            window.dispatch('executing-transaction', {txId: transaction.events[0].transactionId});
        }
    } catch(error){
        console.log('error reading transaction ID');
        console.log(error);
    }
}



window.loadProfile = function(data){
    //console.log('profile loaded');
    //console.log(data);
    window.profile = data;
    window.dispatch('profile-loaded', {profile: data});
    return data;
}

window.refreshProfile = async function(){
    await getProfileScript(window.user.addr).then(loadProfile);
}

window.loadAddressProfile = function(data){
    //console.log('address profile loaded');
    //console.log(data);
    window.dispatch('address-profile-loaded', {profile: data});
    return data;
}

window.getAddressProfile = async function(address){
    await getProfileScript(address).then(loadAddressProfile);
}

window.loadAddressOwnerProfile = function(data){
    //console.log('address profile owner loaded');
    //console.log(data);
    window.dispatch('address-profile-owner-loaded', {profile: data});
    return data;
}

window.getAddressOwnerProfile = async function(address){
    await getProfileScript(address).then(loadAddressOwnerProfile);
}

window.addressFound = function(data){
    //console.log('address found');
    //console.log(data);
    window.dispatch('address-found', {profile: data});
    return data;
}

window.lookupAddress = async function(address){
    await getProfileScript(address).then(addressFound);
}


window.findAccountProof = function(user){
    if(!user || !user.services){
        return null;
    }
    let service = user.services.find(function(item){ return item.type === 'account-proof'; });
    return (service && service.data && service.data.nonce && service.data.signatures) ? service.data : null;
}

/**
 * Server login for the connected wallet. The server verifies an FCL account-proof
 * (Dapper Wallet, Flow Wallet) or, as a fallback, a signUserMessage signature of the
 * session message against the account keys on chain. onSuccess() runs after a
 * successful login (default: reload the page).
 */
window.walletLogin = function(onSuccess){
    window.afterWalletLogin = onSuccess || null;
    return window.createSignature(window.loginMessage);
}

window.createSignature = async function(message){
    window.dispatch('loading-start');

    try{
        if(!window.accountProofNonce){
            await window.loadLoginNonce();
        }
        const user = await fcl.currentUser().snapshot();
        const proof = window.findAccountProof(user);

        if(proof && window.accountProofNonce && proof.nonce === window.accountProofNonce){
            // Account-proof returned by the wallet during authentication
            window.dispatch('loading-completed');
            window.sendAccountProofLogin(proof);
        } else if(window.currentWallet == 'dapper' || proof){
            // Missing or stale account-proof: re-authenticate so the wallet signs the current nonce
            const freshUser = await fcl.reauthenticate();
            const freshProof = window.findAccountProof(freshUser);
            window.dispatch('loading-completed');
            if(freshProof){
                window.sendAccountProofLogin(freshProof);
            } else {
                window.dispatch('login-failed');
            }
        } else {
            const MSG = Buffer.from(message || window.loginMessage || '').toString("hex");
            await fcl.currentUser().signUserMessage(MSG).then(function(data){
                console.log('signature created');
                window.dispatch('signature-created', {signature: data});
                window.dispatch('loading-completed');
                window.sendLogin(MSG, data);
            });
        }
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}



window.sendSubscription = function(email, consentSportvatar, consentRcs) {
    if(email) {
        console.log('send subscription');
        axios.post('/subscribe', {
            email: email,
            sportvatar: consentSportvatar,
            rcs: consentRcs
        })
            .then(function (response) {
                window.dispatch('subscribed-email');
                //console.log(response);
            })
            .catch(function (error) {
                console.log(error);
            });
    }
}



window.handleLoginResponse = function(response){
    if(response.data && response.data.result && response.data.result === true){
        window.dispatch('logged-in');
        if(typeof window.afterWalletLogin === 'function'){
            const next = window.afterWalletLogin;
            window.afterWalletLogin = null;
            window.loadLoginNonce().then(function(){ window.checkPendingPacks(); });
            next();
        } else {
            window.location.reload(true);
        }
    } else {
        window.loadLoginNonce();
        window.dispatch('login-failed');
    }
}

window.sendLogin = function(msg, compSigs) {

    let signatures = (compSigs || []).map(function(compSig){
        return {keyId: compSig.keyId, signature: compSig.signature};
    });

    if(window.user && window.user.addr && signatures.length > 0) {
        axios.post('/login', {
            message: msg,
            address: window.user.addr,
            signatures: signatures
        })
            .then(window.handleLoginResponse)
            .catch(function (error) {
                console.log(error);
                window.dispatch('login-failed');
            });
    }
}

window.sendAccountProofLogin = function(proof) {

    axios.post('/login', {
        proofType: 'account-proof',
        address: proof.address,
        nonce: proof.nonce,
        signatures: proof.signatures.map(function(compSig){
            return {keyId: compSig.keyId, signature: compSig.signature};
        })
    })
        .then(window.handleLoginResponse)
        .catch(function (error) {
            console.log(error);
            window.dispatch('login-failed');
        });
}

/**
 * Reserves a paid pack for the logged-in wallet and opens the purchase page.
 * If the server session is missing (or belongs to another wallet) the user is
 * asked to log in with the wallet first, then the reservation is retried once.
 */
window.reservePackAndGo = function(reserveUrl, retried){
    const connected = window.user && window.user.addr ? window.user.addr.toLowerCase() : null;
    const sessionAddress = window.loginAddress ? window.loginAddress.toLowerCase() : null;

    if(!connected){
        fcl.authenticate();
        return;
    }
    if(sessionAddress !== connected && !retried){
        window.walletLogin(function(){ window.reservePackAndGo(reserveUrl, true); });
        return;
    }

    window.dispatch('loading-start');
    axios.post(reserveUrl, {})
        .then(function (response) {
            window.dispatch('loading-completed');
            if(response.data && response.data.result && response.data.redirect){
                window.location = response.data.redirect;
            } else {
                window.dispatch('reserve-failed');
            }
        })
        .catch(function (error) {
            window.dispatch('loading-completed');
            if(error.response && error.response.status == 401 && !retried){
                window.walletLogin(function(){ window.reservePackAndGo(reserveUrl, true); });
            } else {
                console.log(error);
                window.dispatch('reserve-failed');
            }
        });
}

window.getCollections = async function(address, addressName){
    await getCollectionsScript(address, addressName).then(function(data){
        console.log('collections loaded');
        //console.log(data);
        window.dispatch('collections-loaded', {collections: data});
    });
}
window.getCollectionsPack = async function(address, addressName){
    await getCollectionsPackScript(address, addressName).then(function(data){
        console.log('collections pack loaded');
        //console.log(data);
        window.dispatch('collections-pack-loaded', {collections: data});
    });
}
window.getCollectionsSportvatar = async function(address, addressName){
    await getCollectionsSportvatarScript(address, addressName).then(function(data){
        console.log('collections sportvatar loaded');
        //console.log(data);
        window.dispatch('collections-sportvatar-loaded', {collections: data});
    })
    .catch(function (error) {
        //console.log(error);
        console.log('failed to load Sportvatar collection');
        window.dispatch('collections-sportvatar-failed', {});
    });
}
window.getCollectionsSportvatarIds = async function(address, addressName){
    await getCollectionsSportvatarIdsScript(address, addressName).then(function(data){
        console.log('collections sportvatar ids loaded');
        //console.log(data);
        window.dispatch('collections-sportvatar-ids-loaded', {collections: data});
    });
}

window.getCollectionsSportbit = async function(address, addressName){
    await getCollectionsSportbitScript(address, addressName).then(function(data){
        console.log('collections sportbit loaded');
        //console.log(data);
        window.dispatch('collections-sportbit-loaded', {collections: data});
    });
}


window.getComponent = async function(address, componentId){
    await getSportbitScript(address, componentId).then(function(data){
        console.log('component loaded');
        //console.log(data);
        window.dispatch('fcomponent-loaded', {component: data});
    });
}
window.getComponents = async function(address){
    await getSportbitsScript(address).then(function(data){
        console.log('components loaded');
        //console.log(data);
        window.dispatch('fcomponents-loaded', {components: data});
    });
}

window.openPack = async function(packId){
    console.log('opening pack');
    window.dispatch('loading-start');
    try{
        window.txId = await packOpenTx(packId);
        console.log('pack opening');
        window.dispatch('waiting-seal');

        setTimeout(function(){
            window.openPackTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}


window.openPackTx = async function(txId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            window.dispatch('pack-opening-started');
            if (fcl.tx.isSealed(transaction)) {
                if (!window.transError(transaction)) {
                    console.log('pack opened sealed');
                    window.dispatch('pack-opened');
                    window.dispatch('loading-completed');
                    window.getCollectionsSportbit(window.user.addr);
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.openPackTx(txId);
        },5000);
    }
}


window.reservePack = function(packType){

    console.log('reserve pack');
    console.log(packType);
    axios.post('/pack/reserve', {
        type: packType
    }).then(function (response) {
            //console.log(response);
            if(response.data && response.data.packId && response.data.signature){
                window.dispatch('pack-reserved');
                window.dispatch('loading-start');
                let price = (packType == 'deluxe') ? window.packPrice2 : window.packPrice;
                price = (packType == 'flobit') ? 39.0 : price;
                window.buyPack(response.data.packId, price, response.data.signature)
            } else {
                window.dispatch('reserve-failed');
                console.log(response);
            }
        })
        .catch(function (error) {
            console.log(error);
        });
}

window.buyPack = async function(packId, price, expiry, signature){
    //saleAddress, tokenId, amount, secret
    console.log('purchasing pack');
    window.dispatch('loading-start');
    try{
        window.txId = await packBuyTx(process.env.MIX_DAPPER_MERCHANT, packId, price, expiry, signature);
        console.log('pack purchased');
        window.dispatch('waiting-seal');
        window.dispatch('pack-sealing');
        setTimeout(function(){
            window.buyPackTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}
window.buyPackTx = async function(txId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if (!window.transError(transaction)) {
                    console.log('pack purchased sealed');
                    window.dispatch('pack-purchased');
                    window.dispatch('loading-completed');
                    /*
                    if (window.vipCookieVal != 'hdfh55yyerg5g34t34t344') {
                        axios.post('/pack/purchased', {}).then(() => {
                            window.removeQueueCookies();
                        });
                    }
                    */
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.buyPackTx(txId);
        },5000);
    }
}



window.redeemPack = async function(packId, expiry, signature){
    //saleAddress, tokenId, amount, secret
    console.log('claiming pack');
    window.dispatch('loading-start');
    try{
        window.dispatch('waiting-seal');
        window.dispatch('pack-sealing');
        window.txId = await packRedeemTx(packId, expiry, signature);
        console.log('pack claimed');
        setTimeout(function(){
            window.redeemPackTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}

window.redeemPackTx = async function(txId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    console.log('pack claim sealed');
                    window.dispatch('pack-purchased');
                    window.dispatch('pack-claimed');
                    window.dispatch('loading-completed');
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.redeemPackTx(txId);
        },5000);
    }
}



window.redeemAndOpenPack = async function(packId, expiry, signature){
    //saleAddress, tokenId, amount, secret
    console.log('claiming pack and open');
    window.dispatch('loading-start');
    try{
        window.dispatch('waiting-seal');
        window.dispatch('pack-sealing');
        window.txId = await packRedeemAndOpenTx(packId, expiry, signature);
        console.log('pack claiming and opening');
        setTimeout(function(){
            window.redeemAndOpenPackTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}

window.redeemAndOpenPackTx = async function(txId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    console.log('pack claim and open sealed');
                    window.dispatch('pack-purchased');
                    window.dispatch('pack-claimed');
                    window.dispatch('pack-claimed-open');
                    window.dispatch('loading-completed');
                    window.getComponents(window.user.addr);
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.redeemAndOpenPackTx(txId);
        },5000);
    }
}

//


window.createSportvatar = async function(series, layers, flame){
    console.log('creating collectible');
    console.log(layers);
    window.dispatch('loading-start');
    let layersVal = _.map(Object.values(layers), item => {
        return item ? ''+item : null;
    });

    try{
        window.txId = await sportvatarCreateTx(series, Object.keys(layers), layersVal, flame);
        console.log('sportvatar created');
        window.dispatch('waiting-seal');
        setTimeout(function(){
            window.createSportvatarTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}
window.createSportvatarWithSportbit = async function(series, layers, flame, sportbits){
    console.log('creating collectible');
    console.log(layers);
    window.dispatch('loading-start');
    let layersVal = _.map(Object.values(layers), item => {
        return item ? ''+item : null;
    });

    try{
        window.txId = await sportvatarCreateWithSportbitTx(series, Object.keys(layers), layersVal, flame, sportbits);
        console.log('sportvatar created');
        window.dispatch('waiting-seal');
        setTimeout(function(){
            window.createSportvatarTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}

window.createSportvatarTx = async function(txId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    console.log('sportvatar created sealed');
                    window.dispatch('sportvatar-created');
                    window.dispatch('loading-completed');
                    window.getComponents(window.user.addr);
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.createSportvatarTx(txId);
        },5000);
    }
}


window.checkSportvatarAvailability = async function(series, layers){
    //console.log('start check sportvatar availability');
    //console.log(Object.keys(layers));
    let layersVal = _.map(Object.values(layers), item => {
        return item ? ''+item : null;
    });
    //console.log(layersVal);
    await checkSportvatarAvailabilityScript(series, Object.keys(layers), layersVal).then(function(data){
        //console.log('availability checked');
        //console.log(data);
        window.dispatch('availability-checked', {result: data});
    });
}





// Refuses a listing the connected wallet can't pay: DUC listings need Dapper Wallet, FLOW listings a Flow Wallet.
window.checkListingWallet = function(currency){
    currency = currency || 'DUC';
    if(window.walletCurrency() === currency){
        return true;
    }
    window.dispatch('transaction-error', {message: 'This item is listed in ' + window.currencyLabel(currency) + ': connect a ' + (currency === 'FLOW' ? 'Flow Wallet' : 'Dapper Wallet') + ' to buy it.'});
    return false;
}

window.buyComponentSale = async function(saleAddress, tokenId, amount, currency){
    console.log('component purchase start');
    if(!window.checkListingWallet(currency)){
        return;
    }
    window.dispatch('loading-start');

    try{
        if(currency === 'FLOW'){
            window.txId = await componentSaleBuyFlowTx(saleAddress, tokenId, amount);
        } else {
            window.txId = await componentSaleBuyTx(saleAddress, tokenId, amount, process.env.MIX_DAPPER_MERCHANT);
        }

        console.log('component purchase finish');
        window.dispatch('waiting-seal');
        setTimeout(function(){
            window.buyComponentSaleTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}
window.buyComponentSaleTx = async function(txId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    console.log('component purchase sealed');
                    window.dispatch('fcomponent-sale-purchased');
                    window.dispatch('loading-completed');
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.buyComponentSaleTx(txId);
        },5000);
    }
}


window.cancelComponentSale = async function(componentId){
    console.log('component sale cancel start');
    window.dispatch('loading-start');
    try{
        window.txId = await componentSaleCancelTx(componentId);

        console.log('component sale cancel finish');
        window.dispatch('waiting-seal');
        setTimeout(function(){
            window.cancelComponentSaleTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}
window.cancelComponentSaleTx = async function(txId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    console.log('component sale cancel sealed');
                    window.dispatch('fcomponent-sale-cancelled');
                    window.dispatch('loading-completed');
                    //window.getComponent(window.user.addr, componentId);
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.cancelComponentSaleTx(txId);
        },5000);
    }
}

window.createComponentSale = async function(componentId, price){
    console.log('component sale create start');
    window.dispatch('loading-start');
    try{
        let expiry = Math.floor(Date.now()) + (60 * 60 * 24 * 365); //expires in one year
        if(window.currentWallet == 'dapper'){
            window.txId = await componentSaleCreateTx(componentId, price, 'Sportbit', expiry);
        } else if(window.currentWallet == 'flow'){
            // Flow Wallet: FLOW listing only (never DUC)
            window.txId = await componentSaleCreateFlowTx(componentId, price, 'Sportbit', expiry);
        } else {
            throw new Error('Connect a wallet first');
        }
        console.log('component sale create finish');
        window.dispatch('waiting-seal');
        setTimeout(function(){
            window.createComponentSaleTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}
window.createComponentSaleTx = async function(txId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    console.log('component sale create sealed');
                    window.dispatch('fcomponent-sale-created');
                    window.dispatch('loading-completed');
                    //window.getComponent(window.user.addr, componentId);
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.createComponentSaleTx(txId);
        },5000);
    }
}




window.sportvatarAddSportbit = async function(sportvatarId, accessory){
    console.log('sportvatar add accessory start');
    window.dispatch('loading-start');
    try{
        window.txId = await sportvatarAddSportbitTx(sportvatarId, parseInt(accessory,10));
        console.log('sportvatar add accessory finish');
        window.dispatch('waiting-seal');
        setTimeout(function(){
            window.sportvatarAddSportbitInternalTx(window.txId, sportvatarId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}

window.sportvatarAddSportbitInternalTx = async function(txId, sportvatarId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if (!window.transError(transaction)) {
                    console.log('sportvatar add accessory sealed');
                    window.dispatch('sportvatar-accessory-added');
                    window.dispatch('loading-completed');
                    window.getSportvatar(window.user.addr, sportvatarId);
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.sportvatarAddSportbitInternalTx(txId, sportvatarId);
        },5000);
    }
}

window.sportvatarRemoveSportbit = async function(sportvatarId, layer){
    console.log('sportvatar remove accessory start');
    window.dispatch('loading-start');
    try{
        window.txId = await sportvatarRemoveSportbitTx(sportvatarId, layer);
        console.log('sportvatar remove accessory finish');
        window.dispatch('waiting-seal');
        setTimeout(function(){
            window.sportvatarRemoveSportbitInternalTx(window.txId, sportvatarId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}

window.sportvatarRemoveSportbitInternalTx = async function(txId, sportvatarId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    console.log('sportvatar remove accessory sealed');
                    window.dispatch('sportvatar-accessory-added');
                    window.dispatch('loading-completed');
                    window.getSportvatar(window.user.addr, sportvatarId);
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.sportvatarRemoveSportbitInternalTx(txId, sportvatarId);
        },5000);
    }
}

window.buySportvatarSale = async function(saleAddress, tokenId, amount, currency){
    console.log('sportvatar purchase start');
    if(!window.checkListingWallet(currency)){
        return;
    }
    window.dispatch('loading-start');
    try{
        if(currency === 'FLOW'){
            window.txId = await sportvatarSaleBuyFlowTx(saleAddress, tokenId, amount);
        } else {
            window.txId = await sportvatarSaleBuyTx(saleAddress, tokenId, amount, process.env.MIX_DAPPER_MERCHANT);
        }
        console.log('sportvatar purchase finish');
        window.dispatch('waiting-seal');
        setTimeout(function(){
            window.buySportvatarSaleTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}
window.buySportvatarSaleTx = async function(txId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    console.log('sportvatar purchase sealed');
                    window.dispatch('sportvatar-sale-purchased');
                    window.dispatch('loading-completed');
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.buySportvatarSaleTx(txId);
        },5000);
    }
}

window.cancelSportvatarSale = async function(sportvatarId){
    console.log('sportvatar sale cancel start');
    window.dispatch('loading-start');
    try{
        window.txId = await sportvatarSaleCancelTx(sportvatarId);
        console.log('sportvatar sale cancel finish');
        window.dispatch('waiting-seal');
        setTimeout(function(){
            window.cancelSportvatarSaleTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}
window.cancelSportvatarSaleTx = async function(txId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    console.log('sportvatar sale cancel sealed');
                    console.log(transaction);
                    window.dispatch('sportvatar-sale-cancelled');
                    window.dispatch('loading-completed');
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.cancelSportvatarSaleTx(txId);
        },5000);
    }
}


window.createSportvatarSale = async function(sportvatarId, price){
    console.log('sportvatar sale create start');
    window.dispatch('loading-start');
    try{
        let expiry = Math.floor(Date.now()) + (60 * 60 * 24 * 365); //expires in one year
        if(window.currentWallet == 'dapper'){
            window.txId = await sportvatarSaleCreateTx(sportvatarId, price, 'Sportvatar', expiry);
        } else if(window.currentWallet == 'flow'){
            // Flow Wallet: FLOW listing only (never DUC)
            window.txId = await sportvatarSaleCreateFlowTx(sportvatarId, price, 'Sportvatar', expiry);
        } else {
            throw new Error('Connect a wallet first');
        }
        console.log('sportvatar sale create finish');
        window.dispatch('waiting-seal');
        setTimeout(function(){
            window.createSportvatarSaleTx(window.txId);
        },2000);
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}
window.createSportvatarSaleTx = async function(txId){
    try {
        const unsub = await fcl.tx(txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    console.log('sportvatar sale create sealed');
                    console.log(transaction);
                    window.dispatch('sportvatar-sale-created');
                    window.dispatch('loading-completed');
                    //window.getSportvatar(window.user.addr, sportvatarId);
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        console.log(error);
        console.log('caught error on access node!');
        setTimeout(function(){
            window.createSportvatarSaleTx(txId);
        },5000);
    }
}



// Gifts (same transaction for both wallets; for Dapper Wallet it must be approved in the Dapper dashboard)
window.sendGiftTx = async function(txFn, id, address, eventName){
    window.dispatch('loading-start');
    try{
        window.txId = await txFn(id, address);
        window.dispatch('waiting-seal');
        const unsub = await fcl.tx(window.txId).subscribe(transaction => {
            if (fcl.tx.isSealed(transaction)) {
                if(!window.transError(transaction)){
                    window.dispatch(eventName, {id: id, address: address});
                    window.dispatch('loading-completed');
                } else {
                    window.dispatch('transaction-error', {message: window.transError(transaction)});
                }
                unsub();
            }
        });
    } catch(error){
        window.dispatch('transaction-error', {message: error});
    }
}
window.sportvatarSendGift = async function(sportvatarId, address){
    await window.sendGiftTx(sportvatarSendGiftTx, sportvatarId, address, 'sportvatar-gift-sent');
}
window.componentSendGift = async function(componentId, address){
    await window.sendGiftTx(componentSendGiftTx, componentId, address, 'fcomponent-gift-sent');
}

window.getSportvatar = async function(address, sportvatarId){
    await getSportvatarScript(address, sportvatarId).then(function(data){
        //console.log('sportvatar loaded');
        //console.log(data);
        window.dispatch('sportvatar-loaded', {sportvatar: data});
    });
}
window.getSportvatars = async function(address){
    await getSportvatarsScript(address).then(function(data){
        console.log('sportvatars loaded');
        //console.log(data);
        window.dispatch('sportvatar-loaded', {sportvatars: data});
    });
}
window.getSportvatarIds = async function(address){
    await getSportvatarIdsScript(address).then(function(data){
        console.log('sportvatars ids loaded');
        //console.log(data);
        window.dispatch('sportvatar-ids-loaded', {sportvatars: data});
    });
}

window.getTemplate = async function(address, templateId){
    await getTemplateScript(address, templateId).then(function(data){
        console.log('template loaded');
        //console.log(data);
        window.dispatch('template-loaded', {template: data});
    });
}
window.getTemplates = async function(address){
    await getTemplatesScript(address).then(function(data){
        console.log('templates loaded');
        //console.log(data);
        window.dispatch('templates-loaded', {templates: data});
    });
}


window.getPacks = async function(address){
    await getPacksScript(address).then(function(data){
        console.log('packs loaded');
        //console.log(data);
        window.dispatch('packs-loaded', {packs: data});
    });
}

window.checkPackAvailable = async function(id){
    l('check pack available');
    await checkPacksScript(id).then(function(data){
        console.log('pack checked');
        console.log(data);
        window.dispatch('pack-checked', data);
    });
}


// Free packs waiting for the connected wallet: address-bound vouchers (e.g. Flovatar holders), claimable without a code.
// GET /api/packs/pending/{address} returns only {count, claimsOpen}. While claims are paused (claimsOpen = false) only
// admin wallets with a verified session of this same wallet get the preview link; everybody else sees nothing.
// Dispatches 'free-packs-pending' {count, claimsOpen, canClaim, claimUrl} for the banner and the claim page.
window.pendingFreePacks = {count: 0, claimsOpen: false, canClaim: false, claimUrl: '/pack/claim'};
window.checkPendingPacks = async function(){
    const addr = window.user && window.user.addr ? window.user.addr.toLowerCase() : null;
    let detail = {count: 0, claimsOpen: false, canClaim: false, claimUrl: '/pack/claim'};
    if(addr){
        try {
            const response = await axios.get('/api/packs/pending/' + addr);
            const data = response.data || {};
            detail.count = parseInt(data.count, 10) || 0;
            detail.claimsOpen = !!data.claimsOpen;
            detail.canClaim = detail.claimsOpen;
            if(detail.count > 0 && !detail.claimsOpen){
                if(window.loginNoncePromise){
                    await window.loginNoncePromise;
                }
                detail.canClaim = !!window.loginIsAdmin && (window.loginAddress || '').toLowerCase() === addr;
                detail.claimUrl = '/pack/claim?preview=1';
            }
        } catch(error){
            console.log('pending packs check failed');
            console.log(error);
        }
    }
    window.pendingFreePacks = detail;
    window.dispatch('free-packs-pending', detail);
    return detail;
}
// A claimed voucher stays 'reserved' until the indexer sees the pack deposit (~1 min): count it down right away,
// then ask the server again.
window.addEventListener('pack-claimed', function(){
    const p = window.pendingFreePacks || {};
    if(p.count > 0){
        window.pendingFreePacks = Object.assign({}, p, {count: p.count - 1});
        window.dispatch('free-packs-pending', window.pendingFreePacks);
    }
    setTimeout(function(){ window.checkPendingPacks(); }, 90000);
});

// Pack codes v2: exchange a voucher code (or an address-bound free pack) for an on-chain authorisation
// bound to the logged-in wallet. Requires a verified wallet login (server session).
// open = true -> claim and open in one transaction.
window.claimWithVoucher = async function(code, open, retried){
    // The server session must belong to the connected wallet (verified login), otherwise log in first and retry once.
    const connected = window.user && window.user.addr ? window.user.addr.toLowerCase() : null;
    const sessionAddress = window.loginAddress ? window.loginAddress.toLowerCase() : null;
    if(!connected){
        fcl.authenticate();
        return;
    }
    if(sessionAddress !== connected && !retried){
        window.walletLogin(function(){ window.claimWithVoucher(code, open, true); });
        return;
    }
    window.dispatch('loading-start');
    try {
        const response = await axios.post('/pack/authorize-claim', {code: code});
        const a = response.data;
        if (!a || !a.packId) {
            window.dispatch('transaction-error', {message: (a && a.message) || 'No pack available for this code'});
            return;
        }
        if (open) {
            await window.redeemAndOpenPack(a.packId, a.expiry, a.signature);
        } else {
            await window.redeemPack(a.packId, a.expiry, a.signature);
        }
    } catch (error) {
        if (error && error.response && error.response.status == 401 && !retried) {
            window.dispatch('loading-completed');
            window.walletLogin(function(){ window.claimWithVoucher(code, open, true); });
            return;
        }
        const msg = error && error.response && error.response.data && error.response.data.message ? error.response.data.message : error;
        window.dispatch('transaction-error', {message: msg});
    }
}

// Pack codes v2: paid pack. The server signs an authorisation bound to the logged-in buyer for the reserved pack.
window.buyPackV2 = async function(packId, price){
    try {
        const response = await axios.post('/pack/authorize-purchase', {});
        const a = response.data;
        if (!a || String(a.packId) !== String(packId)) {
            window.dispatch('transaction-error', {message: 'Pack reservation expired, please reserve again'});
            return;
        }
        await window.buyPack(a.packId, price, a.expiry, a.signature);
    } catch (error) {
        const msg = error && error.response && error.response.data && error.response.data.message ? error.response.data.message : error;
        window.dispatch('transaction-error', {message: msg});
    }
}
