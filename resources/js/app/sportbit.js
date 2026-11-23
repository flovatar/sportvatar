import Alpine from "alpinejs";

Alpine.data('componentData', (address, templates, componentSaleOffer) => ({
    templates: templates,
    address: address,
    componentSaleOffer: componentSaleOffer,
    isLoading: true,
    component: null,
    currentPrice : null,
    currentPriceChange : null,
    changePriceOpen: false,
    showSale: false,
    showGift: false,
    currentGiftAddress: '',
    showZoomModal: false,
    init(){
        if(this.componentSaleOffer) {
            this.currentPrice = parseFloat(this.componentSaleOffer.price);
            this.currentPriceChange = this.currentPrice;
        }
    },
    showSaleBtn(){
        return this.isSale() && !this.isOwned() && !this.showSale;
    },
    getDapperListing(){
        return (this.sportvatarSaleOffer);
    },
    isSale() {
        return (this.componentSaleOffer && this.component);
    },
    isComponent() {
        return !this.isSale();
    },
    isOwned(){
        if(this.user && this.address){
            return this.user.addr == this.address;
        }
        return null;
    },
    isFlobit(){
        return (this.component && this.component.layer != 0 && this.component.layer != 13);
    },
    getMarketplaceUrl(){
        if(this.category() == 'boost' || this.category() == 'land' || this.category() == 'flobotkit' || this.category() == 'fub'){
            return '/marketplace#templatecat=' + this.category() + '&rarity=' + this.rarity();
        }  else {
            return '/marketplace#templatecat=flobits&templateid=' + this.templateId()
        }
    },
    meta(){
        if(this.component){
            return this.component;
        }
        return null;
    },
    template(){
        if(!this.meta()){
            return null;
        }
        return  _.find(this.templates, { 'flow_id': parseInt(this.meta().templateId, 10)});
    },
    svg(){
        if(!this.meta()){
            return '';
        }
        if(this.component && this.component.layer == 13){
            return '<div class="img-hold"><span class="number-thumb white">'+this.parseNumberSportbit(this.component.name)+'</span></div>'
        }
        let template = _.find(this.templates, { 'flow_id': parseInt(this.meta().templateId, 10)});
        return template ? wrapSvg(template.svg) : '';
    },
    name(){
        if(!this.meta()){
            return '';
        }
        return this.category() == 'boost' ? this.meta().name + ' Booster' : this.meta().name;
    },
    description(){
        return this.meta() ? this.meta().description : '';
    },
    flobitId(){
        return this.meta() ? this.meta().id : '';
    },
    templateId(){
        return this.meta() ? this.meta().templateId : '';
    },
    mint(){
        let template = this.template();
        return (this.meta() && template) ? this.meta().mint + ' / ' + template.max_mintable : '';
    },
    category(){
        return this.meta() ? this.meta().layer : '';
    },
    color(){
        return this.meta() ? this.meta().color : '';
    },
    series(){
        return this.template() ? this.template().series : '';
    },
    burned(){
        return this.template() ? this.template().sportvatar_components_count : '';
    },
    maxMintable(){
        return this.template() ? this.template().max_mintable : '';
    },
    rarity(){
        return this.template() ? this.template().rarity : '';
    },
    loggedIn(){
        return (this.user && this.profile && this.profile.balance);
    },
    createSale(){
        if(this.component && this.isOwned() && this.currentPrice) {
            console.log('create component sale');
            window.createComponentSale(this.component.id, this.currentPrice);
        }
    },
    cancelSale(){
        if(this.isSale() && this.isOwned()) {
            console.log('cancel sale');
            window.cancelComponentSale(this.componentSaleOffer.listing_id);
        }
    },
    buySale(){
        if(this.isSale() && !this.isOwned()) {
            console.log('buy sale');
            if(this.componentSaleOffer && this.componentSaleOffer.listing_id && this.componentSaleOffer.price)
            window.buyComponentSale(this.address, this.componentSaleOffer.listing_id, this.componentSaleOffer.price, window.listingCurrency(this.componentSaleOffer));
        }
    },
    offerCurrencyLabel(){
        return window.currencyLabel(window.listingCurrency(this.componentSaleOffer));
    },
    canBuyOffer(){
        return this.canPayListing(this.componentSaleOffer);
    },
    sendGift(){
        if(this.component && this.isOwned() && this.currentGiftAddress) {
            window.componentSendGift(this.component.id, this.currentGiftAddress);
        }
    },
    handleGiftSent(){
        if(this.component && this.currentGiftAddress){
            window.location = '/components/'+this.component.id+'/'+this.currentGiftAddress;
        }
    },
    handlePurchaseCompleted(){
        console.log('purchase completed!');
        window.location = '/components/'+this.component.id+'/'+window.user.addr;
    },
    handleOfferCreated(){
        console.log('sale created!');
        this.componentSaleOffer = {price: this.currentPrice, currency: window.walletCurrency()};
    },
    handleOfferDeleted(){
        console.log('sale deleted!');
        this.currentPrice = null;
        this.componentSaleOffer = null;
    }
}));

window.initComponent = function(dispatch, address, componentId){
    window.dispatch = dispatch;
    window.getComponent(address, componentId);
}
