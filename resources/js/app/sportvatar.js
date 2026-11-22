import Alpine from "alpinejs";

Alpine.data('sportvatarData', (address, sportvatarId, series, templates, sportvatarSales, sportvatarSaleOffer, revealStats) => ({
    templates: templates,
    series: series,
    address: address,
    sportvatarId: sportvatarId,
    sportvatarSales: sportvatarSales,
    sportvatarSaleOffer: sportvatarSaleOffer,
    isLoading: true,
    sportvatar: null,
    sale: null,
    components: null,
    currentPrice : null,
    currentPriceChange : null,
    changePriceOpen: false,
    optionalIds: {},
    optionalTempIds: {},
    sportvatarName: '',
    searchAddress: '',
    foundAddress: '',
    sportvatarStory: '',
    showSale: false,
    showGift: false,
    currentGiftAddress: '',
    currentCreatorName: null,
    currentOwnerName: null,
    assetSelect: 'svg',
    showZoomModal: false,
    revealStats: revealStats,
    init(){
        let self = this;

        if(this.sportvatarSaleOffer) {
            this.currentPrice = parseFloat(this.sportvatarSaleOffer.price);
            this.currentPriceChange = this.currentPrice;
        }


    },


    seriesLayers(){
        let layersArr = this.series[0] ? this.series[0].layers : [];

        return _.sortBy(layersArr, function(item){
            return parseInt(item.id, 10);
        });
    },
    sportbitLayers(){
        return _.filter(this.seriesLayers(), {isAccessory: true});
    },
    visibleSportbitLayers(){
        let self = this;
        return _.filter(this.sportbitLayers(), (item) => {return !self.hideEmptyLayer(item);});
    },
    traitsLayers(){
        return _.filter(this.seriesLayers(), {isAccessory: false});
    },
    isLayerOptional(layerId){
        let allLayers = this.seriesLayers();
        return allLayers && allLayers[layerId] && allLayers[layerId].isAccessory;
    },

    getLayerName(layerId){
        let layer = _.find(this.seriesLayers(), {id: ''+layerId});
        return layer ? layer.name : '';
    },
    isDapperSale(){
        return (this.sportvatarSaleOffer);
    },
    showSaleBtn(){
        return this.isSale() && !this.isOwned() && !this.showSale;
    },
    getDapperListing(){
        return (this.sportvatarSaleOffer) ? true : false;
    },
    claimDust(){
        window.claimSportvatarDust(this.sportvatarId);
    },
    claimAirdrop(){
        window.claimSportvatarAirdrop(this.sportvatarId);
    },
    combinationUrl(){
        let extraParams = '';
        return '/builder#combination='+this.combination()+extraParams;
    },
    loadSportvatar(sportvatar){
        this.sportvatar = sportvatar;
        this.loadOptionals();
        this.isLoading = false;
        if(this.sportvatar && this.creatorAddress()){
            window.getAddressProfile(this.creatorAddress());
            window.getAddressOwnerProfile(this.address);
        }
    },
    loadSale(sale){
        this.sale = sale;
        this.isLoading = false;
        if(this.sale) {
            this.currentPrice = parseFloat(this.sale.price);
            this.currentPriceChange = this.currentPrice;
            if(this.creatorAddress()){
                window.getAddressProfile(this.creatorAddress());
                window.getAddressOwnerProfile(this.address);
            }
        }
    },
    loadCreatorName(profile){
        if(profile && profile.name){
            this.currentCreatorName = profile.name;
        }
    },
    loadOwnerName(profile){
        if(profile && profile.name){
            this.currentOwnerName = profile.name;
        }
    },
    sendGift(){
        if(this.sportvatar && this.isOwned() && this.currentGiftAddress) {
            window.sportvatarSendGift(this.sportvatar.id, this.currentGiftAddress);
        }
    },
    isSale() {
        return (this.isDapperSale() && this.sportvatar) || (this.sale && !this.sportvatar);
    },
    isSportvatar() {
        return !this.isSale();
    },
    isOwned(){
        if(this.user && this.address){
            return this.user.addr == this.address;
        }
        return null;
    },
    meta(){
        if(this.sportvatar){
            return this.sportvatar;
        }
        return null;
    },
    hasSportbit(layerId){
        let layers = this.sportvatar ? this.sportvatar.layers : {};
        return (layers[layerId]) ? true : false;
    },
    hasSportbits(){
        return this.components && this.components.length > 0;
    },
    hasSportbitsEquipped(){
        if(!this.sportvatar){
            return false;
        }
        let count = 0;
        let self = this;
        _.each(this.sportbitLayers(), layer => {
            let temp = _.find(self.sportvatar.layers, (val,key) => {
                return key == layer.id;
            });
            if(temp){
                count++;
            }
        });
        return count > 0;
    },
    svg(){
        let meta = this.meta();
        if(!meta){
            return '';
        }
        let self = this;
        let finalSvg = '';
        _.each(this.seriesLayers(), layer => {
            if(layer.id > 0){
                if(layer.isAccessory == true && (self.optionalTempIds[layer.id] || self.optionalIds[layer.id])){
                    let template = null;
                    if(self.optionalIds[layer.id]){
                        template = this.getTemplate(self.optionalIds[layer.id]);
                    } else {
                        template = _.find(this.templates, { 'flow_id': parseInt(self.optionalTempIds[layer.id], 10)});
                    }
                    finalSvg += template ? template.svg : '';
                } else if(layer.isAccessory == false){
                    let tempId = _.find(self.sportvatar.layers, (val,key) => {
                        return key == layer.id;
                    });
                    if(tempId){
                        let template = _.find(this.templates, { 'flow_id': parseInt(tempId, 10)});
                        finalSvg += template ? template.svg : '';
                    }
                }
            }
        });

        return window.wrapSvg(finalSvg)
    },
    getOverallScore(){
        if(!this.sportvatar){
            return '0.0';
        }

        let stats = this.sportvatar.stats;

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
    },
    name(){
        if(this.isSportvatar()){
            return this.meta() ? this.meta().name : '';
        }
        return this.meta() ? this.meta().metadata.name : '';
    },
    story(){
        if(this.isSportvatar()){
            return this.meta() && this.meta().bio.story ? this.meta().bio.story : '';
        }
        return this.meta() ? this.meta().metadata.bio.story : '';
    },
    position(){
        if(this.isSportvatar()){
            return this.meta() && this.meta().bio.position ? this.meta().bio.position : '';
        }
        return this.meta() ? this.meta().metadata.bio.position : '';
    },
    mint(){
        return this.meta() ? this.meta().mint : '';
    },
    combination(){
        return this.meta() ? this.meta().combination : '';
    },
    combinationShort(){
        return this.combination() ? this.combination().replace(/\-|\_/g, '') : '';
    },
    sportvatarLayers(){
        return this.meta() ? this.meta().layers : '';
    },
    sportvatarStats(){
        return this.meta() ? this.meta().stats : '';
    },
    creatorAddress(){
        if(this.currentCreatorName){
            return this.currentCreatorName;
        }
        return this.meta() ? this.meta().creatorAddress : '';
    },
    ownerAddress(){
        if(this.currentOwnerName){
            return this.currentOwnerName;
        }
        return this.address;
    },
    creatorAddressName(){
        if(this.currentCreatorName){
            return this.currentCreatorName+'.find';
        }
        return this.meta() ? this.meta().creatorAddress : '';
    },
    ownerAddressName(){
        if(this.currentOwnerName){
            return this.currentOwnerName+'.find';
        }
        return this.address;
    },
    rarityText(){
        return this.meta() ? window.getRarityScore(this.meta()) : 0;
    },
    getRarityCountText(type){
        return this.meta().rarity ? this.meta().rarity : '';
    },
    getRarityClass(){
        return this.meta() && this.meta().rarity ? this.meta().rarity : 'common';
    },
    loggedIn(){
        return (this.user && this.profile && this.profile.balance);
    },
    createSale(){
        if(this.sportvatar && this.isOwned() && this.currentPrice) {
            console.log('create sportvatar sale');
            window.createSportvatarSale(this.sportvatar.id, this.currentPrice);
        }
    },
    cancelSale(){
        if(this.isSale() && this.isOwned()) {
            console.log('cancel sale');
            window.cancelSportvatarSale(this.sportvatarSaleOffer.listing_id);
        }
    },
    buySale(){
        if(this.isSale() && !this.isOwned()) {
            console.log('buy sale');
            if(this.sportvatarSaleOffer && this.sportvatarSaleOffer.listing_id && this.sportvatarSaleOffer.price){
                let saleId = this.sportvatarSaleOffer.listing_id;
                let price = this.sportvatarSaleOffer.price;
                window.buySportvatarSale(this.address, saleId, price);
            }
        }
    },
    setBackground(){
        console.log('set background');
        if(this.optionalIds['background'] && this.isOwned() && this.isSportvatar() && this.loggedIn()){
            //console.log('set background execute');
            window.sportvatarAddBackground(this.sportvatar.id, this.optionalIds['background']);
        }
    },
    loadOptionals(){
        console.log('load optionals');
        let self = this;
        _.each(this.seriesLayers(), layer => {
            if (layer.id > 0) {
                if (layer.isAccessory == true) {
                    let tempId = _.find(self.sportvatar.layers, (val, key) => {
                        return key == layer.id;
                    });
                    if (tempId) {
                        self.optionalTempIds[layer.id] = tempId;
                    }

                }
            }
        });
    },
    setOptional(category){
        console.log('set optional');
        if(this.optionalIds[category] && this.isOwned() && this.isSportvatar() && this.loggedIn()){
            //console.log('set optional execute');
            window.sportvatarAddSportbit(this.sportvatar.id, this.optionalIds[category]);
        }
    },
    removeOptional(category){
        console.log('set optional');
        if(this.hasSportbit(category) && this.isOwned() && this.isSportvatar() && this.loggedIn()){
            //console.log('set optional execute');
            window.sportvatarRemoveSportbit(this.sportvatar.id, category);
        }
    },
    refreshAvatar(){
        if(this.sportvatar) {
            window.getSportvatar(address, this.sportvatar.id);
        }
    },
    filterComp(category){
        return _.filter(this.components, function(comp){ return comp.layer == category; }).sort(function (l,r){
            if(l.name > r.name){
                return 1;
            } else if(l.name < r.name){
                return -1;
            } else {
                return 0;
            }
        });
    },
    getTemplate(id){
        let comp = _.find(this.components, function(item) { return parseInt(item.id, 10) == parseInt(id, 10); });
        if (comp) {
            return _.find(this.templates, { 'flow_id': parseInt(comp.templateId, 10)});
        }
        return null;
    },
    getTemplateId(id){
        let temp = this.getTemplate(id);
        return temp ? temp.flow_id : null;
    },
    setDiscordProfile(){
        /*
        client.user.setAvatar('./avatar.png')
            .then(user => console.log(`New avatar set!`))
            .catch(console.error);
            */
    },
    getSportvatarTemplateIds(){
        var meta = this.meta();
        if(meta && this.combination()){
            let arr = window.signatureToArray(this.combination());
            return arr;
        } else {
            return null;
        }
    },
    getSportvatarTemplates(){
        let templateIds = this.getSportvatarTemplateIds();
        if(templateIds) {
            let self = this;
            let templates = _.map(templateIds, function (item) {
                return self.getTemplate(item);
            });
            return templates;
        } else {
            return {};
        }
    },
    getSportvatarBasicTemplates(){
        if(this.sportvatarLayers()) {
            let self = this;
            let templates = [];
            _.each(this.sportvatarLayers(), function (item) {
                let template = _.find(self.templates, function(templ) { return templ.flow_id == parseInt(item,10); });
                if(template && !self.isLayerOptional(template.layer)){
                    templates.push(template);
                }
            });
            return templates;
        } else {
            return [];
        }
    },
    getSportvatarOptionalTemplates(){
        if(this.sportvatarLayers()) {
            let self = this;
            let templates = [];
            _.each(this.sportvatarLayers(), function (item) {
                let template = _.find(self.templates, function(templ) { return templ.flow_id == parseInt(item,10); });
                if(template && self.isLayerOptional(template.layer)){
                    templates.push(template);
                }
            });
            return templates;
        } else {
            return [];
        }
    },
    getSportvatarBasicTemplate(layerId){
        let templates = this.getSportvatarBasicTemplates();
        return templates ? _.find(templates, function(item){ return item ? item.layer == layerId : false; }) : null;
    },
    getSportvatarOptionalTemplate(layerId){
        let templates = this.getSportvatarOptionalTemplates();
        return templates ? _.find(templates, function(item){ return item ? item.layer == layerId : false; }) : null;
    },
    templateBasicSvg(layerId){
        let template = this.getSportvatarBasicTemplate(layerId);
        return (template) ? window.wrapSvg(template.svg) : '';
    },
    templateBasicName(layerId){
        let template = this.getSportvatarBasicTemplate(layerId);
        return (template) ? template.name : layerId;
    },
    templateBasicRarity(layerId){
        let template = this.getSportvatarBasicTemplate(layerId);
        return (template) ? template.rarity : 'N/A';
    },
    templateUsedBy(layerId){
        let template = this.getSportvatarBasicTemplate(layerId);
        let extraS = (template && template.burned > 1) ? 's' : '';
        return (template) ? template.burned+' Sportvatar'+extraS : 'N/A';
    },
    templateOptionalSvg(layerId){
        let template = this.getSportvatarOptionalTemplate(layerId);
        return (template) ? window.wrapSvg(template.svg) : '';
    },
    templateOptionalName(layerId){
        let template = this.getSportvatarOptionalTemplate(layerId);
        return (template) ? template.name : layerId;
    },
    templateOptionalRarity(layerId){
        let template = this.getSportvatarOptionalTemplate(layerId);
        return (template) ? template.rarity : 'N/A';
    },
    getComponentBasicClass(layerId){
        let template = this.getSportvatarBasicTemplate(layerId);
        return (template) ? template.rarity : 'common';
    },
    getComponentOptionalClass(layerId){
        let template = this.getSportvatarOptionalTemplate(layerId);
        return (template) ? template.rarity : 'common';
    },
    saveSvg() {
        let svg = this.svg();
        svg = svg.replace('width="100%"', 'width="3000"').replace('height="100%"', 'height="3000"');
        const base64 = window.btoa(unescape(encodeURIComponent(svg)));
        const src = "data:image/svg+xml;base64," + base64;

        const evt = new MouseEvent("click", {
            view: window,
            bubbles: false,
            cancelable: true
        });
        const a = document.createElement("a");
        a.setAttribute("download", `sportvatar-${ (new Date().getTime()).toString(10) }.svg`);
        a.setAttribute("href", src);
        a.setAttribute("target", '_blank');
        a.dispatchEvent(evt);
        a.remove();
    },
    savePng() {
        l('save png');
        let svg = this.svg();
        l(svg);
        const img = new Image(3000, 3000);
        const canvas = document.createElement("canvas");
        canvas.width = 3000;
        canvas.height = 3000;
        const ctx = canvas.getContext("2d");
        svg = svg.replace('width="100%"', 'width="3000"').replace('height="100%"', 'height="3000"');
        const base64 = window.btoa(unescape(encodeURIComponent(svg)));
        const src = "data:image/svg+xml;base64," + base64;

        img.onload = () => {
            ctx.drawImage(img, 0, 0, 3000, 3000);

            let imgURI = canvas
                .toDataURL("image/png")
                .replace("image/png", "image/octet-stream");

            const evt = new MouseEvent("click", {
                view: window,
                bubbles: false,
                cancelable: true
            });
            const a = document.createElement("a");
            a.setAttribute("download", `sportvatar-${ (new Date().getTime()).toString(10) }.png`);
            a.setAttribute("href", imgURI);
            a.setAttribute("target", '_blank');
            a.dispatchEvent(evt);
            canvas.remove();
            a.remove();
            img.remove();
        }
        img.src = src;
    },
    handlePurchaseCompleted(){
        console.log('purchase completed!');
        window.location = '/sportvatars/'+this.sportvatar.id+'/'+window.user.addr;
    },
    handleOfferDeleted(){
        console.log('sale deleted!');
        this.currentPrice = null;
        this.sportvatarSaleOffer = null;
    },
    handleSaleCreated(){
        console.log('sale created!');
        this.sportvatarSaleOffer = {price: this.currentPrice};
    }
}));

window.initSportvatar = function(dispatch, address, sportvatarId){
    window.dispatch = dispatch;
    window.getSportvatar(address, sportvatarId);
}
