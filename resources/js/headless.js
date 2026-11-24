/**
 * Headless (static) mode for pages served from R2 by the sportvatar-headless Worker (docs/HEADLESS.md).
 *
 * window.HEADLESS is printed by the layout only when a page is rendered by `php artisan headless:build`;
 * on the classic server-rendered site it is undefined and every helper below falls back to the legacy
 * behaviour, so the same bundle serves both.
 *
 * - searchApi(): list searches go to the cacheable GET /api/v2/... endpoints (same params + response shape
 *   as the legacy POST searches) instead of POST.
 * - HEADLESS_ROUTE: shells read the wallet (collection) from the URL.
 * - resolveItemOwner(): item pages carry a DB snapshot of the owner + listing; the real owner and the live
 *   storefront listing are confirmed ON CHAIN (one Cadence script over the candidates: URL hint, snapshot owner,
 *   connected wallet, API owner).
 * - itemUrl(): canonical owner-less Sportvatar URLs in headless mode (/sportvatars/{id}).
 */
import {findItemOwnerScript} from "./flow/find-item-owner.script";

const H = window.HEADLESS && window.HEADLESS.mode === 'static' ? window.HEADLESS : null;
window.isHeadless = !!H;

const API_SEGMENTS = {sportvatar: 'sportvatars', sportbit: 'sportbits'};
const ADDRESS_RE = /^0x[a-f0-9]{16}$/;

// ---- routes of shell pages (evaluated before Alpine starts: app.js requires this module first)
(function(){
    const route = {address: '', blocked: false};
    const m = window.location.pathname.match(/^\/collection\/([^\/?#]+)\/?$/);
    if(m){
        const seg = decodeURIComponent(m[1]).toLowerCase();
        if(ADDRESS_RE.test(seg)){
            route.address = seg;
        }
    }
    route.blocked = !!(H && route.address && (H.blocked || []).map(a => (a || '').toLowerCase()).indexOf(route.address) >= 0);
    window.HEADLESS_ROUTE = route;
})();

// ---- searches: legacy POST path -> v2 GET endpoint
const SEARCH_MAP = {
    '/search': '/api/v2/marketplace',
    '/locker-room/search': '/api/v2/locker-room',
    '/transactions/search': '/api/v2/transactions',
};
window.searchApi = function(path, params){
    if(H && SEARCH_MAP[path]){
        const clean = {};
        Object.keys(params || {}).forEach(function(k){
            const v = params[k];
            if(v !== null && v !== undefined && v !== ''){
                clean[k] = v;
            }
        });
        return window.axios.get((H.apiBase || '') + SEARCH_MAP[path], {params: clean});
    }
    return window.axios.post(path, params);
};

/** Sportvatar page URL: canonical /sportvatars/{id} in headless mode, legacy owner URL otherwise. */
window.itemUrl = function(segment, id, address){
    if((H && segment === 'sportvatars') || !address){
        return '/' + segment + '/' + id;
    }
    return '/' + segment + '/' + id + '/' + address;
};

/** After a transfer/purchase: headless pages get the new owner as a hint (?owner=) for the chain check. */
window.itemUrlAfterTx = function(segment, id, address){
    if(H && segment === 'sportvatars'){
        return '/' + segment + '/' + id + (address ? '?owner=' + encodeURIComponent(address) : '');
    }
    return '/' + segment + '/' + id + '/' + address;
};

// ---- item pages: confirm the owner (+ live listing) on chain
function uniq(list){
    const out = [];
    list.forEach(function(a){
        a = (a || '').toLowerCase();
        if(ADDRESS_RE.test(a) && out.indexOf(a) < 0){
            out.push(a);
        }
    });
    return out;
}

async function connectedAddress(){
    try {
        const u = await window.fcl.currentUser().snapshot();
        return u && u.addr ? u.addr : null;
    } catch(e){
        return null;
    }
}

window.fetchItemSnapshot = async function(type, id){
    const r = await window.axios.get((H ? (H.apiBase || '') : '') + '/api/v2/' + API_SEGMENTS[type] + '/' + id);
    return r.data;
};

/** Storefront listing read on chain -> the sale-offer shape the item components use (listing_id, price, currency). */
function chainListing(found){
    if(!found || found.listingId === null || found.listingId === undefined){
        return null;
    }
    const vault = found.vault || '';
    return {
        listing_id: '' + found.listingId,
        price: parseFloat(found.price),
        currency: vault.indexOf('.FlowToken.') >= 0 ? 'FLOW' : 'DUC',
        is_dapper: vault.indexOf('.FlowToken.') < 0,
        flow_address: found.owner,
        onChain: true,
    };
}

/**
 * Returns the address that holds the item on chain (or the given address when it can't be confirmed).
 * Dispatches `item-owner-resolved` {address} when it differs from the build-time owner and `item-listing-resolved`
 * {listing|null} with the live storefront listing, so the Alpine component updates.
 */
window.resolveItemOwner = async function(type, id, address){
    if(!H || !H.item || H.item.type !== type || ('' + H.item.id) !== ('' + id)){
        return address;
    }
    const tried = [];
    const check = async function(cands){
        cands = cands.filter(a => tried.indexOf(a) < 0);
        if(!cands.length){
            return null;
        }
        cands.forEach(a => tried.push(a));
        try {
            return await findItemOwnerScript(type, id, cands);
        } catch(e){
            console.log('owner check failed', e);
            return null;
        }
    };
    const hint = new URLSearchParams(window.location.search).get('owner');
    let found = await check(uniq([hint, address, H.item.owner, await connectedAddress()]));
    if(!found){
        // DB snapshot (edge-cached 60 s) knows transfers the static page missed
        try {
            const snapshot = await window.fetchItemSnapshot(type, id);
            found = await check(uniq([snapshot && snapshot.owner]));
        } catch(e){
            console.log('snapshot fetch failed', e);
        }
    }
    const owner = found ? found.owner : address;
    if(owner && owner !== address){
        window.dispatchEvent(new CustomEvent('item-owner-resolved', {detail: {address: owner}}));
    }
    if(found){
        window.dispatchEvent(new CustomEvent('item-listing-resolved', {detail: {listing: chainListing(found)}}));
    }
    window.dispatchEvent(new CustomEvent('item-owner-confirmed', {detail: {address: owner, onChain: !!found}}));
    return owner;
};
