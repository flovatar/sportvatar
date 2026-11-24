import {DateTime} from "luxon";
import * as fcl from "@onflow/fcl";
import Cookies from "js-cookie";


window.parseDate = function(date){
    return DateTime.fromSeconds(parseFloat(date)).toLocaleString(DateTime.DATE_SHORT);
}
window.parseTime = function(date){
    return DateTime.fromSeconds(parseFloat(date)).toLocaleString(DateTime.TIME_SIMPLE);
}

window.parseDateTime = function(date){
    return window.parseDate(date) + ' ' + window.parseTime(date);
}


window.parseDateTimeString = function(date){
    let tempDate = DateTime.fromMillis(Date.parse(date)).toLocaleString(DateTime.DATE_SHORT);
    let tempTime = DateTime.fromMillis(Date.parse(date)).toLocaleString(DateTime.TIME_SIMPLE);

    return tempDate + ' ' + tempTime;
}

window.logout = function(){
    console.log('logout');
    fcl.unauthenticate();
    window.user = null;
    window.profile = null;
    window.dispatch('logged-out');
    // End the server session too (it belongs to the wallet that logged in)
    // The nonce is fetched again only when the next login needs it (no origin request for anonymous visitors).
    if(window.loginAddress || window.hasServerLoginHint()){
        window.axios.post('/logout').catch(function(){});
    }
    window.resetServerAuth();

    //window.location = '/logout';
}

window.getDiffString = function(start, end){
    let startD = (!start) ? DateTime.now() : DateTime.fromSeconds(parseFloat(start));
    let endD = (!end) ? DateTime.now() : DateTime.fromSeconds(parseFloat(end));
    let diff = endD.diff(startD, ['hours', 'minutes', 'seconds']);
    return diff.hours+'h '+diff.minutes+'m '+Math.round(diff.seconds)+'s';
}

window.formatPrice = function(price){
    return new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2, fractionDigits: 2}).format(parseFloat(price));
}
window.formatSingleDecimal = function(val){
    return new Intl.NumberFormat('en-US', {minimumFractionDigits: 1, maximumFractionDigits: 1, fractionDigits: 1}).format(parseFloat(val));
}

window.formatPriceSmall = function(price){
    let intPrice = Math.floor(parseFloat(price));
    let decimals = '' + new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2, fractionDigits: 2}).format(parseFloat(price) - intPrice);
    decimals = decimals.replace('0.','');
    return intPrice + '.<small>' + decimals + '</small>';
}
window.formatPriceNoDecimal = function(price){
    return new Intl.NumberFormat('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0, fractionDigits: 0}).format(parseFloat(price));
}
window.formatPriceNoDecimalNoComma = function(price){
    return Math.floor(parseFloat(price));
}

window.floatArg = function(val){
    return new Intl.NumberFormat('en-US', {useGrouping: false, minimumFractionDigits: 2, maximumFractionDigits: 2, fractionDigits: 2}).format(parseFloat(val));
}

window.floatArgFull = function(val){
    return new Intl.NumberFormat('en-US', {useGrouping: false, minimumFractionDigits: 6, maximumFractionDigits: 6, fractionDigits: 6}).format(parseFloat(val));
}

window.wrapSvg = function(svg){
    svg = (!svg) ? '<rect x="0" y="0" width="3000" height="3000" style="fill:#fff;fill-opacity:0;"/>' : svg;
    return window.svgOpen+svg+window.svgClose;
}

window.getSvg = function(templateId){
    let template = _.find(window.templates, function (x) { return x.flow_id == templateId });
    return (template) ? window.wrapSvg(template.svg) : '';
}


window.getRarityScore = function(sportvatar){
    if(!sportvatar || !sportvatar.stats){
        return '0.0';
    }

    let stats = sportvatar.stats;

    let overall = 0.0;
    let count = 0;

    _.each(stats, val => {
        overall += parseInt(val,10);
        count++;
    });

    if(count <= 0){
        return '0.0';
    }


    return window.formatSingleDecimal(overall/(count*2));
}


window.getSportvatarRarityText = function(collectible, templates){
    if(!collectible || !collectible.layers){
        return 0;
    }

    let legendaryCount = 0;
    let epicCount = 0;
    let rareCount = 0;

    _.each(collectible.layers, item => {
        let template = _.find(templates, temp => { return temp.flow_id == item; });
        if(template){
            if(template.rarity == 'legendary'){
                legendaryCount++;
            } else if(template.rarity == 'epic'){
                epicCount++;
            } else if(template.rarity == 'rare'){
                rareCount++;
            }
        }
    });

    if(legendaryCount > 0){
        return 'legendary';
    }
    if(epicCount > 0){
        return 'epic';
    }
    if(rareCount > 0){
        return 'rare';
    }
    return 'common';
}




window.getTemplateScore = function(templateId){
    if(!window.templates){
        return 0;
    }
    let template = _.find(window.templates, function (x) { return x.flow_id == templateId });

    return (template) ? template.max_mintable : null;

}


window.writeHashParams = function(params){
    let hash = '';
    _.each(params, function(val, key){
        hash += key + '=' + val + '&';
    });
    hash = _.trim(hash,'&');
    hash = '#' + hash;
    if(window.location.hash != hash){
        //console.log('write hash params');
        //console.log(window.location.hash);
        //console.log(hash);
        window.history.pushState(params, null, hash);
    }
}

window.getLayersFromCombination = function(combination) {
    let templateIds = {};
    combination = combination.replace('S1-', '');
    let params = _.split(combination, '-');
    _.each(params, function(param) {
        let paramArr = _.split(param, '_');
        if (paramArr.length == 2) {
            let tempLevel = paramArr[0].replace('L', '');
            let tempVal = paramArr[1] ? paramArr[1] : null;
            templateIds[tempLevel] = tempVal;
        }
    });
    return templateIds;
}

window.removeCookiesList = [
    'QueueITAccepted-SDFrts345E-V3_flovatartestnet',
    'QueueITAccepted-SDFrts345E-V3_flovatartestpresale',
    'QueueITAccepted-SDFrts345E-V3_flovatarwhitelist',
    'QueueITAccepted-SDFrts345E-V3_flovatarpublicsale',
    'QueueITAccepted-SDFrts345E-V3_flovatarstresstest',
    'QueueITAccepted-SDFrts345E-V3_flovatarflobitpre',
    'QueueITAccepted-SDFrts345E-V3_flovatarsaleflobit',
    'QueueITAccepted-SDFrts345E-V3_flovatarflobittest'
];

window.removeQueueCookies = function(){

    for (let cookieName of window.removeCookiesList) {
        Cookies.remove(cookieName);
    }
}

window.l = function(data){
    console.log(data);
}
