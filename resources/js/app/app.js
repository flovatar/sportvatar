import Alpine from "alpinejs";
import {DateTime} from "luxon";
import Parallax from "parallax-js";
import {config} from "@onflow/fcl";
import {toSvg} from "jdenticon";

Alpine.data('appData', () => ({
    user: null,
    profile: null,
    loading: false,
    waitingSeal: false,
    sealCompleted: false,
    open: false,
    showMoonpay: false,
    showSubscription: true,
    sealingPack: false,
    packPurchased: false,
    jsLoaded: false,
    showInitPopup: false,
    showInitMetadataPopup: false,
    emailSubscription: '',
    showTransactionError: false,
    showTransactionErrorMessage: false,
    showMaintenance: false,
    showLoginResult: false,
    showReserveResult: false,
    transactionErrorMessage: '',
    showProfileName: true,
    userCheckLoading: true,
    showBlacklistModal: false,
    currentWallet: Alpine.$persist('dapper'),
    packRedeemCode: Alpine.$persist(''),
    isVipUser: false,
    remainingMinutesToSale: 0,
    fubPreviewMode: false,
    activeSkin: 0,
    consentSportvatar: false,
    consentRcs: false,
    init() {
        //console.log('init');
        //console.log(this.currentWallet);
        window.currentWallet = this.currentWallet;
        if(window.vipCookieVal == 'hdfh55yyerg5g34t34t344'){
            this.isVipUser = true;
            let saleDateTime = DateTime.utc(2022, 9, 26, 16, 0);
            let curDateTime = DateTime.utc();
            this.remainingMinutesToSale = Math.ceil(saleDateTime.diff(curDateTime, 'minutes').minutes);
            //console.log('remaining time until sale');
            //console.log(this.remainingMinutesToSale);
        }


        var sceneSalesHero = document.getElementById('sales-hero-box');
        if(sceneSalesHero){
            var parallaxInstance = new Parallax(sceneSalesHero);
        }

    },
    nextSkin(){
        this.activeSkin++;
        if(this.activeSkin >= this.availableSkins.length){
            this.activeSkin = 0;
        }
    },
    currentSkinClass(){
        let jsHidden = this.jsLoaded ? 'jsloaded ' : '';
        return jsHidden + (this.availableSkins[this.activeSkin] ? 'theme--'+this.availableSkins[this.activeSkin] : '');
    },
    connectWallet(){
        l('connect wallet');
        fcl.authenticate();
    },
    getToasterClass(){
        return this.loading || this.waitingSeal ? 'active' : '';
    },
    copyAddress(){
        if(navigator.clipboard && this.address()) {
            navigator.clipboard.writeText(this.address());
        }
    },
    logoutAccount(){
        window.currentWallet = null;
        this.currentWallet = null;
        window.logout();
    },
    handleTransactionErrorMessage(message){
        this.loading = false;
        this.waitingSeal = false;
        this.sealCompleted = false;
        this.showTransactionError = true;
        this.showTransactionErrorMessage = false;
        this.transactionErrorMessage = message;
    },
    handleAddressFound(data){
        console.log('Handle address found');
        console.log(data);
    },
    submitSubscription() {
        window.sendSubscription(this.emailSubscription, this.consentSportvatar, this.consentRcs);
    },
    moonpaySignature: '',
    moonpayUrl(){
        return window.moonpayDomain + window.moonpayParams + '&apiKey=' + window.moonpayPublicKey + '&walletAddress=' + this.address() + '&signature=' + this.moonpaySignature;
    },
    startAccountInit(){
        this.showInitPopup = false;
        window.loadInitUser();
    },
    startAccountMetadataInit(){
        this.showInitMetadataPopup = false;
        window.loadInitMetadataViews();
    },
    cancelAccountMetadataInit(){
        this.showInitMetadataPopup = false;
        window.logout();
    },
    cancelAccountInit(){
        // Flow Wallet setup is optional (transactions create what they need); just close the popup
        this.showInitPopup = false;
    },
    // Wallet / listing currency helpers (see actions.js): Dapper Wallet trades in DUC (shown as USD), Flow Wallet in FLOW
    isDapperWallet(){
        return this.currentWallet == 'dapper';
    },
    walletCurrency(){
        return this.user && this.user.addr ? window.walletCurrency(this.currentWallet) : null;
    },
    walletCurrencyLabel(){
        return window.currencyLabel(this.walletCurrency());
    },
    walletName(){
        return window.walletName(this.currentWallet);
    },
    listingCurrency(offer){
        return window.listingCurrency(offer);
    },
    listingCurrencyLabel(offer){
        return window.currencyLabel(window.listingCurrency(offer));
    },
    // true when the connected wallet can pay this listing (no wallet connected = true, the button asks to connect)
    canPayListing(offer){
        if(!this.user || !this.user.addr){
            return true;
        }
        return window.canPayListing(offer, this.currentWallet);
    },
    requiredWalletName(offer){
        return window.listingCurrency(offer) === 'FLOW' ? 'Flow Wallet' : 'Dapper Wallet';
    },
    categories: ['body', 'clothing', 'nose', 'mouth', 'facialHair', 'hair', 'eyes', 'accessory', 'eyeglasses', 'hat', 'background'],
    basicCategories: ['body', 'clothing', 'nose', 'mouth', 'facialHair', 'hair', 'eyes'],
    basicFlobotCategories: ['body', 'head', 'arms', 'legs', 'face'],
    optionalCategories: ['eyeglasses', 'hat', 'accessory', 'background'],
    rarityBoost: ['rare', 'epic', 'legendary'],
    categoriesLabel: {
        spark: 'Spark',
        boost: 'Boost',
        body: 'Body',
        hair: 'Hair',
        eyes: 'Eyes',
        nose: 'Nose',
        mouth: 'Mouth',
        clothing: 'Clothing',
        facialHair: 'Facial Hair',
        eyeglasses: 'Eyeglasses',
        hat: 'Hat',
        accessory: 'Accessory',
        background: 'Background'
    },
    rarities: [
        {
            id: 'legendary',
            name: 'Legendary',
            css: 'bg-pink-700',
            border: 'border-black-100',
            mint: 10,
            score: 90,
            order: 3
        },
        {
            id: 'epic',
            name: 'Ultra Rare',
            css: 'bg-yellow-100',
            border: 'border-black-100',
            mint: 100,
            score: 80,
            order: 2
        },
        {
            id: 'rare',
            name: 'Rare',
            css: 'bg-blue-1300',
            border: 'border-black-100',
            mint: 500,
            score: 60,
            order: 1
        },
        {
            id: 'common',
            name: 'Common',
            css: 'bg-green-50',
            border: 'border-black-100',
            mint: 99999999999,
            score: 40,
            order: 0
        }
    ],
    address(){
        return this.user ? this.user.addr : null;
    },
    name(){
        return (this.profile && this.profile.name) ? this.profile.name : null;
    },
    loggedIn(){
        return this.user && this.profile && this.profile.balance ? true : false;
    },
    userAvatar(){
        return this.loggedIn() ? toSvg(this.user.addr, 50) : toSvg('Anon', 50);
    },
    packRequestUrl(){
        return this.loggedIn() ? '/pack/request/'+this.user.addr : '#';
    },
    collectionUrl(){
        return this.loggedIn() ? '/collection/'+this.user.addr : '#';
    },
    lastRarity(){
        return this.rarities[this.rarities.length-1];
    },
    getRarity(val){
        let rarity = _.find(this.rarities, (el) => { return val <= el.mint;});

        return rarity ? rarity.name : this.lastRarity().name;
    },
    getRarityByName(val){
        return _.find(this.rarities, (el) => { return val == el.id;});
    },
    getRarityByScore(val){
        let rarity = _.find(this.rarities, (el) => { return val >= el.score;});

        return rarity ? rarity.name : this.lastRarity().name;
    },
    getRarityClass(val){
        let rarity = _.find(this.rarities, (el) => { return val <= el.mint;});

        return rarity ? rarity.css : this.lastRarity().css;
    },
    getRarityClassByScore(val){
        let rarity = _.find(this.rarities, (el) => { return val >= el.score;});

        return rarity ? rarity.css : this.lastRarity().css;
    },
    getRarityClassByName(val){
        let rarity = _.find(this.rarities, (el) => { return val == el.id;});

        return rarity ? rarity.css : this.lastRarity().css;
    },
    getRarityBorderByName(val){
        let rarity = _.find(this.rarities, (el) => { return val == el.id;});

        return rarity ? rarity.border : this.lastRarity().border;
    },
    filterTemplates(category){
        return _.filter(this.templates, (el) => { return el.category == category; })
    },
    filterTemplate(id){
        return _.find(this.templates, (el) => { return el.flow_id == parseInt(id, 10); })
    },
    getTemplateNameById(id){
        let template = this.filterTemplate(id);
        return template ? template.name : '';
    },
    getTemplateCategoryById(id){
        let template = this.filterTemplate(id);
        return template ? (template.category == 'boost' ? 'booster' : template.category) : '';
    },
    getTemplateRarityById(id){
        let template = this.filterTemplate(id);
        return template ? template.rarity : '';
    },
    signMessage(message){
        window.createSignature(message);
    },
    dapperMessageSigned(){

    },
    showBuyButton(category){
        return (category == 'boost' || category == 'spark' || category == 'hat' || category == 'eyeglasses' || category == 'accessory' || category == 'background');
    },
    packRedeemId(){
        if(this.packRedeemCode){
            let packCodeArr = this.packRedeemCode.split('-');
            if(packCodeArr.length == 2) {
                return packCodeArr[0];
            }
        }
        return '';
    },
    packRedeemSecret(){
        if(this.packRedeemCode){
            let packCodeArr = this.packRedeemCode.split('-');
            if(packCodeArr.length == 2) {
                return packCodeArr[1];
            }
        }
        return '';
    },
    // Pack codes v2: the code is a voucher (XXXXX-XXXXX-XXXXX-XXXXX) exchanged server-side for an authorisation
    // bound to the connected wallet; an empty code claims a pack reserved for this wallet (Flovatar holders).
    claimFreePack(){
        window.dispatch('loading-started');
        window.claimWithVoucher(this.packRedeemCode || '', false);
    },
    claimAndOpenFreePack(){
        window.dispatch('loading-started');
        window.claimWithVoucher(this.packRedeemCode || '', true);
    },
    parseNumberSportbit(name){
        return name ? name.replace('Number ', '') : 'N/A';
    },
    getTemplateUrl(flow_id){
        return '/api/image/template/'+flow_id;
    }
}));
